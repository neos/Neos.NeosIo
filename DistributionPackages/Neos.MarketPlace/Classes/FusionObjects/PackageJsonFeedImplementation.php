<?php
declare(strict_types=1);

namespace Neos\MarketPlace\FusionObjects;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\ServerRequest;
use Neos\ContentRepository\Core\Feature\Security\Exception\AccessDenied;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\MarketPlace\Domain\Dto\VersionFeedItem;
use Neos\MarketPlace\Domain\Dto\VersionFeedResult;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\Options;
use Psr\Http\Message\UriInterface;

/**
 * Package TypoScript Implementation
 *
 * @api
 */
class PackageJsonFeedImplementation extends AbstractFusionObject
{
    #[Flow\Inject(lazy: false)]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject(lazy: false)]
    protected NodeUriBuilderFactory $nodeUriBuilderFactory;

    public function evaluate(): string
    {
        $packageNodes = $this->getPackageNodes();
        try {
            $result = $this->buildVersionFeedResult(
                $packageNodes,
                $this->getFrom(),
                $this->getTo(),
                $this->getOnlyStable(),
                $this->getLimit(),
            );
            return json_encode($result, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            return '{"results": [], "error": "' . $e->getMessage() . '"}';
        }
    }

    /**
     * @return Node[]
     */
    private function getPackageNodes(): array
    {
        return $this->fusionValue('packageNodes');
    }

    /**
     * Builds a VersionFeedResult from package nodes with per-version filtering and sorting.
     *
     * Resolves latestVersion per package (highest stability, then latest time)
     * and formats lastActivity as ISO 8601.
     *
     * @param Node[] $packageNodes Array of Package nodes
     * @throws AccessDenied
     */
    public function buildVersionFeedResult(
        array               $packageNodes,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        bool                $onlyStable = true,
        int                 $limit = 50
    ): VersionFeedResult
    {
        $flatVersions = [];
        $contentRepository = null;

        foreach ($packageNodes as $packageNode) {
            if (!$contentRepository) {
                $contentRepository = $this->contentRepositoryRegistry->get($packageNode->contentRepositoryId);
            }
            $subgraph = $contentRepository->getContentSubgraph(
                $packageNode->workspaceName,
                $packageNode->dimensionSpacePoint
            );

            $versionsNode = $subgraph->findNodeByPath(
                NodePath::fromString('versions'),
                $packageNode->aggregateId
            );
            if (!$versionsNode) {
                continue;
            }

            $versionNodes = $subgraph->findChildNodes(
                $versionsNode->aggregateId,
                FindChildNodesFilter::create(
                    nodeTypes: NodeTypeCriteria::createWithAllowedNodeTypeNames(
                        NodeTypeNames::fromStringArray($onlyStable ? ['Neos.MarketPlace:ReleasedVersion'] : [
                            'Neos.MarketPlace:ReleasedVersion',
                            'Neos.MarketPlace:PrereleasedVersion',
                            'Neos.MarketPlace:DevelopmentVersion',
                        ])
                    ),
                    ordering: Ordering::byProperty(
                        PropertyName::fromString('time'),
                        OrderingDirection::DESCENDING
                    ),
                )
            );

            foreach ($versionNodes as $versionNode) {
                $versionTime = $versionNode->getProperty('time');
                if (!$versionTime instanceof \DateTimeInterface) {
                    continue;
                }

                if ($from !== null && $versionTime < $from) {
                    continue;
                }
                if ($to !== null && $versionTime > $to) {
                    continue;
                }
                $stable = $versionNode->getProperty('stability') ?? false;
                if ($onlyStable && !$stable) {
                    continue;
                }

                $repository = $packageNode->getProperty('repository');
                $version = $versionNode->getProperty('version');

                $flatVersions[] = new VersionFeedItem(
                    packageNode: $packageNode,
                    title: $packageNode->getProperty('title'),
                    description: $packageNode->getProperty('description'),
                    link: (string)$this->getPackageNodeUri($packageNode),
                    linkToRelease: is_string($repository) && !empty($repository) ? $repository . '/releases/tag/' . $version : '',
                    version: $version,
                    lastActivity: $versionTime,
                    stability: $stable,
                    stabilityLevel: $versionNode->getProperty('stabilityLevel'),
                    downloads: $packageNode->getProperty('downloadTotal') ?? 0,
                    stars: $packageNode->getProperty('favers') ?? 0,
                );
            }
        }

        usort($flatVersions, static function (VersionFeedItem $a, VersionFeedItem $b): int {
            return $b->lastActivity <=> $a->lastActivity;
        });

        return new VersionFeedResult(...array_slice($flatVersions, 0, $limit));
    }

    protected function getPackageNodeUri(Node $packageNode): UriInterface|null
    {

        $possibleRequest = $this->runtime->fusionGlobals->get('request');
        if ($possibleRequest instanceof ActionRequest) {
            $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest($possibleRequest);
        } else {
            $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest(ActionRequest::fromHttpRequest(ServerRequest::fromGlobals()));
        }
        try {
            return $nodeUriBuilder->uriFor(NodeAddress::fromNode($packageNode), Options::createForceAbsolute());
        } catch (NoMatchingRouteException) {
            return null;
        }
    }

    protected function getFrom(): ?\DateTimeInterface
    {
        return $this->fusionValue('from');
    }

    protected function getTo(): ?\DateTimeInterface
    {
        return $this->fusionValue('to');
    }

    protected function getLimit(): int
    {
        return $this->fusionValue('limit') ?? 50;
    }

    protected function getOnlyStable(): bool
    {
        return $this->fusionValue('onlyStable') ?? false;
    }
}
