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
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
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

    #[Flow\Inject]
    protected ElasticSearchQueryBuilder $elasticSearchQueryBuilder;

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
                $maintainers = $subgraph->findNodeByPath(
                    NodePath::fromString('maintainers'),
                    $packageNode->aggregateId
                );
                $authorNames = $maintainers ? $subgraph->findChildNodes(
                    $maintainers->aggregateId,
                    FindChildNodesFilter::create(
                        'Neos.MarketPlace:Maintainer',
                        ordering: Ordering::byProperty(
                            PropertyName::fromString('title'),
                            OrderingDirection::ASCENDING
                        )
                    )
                )->map(static fn (Node $author) => $author->getProperty('title')) : [];

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

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
