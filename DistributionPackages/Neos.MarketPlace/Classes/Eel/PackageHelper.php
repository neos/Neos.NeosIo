<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Eel;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Composer\Package\Version\VersionParser;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\Security\Exception\AccessDenied;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * Helper for packages in the Neos.MarketPlace package.
 */
class PackageHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    protected ContentRepository $contentRepository;

    protected function initializeObject(): void
    {
        $this->contentRepository = $this->contentRepositoryRegistry->get(
            ContentRepositoryId::fromString('default')
        );
    }

    public function sanitiseVersionArgument(?string $version): ?string
    {
        $version = trim($version ?? '');

        if ($version === '') {
            return null;
        }

        // Ensure the version is a valid semantic version
        try {
            $parser = new VersionParser();
            $parser->normalize($version);
        } catch (\UnexpectedValueException) {
            // If the version is not valid, return the original version string
            return null;
        }

        return $version;
    }

    /**
     * Collects and flattens released versions from a list of Packagist packages, filters and sorts them.
     *
     * @param Node[] $packageNodes Array of Packagist\Api\Result\Package objects
     * @return array{ time: \DateTimeInterface, identifier: string, version: string, description: string, packageName: string, authors: string[], repository: string}[]
     * @throws AccessDenied
     */
    public function getReleasedVersions(array $packageNodes, \DateTimeInterface $dateFilter, int $limit = 10): array
    {
        $filteredReleasedVersions = array_reduce(
            $packageNodes,
            function (array $carry, Node $packageNode) use ($dateFilter, $limit): array {
                $subgraph = $this->contentRepository->getContentSubgraph(
                    $packageNode->workspaceName,
                    $packageNode->dimensionSpacePoint
                );

                // Collect authors
                $maintainers = $packageNode->getProperty('maintainers');
                $authorNames = explode(',', $maintainers);

                // Collect versions
                $versionsNode = $subgraph->findNodeByPath(
                    NodePath::fromString('versions'),
                    $packageNode->aggregateId
                );
                if (!$versionsNode) {
                    return $carry; // No versions node found, skip this package
                }

                // Get released versions, sorted by time DESC, and limited
                $releasedVersions = $subgraph->findChildNodes(
                    $versionsNode->aggregateId,
                    FindChildNodesFilter::create(
                        'Neos.MarketPlace:ReleasedVersion',
                        ordering: Ordering::byProperty(
                            PropertyName::fromString('time'),
                            OrderingDirection::DESCENDING
                        ),
                        pagination: Pagination::fromLimitAndOffset($limit, 0),
                    )
                );

                // Get package meta
                $packageName = $packageNode->getProperty('title');
                $repository = $packageNode->getProperty('repository');

                foreach ($releasedVersions as $versionNode) {
                    $versionDateTime = $versionNode->getProperty('time');
                    if (!$versionDateTime instanceof \DateTimeInterface) {
                        continue; // Skip if time is not a DateTime object
                    }
                    if ($versionDateTime > $dateFilter) {
                        $carry[] = [
                            'time' => $versionDateTime,
                            'identifier' => $versionNode->aggregateId->value,
                            'version' => $versionNode->getProperty('version'),
                            'description' => $versionNode->getProperty('description'),
                            'packageName' => $packageName,
                            'authors' => $authorNames,
                            'repository' => $repository,
                        ];
                    }
                }
                return $carry;
            },
            []
        );
        // Sort by time DESC
        usort($filteredReleasedVersions, static fn ($a, $b) => $b['time'] <=> $a['time']);
        return $filteredReleasedVersions;
    }

    /**
     * Collects all versions from a list of package nodes, flattens them into individual entries,
     * applies per-version filtering and sorting.
     *
     * Each entry contains both package-level and version-level data.
     *
     * @param Node[] $packageNodes Array of Package nodes
     * @return array{ packageNode: Node, title: string, description: ?string, repository: ?string, version: ?string, time: \DateTimeInterface, stability: ?bool, stabilityLevel: ?string, downloadTotal: ?int, favers: ?int}[]
     */
    public function getFlatPackageVersions(
        array $packageNodes,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        ?string $onlyStable = null,
        int $limit = 50
    ): array {
        $flatVersions = [];

        foreach ($packageNodes as $packageNode) {
            $subgraph = $this->contentRepository->getContentSubgraph(
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
                        NodeTypeNames::fromStringArray([
                            'Neos.MarketPlace:ReleasedVersion',
                            'Neos.MarketPlace:PrereleasedVersion',
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
                if ($onlyStable === 'true' && $versionNode->getProperty('stability') !== true) {
                    continue;
                }

                $flatVersions[] = [
                    'packageNode' => $packageNode,
                    'title' => $packageNode->getProperty('title'),
                    'description' => $packageNode->getProperty('description'),
                    'repository' => $packageNode->getProperty('repository'),
                    'version' => $versionNode->getProperty('version'),
                    'time' => $versionTime,
                    'stability' => $versionNode->getProperty('stability'),
                    'stabilityLevel' => $versionNode->getProperty('stabilityLevel'),
                    'downloadTotal' => $packageNode->getProperty('downloadTotal') ?? 0,
                    'favers' => $packageNode->getProperty('favers') ?? 0,
                ];
            }
        }

        usort($flatVersions, static function (array $a, array $b): int {
            return $b['time'] <=> $a['time'];
        });

        return array_slice($flatVersions, 0, $limit);
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
