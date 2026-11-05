<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Service;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Exception;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Indexer\NodeIndexer;
use Neos\Cache\Frontend\StringFrontend;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\MarketPlace\Domain\Model\MarketplaceNodeType;
use Neos\MarketPlace\Domain\Model\PackageSyncResult;
use Neos\MarketPlace\Domain\Model\Slug;
use Neos\MarketPlace\Domain\Model\Storage;
use Neos\MarketPlace\Utility\VersionNumber;
use Packagist\Api\Result\Package;
use Psr\Log\LoggerInterface;

/**
 * Convert package from packagist to nodes
 *
 * @api
 */
#[Flow\Scope('singleton')]
class PackageConverter
{
    /**
     * Properties of a package node that are not part of the search index.
     * Changing those does not require a re-index of the package.
     */
    private const NON_INDEXED_PROPERTIES = ['favers', 'downloadTotal'];

    /**
     * @var LoggerInterface
     */
    #[Flow\Inject('Neos.MarketPlace:Logger')]
    protected $logger;

    /**
     * @var StringFrontend
     */
    #[Flow\Inject('Neos.MarketPlace:PackageSyncCache')]
    protected $packageSyncCache;

    /**
     * @var array<string, array{lastActivity: \DateTimeInterface|null, lastSync: int|null}>
     */
    private array $packagesState = [];

    protected bool $forceUpdate = false;

    /**
     * Aggregate ids of the vendor nodes that were touched during this sync run.
     *
     * @var array<string, NodeAggregateId>
     */
    private array $touchedVendors = [];

    public function __construct(
        protected Storage     $storage,
        protected NodeIndexer $nodeIndexer,
    )
    {
    }

    public function setForceUpdate(bool $forceUpdate): void
    {
        $this->forceUpdate = $forceUpdate;
    }

    /**
     * Updates the index of the marketplace for all nodes added to the batch.
     *
     * @throws Exception
     * @throws Exception\ConfigurationException
     * @throws \Flowpack\ElasticSearch\Exception
     */
    public function updateIndex(): void
    {
        $this->nodeIndexer->flush();
    }

    protected function initializeObject(): void
    {
        $this->fetchPackagesState();
    }

    /**
     * Fetches the last activity and sync date for all packages to later speed up the package update check.
     */
    protected function fetchPackagesState(): void
    {
        $packageNodes = $this->storage->prefetchPackageNodes();

        $this->packagesState = [];
        foreach ($packageNodes as $packageName => $packageNode) {
            $this->packagesState[$packageName] = [
                'lastActivity' => $packageNode->getProperty('lastActivity'),
                'lastSync' => (int)($this->packageSyncCache->get(Slug::create($packageName)) ?: 0),
            ];
        }
        unset($packageNodes);
    }

    /**
     * Synchronizes the package with the given data.
     */
    public function convert(Package $package): PackageSyncResult
    {
        $vendorName = explode('/', $package->getName())[0];

        $vendorNodeAggregateId = $this->storage->getOrCreateVendorNode($vendorName);
        if (!$vendorNodeAggregateId) {
            return PackageSyncResult::NotRequired;
        }

        if (!$this->forceUpdate && !$this->packageRequiresUpdate($package)) {
            return PackageSyncResult::NotRequired;
        }

        $packageNode = $this->storage->getPackageNode($package, $vendorNodeAggregateId);
        if (!$packageNode) {
            $packageNode = $this->storage->createPackageNode(
                $package,
                $vendorNodeAggregateId
            );
            if (!$packageNode) {
                return PackageSyncResult::NotRequired;
            }
        }

        $upstreamMaintainerNames = array_map(static function (Package\Maintainer $maintainer) {
            return $maintainer->getName();
        }, $package->getMaintainers());

        $packageUpdateResult = $this->storage->updateNode(
            $packageNode,
            $packageNode->originDimensionSpacePoint,
            [
                'description' => $package->getDescription(),
                'time' => \DateTime::createFromFormat(
                    Storage::DATE_FORMAT,
                    $package->getTime()
                ),
                'type' => $package->getType(),
                'repository' => $package->getRepository(),
                'favers' => $package->getFavers(),
                'maintainers' => implode(',', $upstreamMaintainerNames),
                'downloadTotal' => $this->getDownloadsCount($package),
                'abandoned' => (string)$package->isAbandoned(),
            ]
        );

        if (!$packageUpdateResult->successful) {
            return PackageSyncResult::NotRequired;
        }

        if ($package->isAbandoned() && trim((string)$packageNode->getProperty('abandoned')) === '') {
            $this->emitPackageAbandoned($packageNode);
        }

        $versionSync = $this->createOrUpdateVersions($package, $packageNode);
        if (!$versionSync['successful']) {
            return PackageSyncResult::NotRequired;
        }

        $lastActivityChanged = $this->updatePackageLastActivity(
            $packageNode,
            $packageNode->originDimensionSpacePoint,
            $versionSync,
        );
        $this->markVendorAsTouched($vendorNodeAggregateId);

        $changed = $packageUpdateResult->hasChanged() || $versionSync['changed'] || $lastActivityChanged;

        // The metrics are not part of the search index, therefore there is no need to update it
        // if only those changed. The same applies if neither the package nor its versions changed.
        if ($packageUpdateResult->hasChangedOtherThan(self::NON_INDEXED_PROPERTIES) || $versionSync['changed']) {
            try {
                $this->nodeIndexer->indexNode($packageNode);
            } catch (Exception $e) {
                $this->logger->error(
                    'Error while indexing package node: ' . $e->getMessage(),
                    LogEnvironment::fromMethodName(__METHOD__)
                );
            }
        }

        // Only mark the package as synced if the import did not fail
        $this->packageSyncCache->set(
            Slug::create($package->getName()),
            (string)time(),
        );

        return $changed ? PackageSyncResult::Changed : PackageSyncResult::Unchanged;
    }

    /**
     * Returns true if the package needs to be updated.
     * This is the case if:
     * - the package is unknown
     * - there is no last activity or last sync recorded
     * - the last activity is newer than the last recorded activity
     * - the last sync is older than 1 day
     * @throws \DateInvalidOperationException
     */
    protected function packageRequiresUpdate(Package $package): bool
    {
        if (!array_key_exists($package->getName(), $this->packagesState)) {
            return true;
        }
        $lastRecordedActivity = $this->packagesState[$package->getName()]['lastActivity'] ?? null;
        $lastSync = $this->packagesState[$package->getName()]['lastSync'] ?? null;
        if (!$lastRecordedActivity instanceof \DateTimeInterface || !$lastSync) {
            return true;
        }

        $lastActivity = null;
        foreach ($package->getVersions() as $version) {
            if (!$this->versionShouldBeImported($version)) {
                continue;
            }
            $time = \DateTime::createFromFormat(Storage::DATE_FORMAT, $version->getTime());
            if ($time && ($lastActivity === null || $time > $lastActivity)) {
                $lastActivity = $time;
            }
        }
        $lastActivity ??= new \DateTime();

        return (
            $lastActivity > $lastRecordedActivity
            || $lastSync < (time() - 86400) // 1 day
        );
    }

    protected function getDownloadsCount(Package $package): int
    {
        $downloads = $package->getDownloads();
        if (!$downloads instanceof Package\Downloads) {
            return 0;
        }
        return $downloads->getTotal();
    }

    /**
     * Creates, updates and removes the version nodes of the given package.
     *
     * @return array{versionsNode: Node, versionNodes: Node[], changed: bool, successful: bool} The versions node, the
     *                                                                  version nodes as they were before the update,
     *                                                                  whether any of them was created, updated or
     *                                                                  removed and whether the update was successful
     */
    protected function createOrUpdateVersions(Package $package, Node $packageNode): array
    {
        $upstreamVersions = [];
        foreach ($package->getVersions() as $version) {
            if ($this->versionShouldBeImported($version)) {
                $upstreamVersions[$version->getVersion()] = $version;
            }
        }

        $versionsNode = $this->storage->getPackageVersionsNode($packageNode->aggregateId);
        if (!$versionsNode) {
            return ['versionsNode' => $packageNode, 'versionNodes' => [], 'changed' => false, 'successful' => false];
        }

        $changed = false;
        $versionNodes = $this->storage->getPackageVersionNodes($versionsNode->aggregateId);
        $versionNodesByVersion = [];
        foreach ($versionNodes as $versionNode) {
            $versionString = (string)$versionNode->getProperty('version');
            $versionNodesByVersion[$versionString] = $versionNode;
            if (!array_key_exists($versionString, $upstreamVersions)) {
                // The version is not available upstream anymore
                if ($this->storage->removeNode($versionNode)) {
                    $changed = true;
                }
            }
        }

        foreach ($upstreamVersions as $versionString => $version) {
            $versionNode = $versionNodesByVersion[$versionString] ?? null;
            $versionStability = VersionNumber::isVersionStable($version->getVersionNormalized());
            $stabilityLevel = VersionNumber::getStabilityLevel($version->getVersionNormalized());
            $versionNormalized = VersionNumber::toInteger($version->getVersionNormalized());
            $versionNodeType = match ($stabilityLevel) {
                'stable' => MarketPlaceNodeType::VERSION_STABLE,
                'dev' => MarketPlaceNodeType::VERSION_DEV,
                default => MarketPlaceNodeType::VERSION_UNSTABLE,
            };

            try {
                $result = $this->storage->createOrUpdateVersionNode(
                    $versionsNode->aggregateId,
                    $versionNode,
                    $versionNodeType,
                    [
                        'version' => $version->getVersion(),
                        'description' => $version->getDescription(),
                        'keywords' => $this->arrayToStringCaster($version->getKeywords()),
                        'homepage' => $version->getHomepage(),
                        'versionNormalized' => $versionNormalized,
                        'stability' => $versionStability,
                        'stabilityLevel' => $stabilityLevel,
                        'license' => $this->arrayToStringCaster($version->getLicenses()),
                        'type' => $version->getType(),
                        'time' => \DateTime::createFromFormat(Storage::DATE_FORMAT, $version->getTime()),
                        'provide' => $this->arrayToJsonCaster($version->getProvide()),
                        'bin' => $this->arrayToJsonCaster($version->getBin()),
                        'require' => $this->arrayToJsonCaster($version->getRequire()),
                        'requireDev' => $this->arrayToJsonCaster($version->getRequireDev()),
                        'suggest' => $this->arrayToJsonCaster($version->getSuggest()),
                        'conflict' => $this->arrayToJsonCaster($version->getConflict()),
                        'replace' => $this->arrayToJsonCaster($version->getReplace()),
                        'sourceType' => $version->getSource()?->getType(),
                        'sourceReference' => $version->getSource()?->getReference(),
                        'sourceUrl' => $version->getSource()?->getUrl(),
                        'distType' => $version->getDist()?->getType(),
                        'distReference' => $version->getDist()?->getReference(),
                        'distUrl' => $version->getDist()?->getUrl(),
                        'distShasum' => $version->getDist()?->getShasum(),
                    ],
                );
            } catch (\JsonException $e) {
                $this->logger->error(
                    'Error while converting version data to JSON: ' . $e->getMessage(),
                    LogEnvironment::fromMethodName(__METHOD__)
                );
                continue;
            }
            if ($result->hasChanged()) {
                $changed = true;
            }
            if (!$result->successful) {
                return [
                    'versionsNode' => $versionsNode,
                    'versionNodes' => iterator_to_array($versionNodes),
                    'changed' => $changed,
                    'successful' => false
                ];
            }
        }
        return [
            'versionsNode' => $versionsNode,
            'versionNodes' => iterator_to_array($versionNodes),
            'changed' => $changed,
            'successful' => true
        ];
    }

    /**
     * Iterates over all versions of the package and updates the last activity of the package node.
     *
     * @param array{versionsNode: Node, versionNodes: Node[], changed: bool, successful: bool} $versionSync
     * @return bool True if the last activity of the package node was changed
     */
    protected function updatePackageLastActivity(
        Node                      $packageNode,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        array                     $versionSync
    ): bool
    {
        // The version nodes only have to be reloaded if they might have changed
        $versions = $versionSync['changed']
            ? iterator_to_array($this->storage->getPackageVersionNodes($versionSync['versionsNode']->aggregateId))
            : $versionSync['versionNodes'];

        /** @var Node|null $lastActiveVersion */
        $lastActiveVersion = null;
        $lastActiveVersionStability = 0;
        foreach ($versions as $version) {
            $lastActivity = $version->getProperty('time');
            if (!$lastActivity instanceof \DateTimeInterface) {
                continue;
            }
            $versionStability = VersionNumber::getStabilityLevelAsInteger($version->getProperty('stabilityLevel'));
            if (!$lastActiveVersion
                || $lastActiveVersionStability < $versionStability
                || ($lastActiveVersionStability === $versionStability && $lastActivity > $lastActiveVersion->getProperty('time'))
            ) {
                $lastActiveVersionStability = $versionStability;
                $lastActiveVersion = $version;
            }
        }
        if (!$lastActiveVersion) {
            return false;
        }

        $changed = false;
        $currentLastActivity = $packageNode->getProperty('lastActivity');
        if (!$currentLastActivity instanceof \DateTimeInterface
            || $lastActiveVersion->getProperty('time') > $currentLastActivity
        ) {
            $this->logger->debug('Updating last activity for package ' . $packageNode->getProperty('title'));
            $result = $this->storage->updateNode(
                $packageNode,
                $originDimensionSpacePoint,
                [
                    'lastActivity' => $lastActiveVersion->getProperty('time'),
                ]
            );
            if (!$result->successful) {
                return false;
            }
            $changed = $result->hasChanged();
        }

        // The reference can only point to another version if the versions changed
        if ($versionSync['changed']) {
            $this->storage->updateNodeReferenceIfChanged(
                $packageNode,
                $originDimensionSpacePoint,
                ReferenceName::fromString('lastVersion'),
                $lastActiveVersion->aggregateId
            );
        }
        return $changed;
    }

    /**
     * Remembers that the given vendor was touched, so its last activity can be updated
     * once at the end of the sync instead of after every single package.
     */
    protected function markVendorAsTouched(NodeAggregateId $vendorNodeAggregateId): void
    {
        $this->touchedVendors[$vendorNodeAggregateId->value] = $vendorNodeAggregateId;
    }

    /**
     * Updates the last activity of all vendors that were touched during this sync run.
     */
    public function updateVendors(): void
    {
        foreach ($this->touchedVendors as $vendorNodeAggregateId) {
            $this->updateVendorLastActivity($vendorNodeAggregateId);
        }
        $this->touchedVendors = [];
    }

    /**
     * Iterates over all packages of the vendor and updates the last activity of the vendor node.
     */
    protected function updateVendorLastActivity(NodeAggregateId $vendorNodeAggregateId): void
    {
        $packages = $this->storage->getPackageNodes($vendorNodeAggregateId);
        $vendorNode = $this->storage->getNodeByAggregateId($vendorNodeAggregateId);

        if (!$vendorNode) {
            $this->logger->error(
                sprintf('Vendor node with aggregate ID %s not found.', $vendorNodeAggregateId->value),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return;
        }

        $lastActivePackageTime = null;
        foreach ($packages as $packageNode) {
            $lastActivity = $packageNode->getProperty('lastActivity');
            if ($lastActivity instanceof \DateTimeInterface && (!$lastActivePackageTime || $lastActivity > $lastActivePackageTime)) {
                $lastActivePackageTime = $lastActivity;
            }
        }
        if ($lastActivePackageTime === null) {
            return;
        }
        $currentLastActivity = $vendorNode->getProperty('lastActivity');
        if ($currentLastActivity instanceof \DateTimeInterface && $lastActivePackageTime <= $currentLastActivity) {
            return;
        }
        $this->logger->debug('Updating last activity for vendor ' . $vendorNode->getProperty('title'));
        $this->storage->updateNode(
            $vendorNode,
            $vendorNode->originDimensionSpacePoint,
            [
                'lastActivity' => $lastActivePackageTime,
            ]
        );
        unset($packages);
    }

    protected function versionShouldBeImported(Package\Version $version): bool
    {
        return $version->getVersion() === 'dev-master'
            || $version->getVersion() === 'dev-main'
            || VersionNumber::isVersionStable($version->getVersionNormalized());
    }

    /**
     * @param string[]|null $value
     */
    protected function arrayToStringCaster(?array $value): string
    {
        $value = $value ?: [];
        return implode(', ', $value);
    }

    /**
     * @param array<string|int, mixed>|null $value
     * @throws \JsonException
     */
    protected function arrayToJsonCaster(?array $value): ?string
    {
        if (!$value) {
            return null;
        }
        // Sort the keys to make sure the result is stable, even if the upstream data is reordered
        ksort($value);
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * Signals that a node was abandoned.
     */
    #[Flow\Signal]
    protected function emitPackageAbandoned(Node $node): void
    {
    }
}
