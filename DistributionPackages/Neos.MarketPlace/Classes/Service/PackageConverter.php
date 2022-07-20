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

use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Indexer\NodeIndexer;
use Github\Api\Repository\Contents;
use Github\Client;
use Github\Exception\ApiLimitExceedException;
use Github\Exception\ExceptionInterface as GithubException;
use Github\Exception\RuntimeException;
use Neos\Cache\Backend\SimpleFileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Exception\InvalidBackendException;
use Neos\Cache\Psr\Cache\CacheFactory;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Property\Exception\InvalidPropertyMappingConfigurationException;
use Neos\Flow\Security\Exception;
use Neos\Flow\Utility\Now;
use Neos\Fusion\Core\Cache\ContentCache;
use Neos\MarketPlace\Domain\Model\Slug;
use Neos\MarketPlace\Domain\Model\Storage;
use Neos\MarketPlace\Utility\VersionNumber;
use Neos\Utility\Arrays;
use Packagist\Api\Result\Package;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Convert package from packagist to node
 *
 * @api
 */
class PackageConverter
{
    private const DATE_FORMAT = 'Y-m-d\TH:i:sO';

    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    /**
     * @var PackageVersion
     * @Flow\Inject
     */
    protected $packageVersion;

    /**
     * @Flow\InjectConfiguration(path="github")
     * @var array
     */
    protected array $githubSettings = [];

    /**
     * @var NodeIndexer
     * @Flow\Inject
     */
    protected $nodeIndexer;

    /**
     * @var ContentCache
     * @Flow\Inject
     */
    protected $contentCache;

    private CacheItemPoolInterface $gitHubApiCachePool;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $logger;

    private bool $forceUpdate;

    private Storage $storage;

    private ?Client $client = null;

    public function __construct(bool $forceUpdate)
    {
        $this->storage = new Storage();
        $this->forceUpdate = $forceUpdate;
    }

    /**
     * @return void
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
    }

    /**
     * Converts $source to a node
     *
     * @throws Exception
     * @throws InvalidPropertyMappingConfigurationException
     * @throws NodeTypeNotFoundException
     * @throws \Flowpack\ElasticSearch\ContentRepositoryAdaptor\Exception
     * @throws \JsonException
     * @throws \Neos\Eel\Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\MarketPlace\Exception
     * @throws NodeException
     */
    public function convert(Package $package): NodeInterface
    {
        $vendor = explode('/', $package->getName())[0];
        $packageNameSlug = Slug::create($package->getName());
        $vendorNode = $this->storage->createVendor($vendor);
        try {
            /** @var NodeInterface $packageNode */
            $packageNode = $vendorNode->findNamedChildNode(NodeName::fromString($packageNameSlug));
            if ($this->forceUpdate === true || $this->packageRequireUpdate($package, $packageNode)) {
                $node = $this->update($package, $packageNode);
            } else {
                return $packageNode;
            }
        } /** @noinspection BadExceptionsProcessingInspection */ catch (NodeException $exception) {
            $node = $this->create($package, $vendorNode);
        }

        $this->createOrUpdateMaintainers($package, $node);
        $this->createOrUpdateVersions($package, $node);

        $this->getPackageLastActivity($node);
        $this->getVendorLastActivity($vendorNode);

        $this->handleDownloads($package, $node);
        $this->handleGithubMetrics($package, $node);

        $this->handleAbandonedPackageOrVersion($package, $node);

        $this->nodeIndexer->indexNode($node);
        $this->contentCache->flushByTag('Node_' . $node->getNodeAggregateIdentifier()->getCacheEntryIdentifier());

        return $node;
    }

    /**
     * @throws NodeException
     */
    protected function packageRequireUpdate(Package $package, NodeInterface $packageNode): bool
    {
        $lastActivities = [];
        foreach ($package->getVersions() as $version) {
            $time = $version->getTime();
            if ($time === null) {
                continue;
            }
            $time = \DateTime::createFromFormat(self::DATE_FORMAT, $time);
            $lastActivities[$time->getTimestamp()] = $time;
        }
        krsort($lastActivities);
        $lastActivity = reset($lastActivities) ?: new \DateTime();
        $lastRecordedActivity = $packageNode->getProperty('lastActivity');
        $lastSync = $packageNode->getProperty('lastSync');

        return (
            !($lastRecordedActivity instanceof \DateTime)
            || $lastActivity > $lastRecordedActivity
            || !($lastSync instanceof \DateTime)
            || $lastSync < (new Now())->sub(new \DateInterval('P1D'))
        );
    }

    protected function handleDownloads(Package $package, NodeInterface $node): void
    {
        $downloads = $package->getDownloads();
        if (!$downloads instanceof Package\Downloads) {
            return;
        }
        $this->updateNodeProperties($node, [
            'downloadTotal' => $downloads->getTotal(),
            'downloadMonthly' => $downloads->getMonthly(),
            'downloadDaily' => $downloads->getDaily(),
        ]);
    }

    /**
     * @throws Exception
     * @throws NodeException
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     */
    protected function handleGithubMetrics(Package $package, NodeInterface $node): void
    {
        if ($package->isAbandoned()) {
            $this->resetGithubMetrics($node);
        } else {
            $repository = $package->getRepository();
            if (strpos($repository, 'github.com') === false) {
                return;
            }
            // todo make it a bit more clever
            $repository = str_replace('.git', '', $repository);
            preg_match('#(.*)://github.com/(.*)#', $repository, $matches);
            [$organization, $repository] = explode('/', $matches[2]);
            if (!$this->client) {
                $this->client = new Client();
                $this->client->addCache($this->gitHubApiCachePool);
                $this->client->authenticate($this->githubSettings['token'], null, Client::AUTH_ACCESS_TOKEN);
            }
            try {
                $meta = $this->client->repositories()->show($organization, $repository);
                if (!is_array($meta)) {
                    $this->logger->warning(sprintf('no repository info returned for %s', $repository), LogEnvironment::fromMethodName(__METHOD__));
                    return;
                }
            } catch (ApiLimitExceedException $exception) {
                // Skip the processing if we hit the API rate limit
                $this->logger->warning($exception->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
                return;
            } catch (RuntimeException $exception) {
                if ($exception->getMessage() === 'Not Found') {
                    $this->logger->warning(sprintf('Repository %s not found.', $repository), LogEnvironment::fromMethodName(__METHOD__));
                    // todo special handling of not found repository ?
                    $this->resetGithubMetrics($node);
                    return;
                }
                $this->logger->warning($exception->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
                return;
            }
            $this->updateNodeProperties($node, [
                'githubStargazers' => (integer)Arrays::getValueByPath($meta, 'stargazers_count'),
                'githubWatchers' => (integer)Arrays::getValueByPath($meta, 'watchers_count'),
                'githubForks' => (integer)Arrays::getValueByPath($meta, 'forks_count'),
                'githubIssues' => (integer)Arrays::getValueByPath($meta, 'open_issues_count'),
                'githubAvatar' => trim((string)Arrays::getValueByPath($meta, 'organization.avatar_url'))
            ]);
            $this->handleGithubReadme($organization, $repository, $node);
        }
    }

    /**
     * @throws Exception
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws NodeException
     */
    protected function handleGithubReadme(string $organization, string $repository, NodeInterface $node): void
    {
        try {
            $readmeNode = $node->findNamedChildNode(NodeName::fromString('readme'));
        } /** @noinspection BadExceptionsProcessingInspection */ catch (NodeException $exception) {
            return;
        }

        if (!$this->client) {
            try {
                $this->client = new Client();
                $this->client->addCache($this->gitHubApiCachePool);
                $this->client->authenticate($this->githubSettings['token'], null, Client::AUTH_ACCESS_TOKEN);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage(), LogEnvironment::fromMethodName(__METHOD__));
                return;
            }
        }
        try {
            $contents = new Contents($this->client);
            $metadata = $contents->readme($organization, $repository);
            $rendered = $this->client->api('markdown')->render(file_get_contents($metadata['download_url']));
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
        $readmeNode->setProperty('readmeSource', $content);
    }

    protected function postprocessGithubReadme(string $organization, string $repository, string $content): string
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
        return trim(preg_replace(array_keys($r), array_values($r), $content));
    }

    protected function resetGithubMetrics(NodeInterface $node): void
    {
        $this->updateNodeProperties($node, [
            'githubStargazers' => 0,
            'githubWatchers' => 0,
            'githubForks' => 0,
            'githubIssues' => 0,
            'githubAvatar' => null
        ]);
    }

    /**
     * @throws NodeException
     */
    protected function handleAbandonedPackageOrVersion(Package $package, NodeInterface $node): void
    {
        if ($package->isAbandoned() && trim((string)$node->getProperty('abandoned')) === '') {
            $node->setProperty('abandoned', (string)$package->isAbandoned());
            $this->emitPackageAbandoned($node);
        } else {
            $node->setProperty('abandoned', (string)$package->isAbandoned());
        }
    }

    /**
     * @throws NodeTypeNotFoundException
     */
    protected function create(Package $package, NodeInterface $parentNode): NodeInterface
    {
        $name = Slug::create($package->getName());
        $data = [
            'uriPathSegment' => $name,
            'title' => $package->getName(),
            'description' => $package->getDescription(),
            'time' => \DateTime::createFromFormat(self::DATE_FORMAT, $package->getTime()),
            'type' => $package->getType(),
            'repository' => $package->getRepository(),
            'favers' => $package->getFavers()
        ];

        $node = $parentNode->createNode($name, $this->nodeTypeManager->getNodeType('Neos.MarketPlace:Package'));
        $this->setNodeProperties($node, $data);
        return $node;
    }

    protected function update(Package $package, NodeInterface $node): NodeInterface
    {
        $this->updateNodeProperties($node, [
            'description' => $package->getDescription(),
            'time' => \DateTime::createFromFormat(self::DATE_FORMAT, $package->getTime()),
            'type' => $package->getType(),
            'repository' => $package->getRepository(),
            'favers' => $package->getFavers(),
            'lastSync' => new \DateTime(),
        ]);
        return $node;
    }

    /**
     * @throws NodeTypeNotFoundException
     * @throws \Neos\Eel\Exception
     * @throws NodeException
     */
    protected function createOrUpdateMaintainers(Package $package, NodeInterface $node): void
    {
        $upstreamMaintainers = array_map(static function (Package\Maintainer $maintainer) {
            return Slug::create($maintainer->getName());
        }, $package->getMaintainers());
        $maintainerStorage = $node->getNode('maintainers');
        /** @var TraversableNodeInterface[] $maintainers */
        /** @noinspection PhpUndefinedMethodInspection */
        $maintainers = (new FlowQuery([$maintainerStorage]))->children('[instanceof Neos.MarketPlace:Maintainer]');
        foreach ($maintainers as $maintainer) {
            if (in_array($maintainer->getNodeName(), $upstreamMaintainers)) {
                continue;
            }
            $maintainer->remove();
        }

        foreach ($package->getMaintainers() as $maintainer) {
            $name = Slug::create($maintainer->getName());
            $node = $maintainerStorage->getNode($name);
            $data = [
                'title' => $maintainer->getName(),
                'email' => $maintainer->getEmail(),
                'homepage' => $maintainer->getHomepage()
            ];
            if ($node === null) {
                $node = $maintainerStorage->createNode($name, $this->nodeTypeManager->getNodeType('Neos.MarketPlace:Maintainer'));
                $this->setNodeProperties($node, $data);
            } else {
                $this->updateNodeProperties($node, $data);
            }
        }
    }

    /**
     * @throws NodeTypeNotFoundException
     * @throws \JsonException
     * @throws \Neos\Eel\Exception
     * @throws NodeException
     */
    protected function createOrUpdateVersions(Package $package, NodeInterface $node): void
    {
        $upstreamVersions = array_map(static function ($version) {
            return Slug::create($version);
        }, array_keys($package->getVersions()));
        $versionStorage = $node->getNode('versions');
        /** @var TraversableNodeInterface[] $versions */
        /** @noinspection PhpUndefinedMethodInspection */
        $versions = (new FlowQuery([$versionStorage]))->children('[instanceof Neos.MarketPlace:Version]');
        foreach ($versions as $version) {
            if (in_array($version->getNodeName(), $upstreamVersions)) {
                continue;
            }
            $version->remove();
        }

        foreach ($package->getVersions() as $version) {
            $versionStability = VersionNumber::isVersionStable($version->getVersionNormalized());
            $stabilityLevel = VersionNumber::getStabilityLevel($version->getVersionNormalized());
            $versionNormalized = VersionNumber::toInteger($version->getVersionNormalized());

            $name = Slug::create($version->getVersion());
            $node = $versionStorage->getNode($name);
            $data = [
                'version' => $version->getVersion(),
                'description' => $version->getDescription(),
                'keywords' => $this->arrayToStringCaster($version->getKeywords()),
                'homepage' => $version->getHomepage(),
                'versionNormalized' => $versionNormalized,
                'stability' => $versionStability,
                'stabilityLevel' => $stabilityLevel,
                'license' => $this->arrayToStringCaster($version->getLicense()),
                'type' => $version->getType(),
                'time' => \DateTime::createFromFormat(self::DATE_FORMAT, $version->getTime()),
                'provide' => $this->arrayToJsonCaster($version->getProvide()),
                'bin' => $this->arrayToJsonCaster($version->getBin()),
                'require' => $this->arrayToJsonCaster($version->getRequire()),
                'requireDev' => $this->arrayToJsonCaster($version->getRequireDev()),
                'suggest' => $this->arrayToJsonCaster($version->getSuggest()),
                'conflict' => $this->arrayToJsonCaster($version->getConflict()),
                'replace' => $this->arrayToJsonCaster($version->getReplace()),
            ];
            switch ($stabilityLevel) {
                case 'stable':
                    $nodeType = $this->nodeTypeManager->getNodeType('Neos.MarketPlace:ReleasedVersion');
                break;
                case 'dev':
                    $nodeType = $this->nodeTypeManager->getNodeType('Neos.MarketPlace:DevelopmentVersion');
                break;
                default:
                    $nodeType = $this->nodeTypeManager->getNodeType('Neos.MarketPlace:PrereleasedVersion');
            }
            if ($node === null) {
                $node = $versionStorage->createNode($name, $nodeType);
                $this->setNodeProperties($node, $data);
            } else {
                if ($node->getNodeType()->getName() !== $nodeType->getName()) {
                    $node->setNodeType($nodeType);
                }
                $this->updateNodeProperties($node, $data);
            }

            if ($version->getSource()) {
                $source = $node->getNode('source');
                $this->updateNodeProperties($source, [
                    'type' => $version->getSource()->getType(),
                    'reference' => $version->getSource()->getReference(),
                    'url' => $version->getSource()->getUrl(),
                ]);
            }

            if ($version->getDist()) {
                $dist = $node->getNode('dist');
                $this->updateNodeProperties($dist, [
                    'type' => $version->getDist()->getType(),
                    'reference' => $version->getDist()->getReference(),
                    'url' => $version->getDist()->getUrl(),
                    'shasum' => $version->getDist()->getShasum(),
                ]);
            }
        }
    }

    /**
     * @throws NodeException
     * @throws \Neos\Eel\Exception
     */
    protected function getPackageLastActivity(NodeInterface $packageNode): void
    {
        /** @var NodeInterface[] $versions */
        $versions = $packageNode->findNamedChildNode(NodeName::fromString('versions'))->findChildNodes();

        $sortedVersions = [];
        foreach ($versions as $version) {
            $lastActivity = $version->getProperty('time');
            if (!$lastActivity instanceof \DateTime) {
                continue;
            }
            $sortedVersions[$version->getProperty('time')->getTimestamp()] = $version;
        }
        krsort($sortedVersions);
        /** @var NodeInterface $lastActiveVersion */
        $lastActiveVersion = reset($sortedVersions);

        $packageNode->setProperty('lastActivity', $lastActiveVersion->getProperty('time'));
        $lastVersion = $this->packageVersion->extractLastVersion($packageNode);
        $packageNode->setProperty('lastVersion', $lastVersion);
    }

    /**
     * @throws NodeException
     */
    protected function getVendorLastActivity(NodeInterface $vendorNode): void
    {
        $packages = $vendorNode->getChildNodes('Neos.MarketPlace:Package');

        $sortedPackages = [];
        foreach ($packages as $packageNode) {
            $lastActivity = $packageNode->getProperty('lastActivity');
            if (!$lastActivity instanceof \DateTime) {
                continue;
            }
            $sortedPackages[$lastActivity->getTimestamp()] = $packageNode;
        }
        krsort($sortedPackages);
        $lastActivePackage = reset($sortedPackages);

        $vendorNode->setProperty('lastActivity', $lastActivePackage->getProperty('lastActivity'));
    }

    protected function setNodeProperties(NodeInterface $node, array $data): void
    {
        foreach ($data as $propertyName => $propertyValue) {
            $node->setProperty($propertyName, $propertyValue);
        }
    }

    protected function updateNodeProperties(NodeInterface $node, array $data): void
    {
        foreach ($data as $propertyName => $propertyValue) {
            $this->updateNodeProperty($node, $propertyName, $propertyValue);
        }
    }

    protected function updateNodeProperty(NodeInterface $node, string $propertyName, $propertyValue): void
    {
        if (isset($node->getProperties()[$propertyName])) {
            if ($propertyValue instanceof \DateTime) {
                if ($node->getProperties()[$propertyName]->getTimestamp() === $propertyValue->getTimestamp()) {
                    return;
                }
            } elseif ($node->getProperties()[$propertyName] === $propertyValue) {
                return;
            }
        }
        $node->setProperty($propertyName, $propertyValue);
    }

    protected function arrayToStringCaster(?array $value): string
    {
        $value = $value ?: [];
        return implode(', ', $value);
    }

    /**
     * @throws \JsonException
     */
    protected function arrayToJsonCaster(?array $value): ?string
    {
        return $value ? json_encode($value, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) : null;
    }

    /**
     * Signals that a node was abandoned.
     *
     * @Flow\Signal
     */
    protected function emitPackageAbandoned(NodeInterface $node): void
    {
    }
}
