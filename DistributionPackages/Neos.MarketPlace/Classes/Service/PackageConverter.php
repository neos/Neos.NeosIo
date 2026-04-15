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

use Doctrine\ORM\EntityManagerInterface;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Exception;
use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Indexer\NodeIndexer;
use Neos\Cache\Backend\SimpleFileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Psr\Cache\CacheFactory;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\MarketPlace\Domain\Model\MarketplaceNodeType;
use Neos\MarketPlace\Domain\Model\Slug;
use Neos\MarketPlace\Domain\Model\Storage;
use Neos\MarketPlace\Utility\VersionNumber;
use Packagist\Api\Result\Package;
use Psr\Cache\CacheItemPoolInterface;
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
     * @var array{ cacheDirectory: string, token: string }
     */
    #[Flow\InjectConfiguration('github')]
    protected array $githubSettings;

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
     * @var EntityManagerInterface
     */
    #[Flow\Inject]
    protected $entityManager;

    protected CacheItemPoolInterface $gitHubApiCachePool;

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

    /**
     * @throws InvalidBackendException
     */
    protected function initializeObject(): void
    {
        $environmentConfiguration = new EnvironmentConfiguration('GitHubApi', FLOW_PATH_DATA);
        $cacheFactory = new CacheFactory(
            $environmentConfiguration
        );

        // Create a PSR-6 compatible cache
        $this->gitHubApiCachePool = $cacheFactory->create(
            'GitHubApiCache',
            SimpleFileBackend::class
        );

        $this->fetchPackagesState();
    }

    /**
     * Fetches the last activity and sync date for all packages to later speed up the package update check.
     */
    protected function fetchPackagesState(): void
    {
        $packageNodes = $this->storage->getPackageNodes();

        $this->packagesState = [];
        foreach ($packageNodes as $packageNode) {
            $packageName = $packageNode->getProperty('title');
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
    public function convert(Package $package): bool
    {
        $vendorName = explode('/', $package->getName())[0];

        $vendorNodeAggregateId = $this->storage->getOrCreateVendorNode($vendorName);
        if (!$vendorNodeAggregateId) {
            return false;
        }

        if (!$this->forceUpdate && !$this->packageRequiresUpdate($package)) {
            return false;
        }

        $packageNode = $this->storage->getPackageNode($package, $vendorNodeAggregateId);
        if (!$packageNode) {
            $packageNode = $this->storage->createPackageNode(
                $package,
                $vendorNodeAggregateId
            );
            if (!$packageNode) {
                return false;
            }
        }

        $this->packageSyncCache->set(
            Slug::create($package->getName()),
            (string)time(),
        );

        $upstreamMaintainerNames = array_map(static function (Package\Maintainer $maintainer) {
            return $maintainer->getName();
        }, $package->getMaintainers());

        $updated = $this->storage->updateNode(
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

        if ($package->isAbandoned() && trim((string)$packageNode->getProperty('abandoned')) === '') {
            $this->emitPackageAbandoned($packageNode);
        }

        if (!$updated && !$this->forceUpdate) {
            return false;
        }

        $this->createOrUpdateVersions($package, $packageNode);

        $this->updatePackageLastActivity(
            $packageNode,
            $packageNode->originDimensionSpacePoint,
        );
        // TODO: Update vendors once at the end of the package sync to reduce the number of updates
        $this->updateVendorLastActivity(
            $vendorNodeAggregateId,
            $packageNode->originDimensionSpacePoint,
        );

        try {
            $this->nodeIndexer->indexNode($packageNode);
        } catch (Exception $e) {
            $this->logger->error(
                'Error while indexing package node: ' . $e->getMessage(),
                LogEnvironment::fromMethodName(__METHOD__)
            );
        }

        return true;
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

        $lastActivities = [];
        foreach ($package->getVersions() as $version) {
            if (!$this->versionShouldBeImported($version)) {
                continue;
            }
            $time = \DateTime::createFromFormat(Storage::DATE_FORMAT, $version->getTime());
            if ($time) {
                $lastActivities[$time->getTimestamp()] = $time;
            }
        }
        krsort($lastActivities);
        $lastActivity = reset($lastActivities) ?: new \DateTime();

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
     */
    protected function createOrUpdateVersions(Package $package, Node $packageNode): bool
    {
        $upstreamVersions = array_reduce(
            $package->getVersions(),
            function(array $versions, Package\Version $version) {
                if ($this->versionShouldBeImported($version)) {
                    $versions[] = Slug::create($version->getVersion());
                }
                return $versions;
            },
            []
        );

        $versionsNode = $this->storage->getPackageVersionsNode($packageNode->aggregateId);
        if (!$versionsNode) {
            return false;
        }

        $versionNodes = $this->storage->getPackageVersionNodes($versionsNode->aggregateId);
        $versionNodesByVersion = [];
        foreach ($versionNodes as $versionNode) {
            $versionString = $versionNode->getProperty('version');
            $versionSlug = Slug::create($versionString);
            $versionNodesByVersion[$versionString] = $versionNode;
            if (!in_array($versionSlug, $upstreamVersions, true)) {
                $this->storage->removeNode($versionNode);
            } else {
                // Remove the version from the upstream versions to avoid duplicates
                $upstreamVersions = array_filter($upstreamVersions, static function ($slug) use ($versionSlug) {
                    return $slug !== $versionSlug;
                });
            }
        }

        foreach ($package->getVersions() as $version) {
            if (!$this->versionShouldBeImported($version)) {
                continue;
            }
            $versionNode = $versionNodesByVersion[$version->getVersion()] ?? null;
            $versionStability = VersionNumber::isVersionStable($version->getVersionNormalized());
            $stabilityLevel = VersionNumber::getStabilityLevel($version->getVersionNormalized());
            $versionNormalized = VersionNumber::toInteger($version->getVersionNormalized());
            $versionNodeType = match ($stabilityLevel) {
                'stable' => MarketPlaceNodeType::VERSION_STABLE,
                'dev' => MarketPlaceNodeType::VERSION_DEV,
                default => MarketPlaceNodeType::VERSION_UNSTABLE,
            };

            try {
                $this->storage->createOrUpdateVersionNode(
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
        }
        unset($versionNodes);
        return true;
    }

    /**
     * Iterates over all versions of the package and updates the last activity of the package node.
     */
    protected function updatePackageLastActivity(
        Node                      $packageNode,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void
    {
        $versionsNode = $this->storage->getPackageVersionsNode($packageNode->aggregateId);
        if (!$versionsNode) {
            return;
        }
        $versions = $this->storage->getPackageVersionNodes(
            $versionsNode->aggregateId,
        );

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
        if ($lastActiveVersion) {
            if ($lastActiveVersion->getProperty('time') > $packageNode->getProperty('lastActivity')) {
                $this->logger->debug('Updating last activity for package ' . $packageNode->getProperty('title'));
                $this->storage->updateNode(
                    $packageNode,
                    $originDimensionSpacePoint,
                    [
                        'lastActivity' => $lastActiveVersion->getProperty('time'),
                    ]
                );
            }
            $this->storage->updateNodeReferenceIfChanged(
                $packageNode,
                $originDimensionSpacePoint,
                ReferenceName::fromString('lastVersion'),
                $lastActiveVersion->aggregateId
            );
        }
        unset($versions);
    }

    /**
     * Iterates over all packages of the vendor and updates the last activity of the vendor node.
     */
    protected function updateVendorLastActivity(
        NodeAggregateId           $vendorNodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint
    ): void
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
        if ($lastActivePackageTime && $lastActivePackageTime > $vendorNode->getProperty('lastActivity')) {
            $this->logger->debug('Updating last activity for vendor ' . $vendorNode->getProperty('title'));
            $this->storage->updateNode(
                $vendorNode,
                $originDimensionSpacePoint,
                [
                    'lastActivity' => $lastActivePackageTime,
                ]
            );
        }
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
        return $value ? json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) : null;
    }

    /**
     * Signals that a node was abandoned.
     */
    #[Flow\Signal]
    protected function emitPackageAbandoned(Node $node): void
    {
    }
}
