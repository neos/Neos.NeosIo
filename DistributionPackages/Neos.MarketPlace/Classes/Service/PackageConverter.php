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
use Neos\MarketPlace\Domain\Model\PackageSyncState;
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
     * A package is re-checked at least once a day, even if nothing changed upstream.
     */
    private const RESYNC_INTERVAL = 86400;

    /**
     * The lifetime of the sync state cache entries. It must be considerably larger than the
     * sync interval, otherwise the state is lost and every package is treated as new.
     */
    private const SYNC_STATE_LIFETIME = 2592000;

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
     * @var array<string, array{lastActivity: \DateTimeInterface|null, state: PackageSyncState|null}>
     */
    private array $packagesState = [];

    protected bool $forceUpdate = false;

    /**
     * Aggregate ids of the vendor nodes that were touched during this sync run.
     *
     * @var array<string, NodeAggregateId>
     */
    private array $touchedVendors = [];

    /**
     * Statistics of the current sync run.
     *
     * @var array{packagesChecked: int, packagesSkipped: int, packagesChanged: int, versionSyncs: int, versionSyncsSkipped: int}
     */
    private array $statistics = [
        'packagesChecked' => 0,
        'packagesSkipped' => 0,
        'packagesChanged' => 0,
        'versionSyncs' => 0,
        'versionSyncsSkipped' => 0
    ];

    /**
     * @return array{packagesChecked: int, packagesSkipped: int, packagesChanged: int, versionSyncs: int, versionSyncsSkipped: int}
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

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
                'state' => PackageSyncState::fromCacheValue(
                    $this->packageSyncCache->get(Slug::create($packageName))
                ),
            ];
        }
        unset($packageNodes);
    }

    /**
     * Synchronizes the package with the given data.
     */
    public function convert(Package $package): PackageSyncResult
    {
        $this->statistics['packagesChecked']++;

        $vendorName = explode('/', $package->getName())[0];

        $vendorNodeAggregateId = $this->storage->getOrCreateVendorNode($vendorName);
        if (!$vendorNodeAggregateId) {
            return PackageSyncResult::NotRequired;
        }

        if (!$this->forceUpdate && !$this->packageRequiresUpdate($package)) {
            $this->statistics['packagesSkipped']++;
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

        // The version payload is built before anything is written, so that the sync state can be
        // compared with the previous one without touching the content repository at all.
        $versionPayloads = $this->buildVersionPayloads($package);
        $versionsHash = $this->calculateVersionsHash($versionPayloads);
        $versionsUnchanged = $this->versionsAreUnchanged($package->getName(), $versionsHash, $packageNode);

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

        if ($versionsUnchanged) {
            // The versions did not change since the last sync, so there is no need to load them again
            $this->statistics['versionSyncsSkipped']++;
            $versionSync = [
                'versionsNode' => null,
                'versionNodes' => [],
                'changed' => false,
                'successful' => true
            ];
        } else {
            $this->statistics['versionSyncs']++;
            $versionSync = $this->createOrUpdateVersions($versionPayloads, $packageNode);
            if (!$versionSync['successful']) {
                return PackageSyncResult::NotRequired;
            }
        }

        $lastActivityChanged = $this->updatePackageLastActivity(
            $packageNode,
            $packageNode->originDimensionSpacePoint,
            $versionSync,
            $versionsUnchanged,
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
            (new PackageSyncState(time(), $versionsHash))->toCacheValue(),
            [],
            self::SYNC_STATE_LIFETIME
        );

        if ($changed) {
            $this->statistics['packagesChanged']++;
            return PackageSyncResult::Changed;
        }
        return PackageSyncResult::Unchanged;
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
        $syncState = $this->packagesState[$package->getName()]['state'] ?? null;
        if (!$lastRecordedActivity instanceof \DateTimeInterface || $syncState === null || !$syncState->syncedAt) {
            return true;
        }

        // The last activity is compared with the activity that was recorded for the package.
        // Both values must be calculated the same way, otherwise a package would be considered
        // outdated forever.
        $lastActivity = $this->calculateLastActivity($package);
        if ($lastActivity !== null && $lastActivity > $lastRecordedActivity) {
            return true;
        }

        return $syncState->syncedAt < (time() - self::RESYNC_INTERVAL);
    }

    /**
     * Whether the versions of the given package are identical to the ones of the last sync.
     */
    protected function versionsAreUnchanged(string $packageName, string $versionsHash, Node $packageNode): bool
    {
        if ($this->forceUpdate) {
            return false;
        }
        $syncState = $this->packagesState[$packageName]['state'] ?? null;
        if ($syncState === null
            || $syncState->versionsHash === null
            || $syncState->versionsHash !== $versionsHash
        ) {
            return false;
        }
        // The state must be newer than the package node, otherwise the version nodes might have
        // been removed without the package node being touched.
        if ($syncState->syncedAt < $packageNode->timestamps->created->getTimestamp()) {
            return false;
        }
        return true;
    }

    /**
     * Calculates the activity of a package the same way it is calculated when the package node
     * is updated, so that both values can be compared with each other.
     *
     * The most stable version wins, only if there are multiple versions with the same stability
     * the most recent one is used.
     */
    protected function calculateLastActivity(Package $package): ?\DateTimeInterface
    {
        $lastActivity = null;
        $lastStability = -1;
        foreach ($package->getVersions() as $version) {
            if (!$this->versionShouldBeImported($version)) {
                continue;
            }
            $time = \DateTime::createFromFormat(Storage::DATE_FORMAT, $version->getTime());
            if (!$time) {
                continue;
            }
            $stability = VersionNumber::getStabilityLevelAsInteger(
                VersionNumber::getStabilityLevel($version->getVersionNormalized())
            );
            if ($lastActivity === null
                || $lastStability < $stability
                || ($lastStability === $stability && $time > $lastActivity)
            ) {
                $lastStability = $stability;
                $lastActivity = $time;
            }
        }
        return $lastActivity;
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
     * Builds the properties of all version nodes that should exist for the given package.
     *
     * @return array<string, array<string, mixed>> The version properties indexed by version name
     */
    protected function buildVersionPayloads(Package $package): array
    {
        $payloads = [];
        foreach ($package->getVersions() as $version) {
            if (!$this->versionShouldBeImported($version)) {
                continue;
            }
            try {
                $payloads[$version->getVersion()] = [
                    'version' => $version->getVersion(),
                    'description' => $version->getDescription(),
                    'keywords' => $this->arrayToStringCaster($version->getKeywords()),
                    'homepage' => $version->getHomepage(),
                    'versionNormalized' => VersionNumber::toInteger($version->getVersionNormalized()),
                    'stability' => VersionNumber::isVersionStable($version->getVersionNormalized()),
                    'stabilityLevel' => VersionNumber::getStabilityLevel($version->getVersionNormalized()),
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
                ];
            } catch (\JsonException $e) {
                $this->logger->error(
                    'Error while converting version data to JSON: ' . $e->getMessage(),
                    LogEnvironment::fromMethodName(__METHOD__)
                );
                // The version is kept as it is, so that it is not removed by mistake
                $payloads[$version->getVersion()] = [
                    'version' => $version->getVersion(),
                    'stabilityLevel' => VersionNumber::getStabilityLevel($version->getVersionNormalized())
                ];
            }
        }
        return $payloads;
    }

    /**
     * Calculates a hash of all version properties, so that it can be compared with the hash of
     * the previous sync to detect changes without loading the version nodes from the database.
     *
     * @param array<string, array<string, mixed>> $versionPayloads
     */
    protected function calculateVersionsHash(array $versionPayloads): string
    {
        $hashable = [];
        foreach ($versionPayloads as $version => $properties) {
            $hashable[$version] = array_map(
                static fn (mixed $value): string => $value instanceof \DateTimeInterface
                    ? $value->format(\DateTimeInterface::ATOM)
                    : (string)($value ?? ''),
                $properties
            );
        }
        ksort($hashable);
        return md5((string)json_encode($hashable));
    }

    /**
     * Creates, updates and removes the version nodes of the given package.
     *
     * @param array<string, array<string, mixed>> $versionPayloads
     * @return array{versionsNode: Node|null, versionNodes: Node[], changed: bool, successful: bool} The versions node,
     *                                                                  the version nodes as they were before the update,
     *                                                                  whether any of them was created, updated or
     *                                                                  removed and whether the update was successful
     */
    protected function createOrUpdateVersions(array $versionPayloads, Node $packageNode): array
    {
        $versionsNode = $this->storage->getPackageVersionsNode($packageNode->aggregateId);
        if (!$versionsNode) {
            return ['versionsNode' => null, 'versionNodes' => [], 'changed' => false, 'successful' => false];
        }

        $changed = false;
        $versionNodes = $this->storage->getPackageVersionNodes($versionsNode->aggregateId);
        $versionNodesByVersion = [];
        foreach ($versionNodes as $versionNode) {
            $versionString = (string)$versionNode->getProperty('version');
            $versionNodesByVersion[$versionString] = $versionNode;
            if (!array_key_exists($versionString, $versionPayloads)) {
                // The version is not available upstream anymore
                if ($this->storage->removeNode($versionNode)) {
                    $changed = true;
                }
            }
        }

        foreach ($versionPayloads as $versionString => $properties) {
            $versionNode = $versionNodesByVersion[$versionString] ?? null;
            $versionNodeType = match ($properties['stabilityLevel']) {
                'stable' => MarketPlaceNodeType::VERSION_STABLE,
                'dev' => MarketPlaceNodeType::VERSION_DEV,
                default => MarketPlaceNodeType::VERSION_UNSTABLE,
            };

            $result = $this->storage->createOrUpdateVersionNode(
                $versionsNode->aggregateId,
                $versionNode,
                $versionNodeType,
                $properties
            );
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
     * @param array{versionsNode: Node|null, versionNodes: Node[], changed: bool, successful: bool} $versionSync
     * @param bool $versionsUnchanged Whether the versions are identical to the ones of the last sync
     * @return bool True if the last activity of the package node was changed
     */
    protected function updatePackageLastActivity(
        Node                      $packageNode,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        array                     $versionSync,
        bool                      $versionsUnchanged
    ): bool
    {
        if ($versionsUnchanged) {
            // The versions did not change, so neither did the last activity
            return false;
        }

        $versionsNode = $versionSync['versionsNode'];
        if ($versionsNode === null) {
            return false;
        }

        // The version nodes only have to be reloaded if they might have changed
        $versions = $versionSync['changed']
            ? iterator_to_array($this->storage->getPackageVersionNodes($versionsNode->aggregateId))
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
