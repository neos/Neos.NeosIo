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
use Github\Api\Markdown;
use Github\Api\Repository\Contents;
use Github\AuthMethod;
use Github\Client;
use Github\Exception\ApiLimitExceedException;
use Github\Exception\ExceptionInterface as GithubException;
use Github\Exception\RuntimeException;
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
use Neos\MarketPlace\Utility\PackageVersion;
use Neos\MarketPlace\Utility\VersionNumber;
use Neos\Utility\Arrays;
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

    private ?Client $client = null;

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
            ]
        );
        if (!$updated && !$this->forceUpdate) {
            return false;
        }

        $this->createOrUpdateMaintainers($package, $packageNode);
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

        $this->updateDownloadsCount($package, $packageNode);
        $this->updateGithubMetrics($package, $packageNode);
        $this->updatePackageAbandonedState($package, $packageNode);

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

    protected function updateDownloadsCount(Package $package, Node $packageNode): void
    {
        $downloads = $package->getDownloads();
        if (!$downloads instanceof Package\Downloads) {
            return;
        }
        $this->storage->updateNode(
            $packageNode,
            $packageNode->originDimensionSpacePoint,
            [
                'downloadTotal' => $downloads->getTotal(),
                'downloadMonthly' => $downloads->getMonthly(),
                'downloadDaily' => $downloads->getDaily(),
            ]);
    }

    protected function updateGithubMetrics(Package $package, Node $packageNode): void
    {
        if ($package->isAbandoned()) {
            $this->resetGithubMetrics($packageNode);
        } else {
            $repository = $package->getRepository();
            if (!str_contains($repository, 'github.com')) {
                return;
            }
            // todo make it a bit more clever
            $repository = str_replace('.git', '', $repository);
            preg_match('#(.*)://github.com/(.*)#', $repository, $matches);
            [$organization, $repository] = explode('/', $matches[2]);
            if (!$this->client) {
                $this->client = new Client();
                $this->client->addCache($this->gitHubApiCachePool);
                $this->client->authenticate($this->githubSettings['token'], null, AuthMethod::ACCESS_TOKEN);
            }
            try {
                $meta = $this->client->repositories()->show($organization, $repository);
                /** @phpstan-ignore function.alreadyNarrowedType */
                if (!is_array($meta)) {
                    $this->logger->warning(
                        sprintf('no repository info returned for %s', $repository),
                        LogEnvironment::fromMethodName(__METHOD__)
                    );
                    return;
                }
            } catch (ApiLimitExceedException $exception) {
                // Skip the processing if we hit the API rate limit
                $this->logger->warning(
                    $exception->getMessage(),
                    LogEnvironment::fromMethodName(__METHOD__)
                );
                return;
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() === 'Not Found') {
                    $this->logger->warning(
                        sprintf('Repository %s not found.', $repository),
                        LogEnvironment::fromMethodName(__METHOD__)
                    );
                    // todo special handling of not found repository ?
                    $this->resetGithubMetrics($packageNode);
                    return;
                }
                $this->logger->warning(
                    $exception->getMessage(),
                    LogEnvironment::fromMethodName(__METHOD__)
                );
                return;
            }
            $this->storage->updateNode(
                $packageNode,
                $packageNode->originDimensionSpacePoint,
                [
                    'githubStargazers' => (integer)Arrays::getValueByPath($meta, 'stargazers_count'),
                    'githubWatchers' => (integer)Arrays::getValueByPath($meta, 'watchers_count'),
                    'githubForks' => (integer)Arrays::getValueByPath($meta, 'forks_count'),
                    'githubIssues' => (integer)Arrays::getValueByPath($meta, 'open_issues_count'),
                    'githubAvatar' => trim((string)Arrays::getValueByPath($meta, 'organization.avatar_url'))
                ]
            );
            $this->updateGithubReadme($organization, $repository, $packageNode);
        }
    }

    /**
     */
    protected function updateGithubReadme(string $organization, string $repository, Node $packageNode): void
    {
        $client = $this->getGithubClient();

        if (!$client) {
            $this->logger->error('Github client not available', LogEnvironment::fromMethodName(__METHOD__));
            return;
        }

        try {
            $contents = new Contents($client);
            $metadata = $contents->readme($organization, $repository);
            /** @var Markdown $markdownApi */
            $markdownApi = $client->api('markdown');
            $markdownContent = file_get_contents($metadata['download_url']);
            if (!$markdownContent) {
                return;
            }
            $rendered = $markdownApi->render($markdownContent);
        } catch (ApiLimitExceedException $exception) {
            // Skip the processing if we hit the API rate limit
            $this->logger->warning($exception->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
            return;
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'Not Found') {
                return;
            }
            $this->logger->warning($exception->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
            return;
        } catch (GithubException $exception) {
            $this->logger->error($exception->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
            return;
        }

        $content = $this->postprocessGithubReadme($organization, $repository, $rendered);

        $readmeNode = $this->storage->getReadmeNode($packageNode->aggregateId);
        if (!$readmeNode) {
            $this->logger->warning(
                sprintf('No readme node found for package %s', $packageNode->getProperty('title')),
                LogEnvironment::fromMethodName(__METHOD__)
            );
            return;
        }
        $this->storage->updateNode(
            $readmeNode,
            $readmeNode->originDimensionSpacePoint,
            [
                'readmeSource' => $content,
            ]
        );
    }

    protected function postprocessGithubReadme(
        string $organization,
        string $repository,
        string $content
    ): string
    {
        $content = trim($content);
        $domain = 'https://raw.githubusercontent.com/' . $organization . '/' . $repository . '/master/';
        $r = [
            '#<svg aria-hidden="true" class="octicon octicon-link"[^>]*>.*?<\s*/\s*svg>#msi' => '',
            '#<a[^>]*><\s*/\s*a>#msi' => '',
            '#<article[^>]*>(.*)<\s*/\s*article>#msi' => '$1',
            '#<div class="announce[^>]*>(.*)<\s*/\s*div>$#msi' => '$1',
            '/href="(?!https?:\/\/)(?!data:)(?!#)/' => 'href="' . $domain,
            '/src="(?!https?:\/\/)(?!data:)(?!#)/' => 'src="' . $domain
        ];
        return trim((string)preg_replace(array_keys($r), array_values($r), $content));
    }

    protected function resetGithubMetrics(Node $packageNode): void
    {
        $this->storage->updateNode(
            $packageNode,
            $packageNode->originDimensionSpacePoint,
            [
                'githubStargazers' => 0,
                'githubWatchers' => 0,
                'githubForks' => 0,
                'githubIssues' => 0,
                'githubAvatar' => null
            ]
        );
    }

    /**
     */
    protected function updatePackageAbandonedState(Package $package, Node $packageNode): void
    {
        $this->storage->updateNode(
            $packageNode,
            $packageNode->originDimensionSpacePoint,
            [
                'abandoned' => (string)$package->isAbandoned(),
            ]
        );
        if ($package->isAbandoned() && trim((string)$packageNode->getProperty('abandoned')) === '') {
            $this->emitPackageAbandoned($packageNode);
        }
    }

    /**
     * Synchronizes the maintainers of the package.
     */
    protected function createOrUpdateMaintainers(Package $package, Node $packageNode): void
    {
        $upstreamMaintainerNames = array_map(static function (Package\Maintainer $maintainer) {
            return $maintainer->getName();
        }, $package->getMaintainers());

        $maintainerNodes = $this->storage->getPackageMaintainerNodes($packageNode->aggregateId);

        // Remove all maintainers that are not in the upstream package
        foreach ($maintainerNodes as $maintainerNode) {
            if (!in_array($maintainerNode->getProperty('title'), $upstreamMaintainerNames, true)) {
                $this->storage->removeNode($maintainerNode);
            }
        }

        // Create or update all maintainers
        foreach ($package->getMaintainers() as $maintainer) {
            $this->storage->createOrUpdateMaintainerNode(
                $maintainer,
                $packageNode
            );
        }
    }

    /**
     */
    protected function createOrUpdateVersions(Package $package, Node $packageNode): bool
    {
        $upstreamVersions = array_map(
            static fn($version) => Slug::create($version),
            array_keys($package->getVersions())
        );

        $versionsNode = $this->storage->getPackageVersionsNode($packageNode->aggregateId);
        if (!$versionsNode) {
            return false;
        }

        $versionNodes = $this->storage->getPackageVersionNodes($versionsNode->aggregateId);
        foreach ($versionNodes as $versionNode) {
            $versionSlug = Slug::create($versionNode->getProperty('version'));
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
                    $version->getVersion(),
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

        $lastActiveVersionTime = null;
        foreach ($versions as $version) {
            $lastActivity = $version->getProperty('time');
            if (!$lastActivity instanceof \DateTimeInterface) {
                continue;
            }
            if (!$lastActiveVersionTime || $lastActivity > $lastActiveVersionTime) {
                $lastActiveVersionTime = $lastActivity;
            }
        }
        $this->storage->updateNode(
            $packageNode,
            $originDimensionSpacePoint,
            [
                'lastActivity' => $lastActiveVersionTime,
            ]
        );
        $this->storage->updateNodeReference(
            $packageNode,
            $originDimensionSpacePoint,
            ReferenceName::fromString('lastVersion'),
            PackageVersion::extractLastVersion($versions)?->aggregateId
        );
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
        $this->storage->updateNode(
            $vendorNode,
            $originDimensionSpacePoint,
            [
                'lastActivity' => $lastActivePackageTime,
            ]
        );
        unset($packages);
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

    protected function getGithubClient(): ?Client
    {
        if (!$this->client) {
            try {
                $this->client = new Client();
                $this->client->addCache($this->gitHubApiCachePool);
                $this->client->authenticate($this->githubSettings['token'], null, AuthMethod::ACCESS_TOKEN);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
                return null;
            }
        }
        return $this->client;
    }

    /**
     * Signals that a node was abandoned.
     */
    #[Flow\Signal]
    protected function emitPackageAbandoned(Node $node): void
    {
    }
}
