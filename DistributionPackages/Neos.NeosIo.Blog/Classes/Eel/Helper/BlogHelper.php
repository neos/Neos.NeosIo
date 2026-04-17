<?php

namespace Neos\NeosIo\Blog\Eel\Helper;

use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

class BlogHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @param Node $site
     * @param int $limit
     * @param array<Node> $filterTags
     * @param string $sortBy
     * @param string $sortDirection
     * @return Nodes The found blog posts
     */
    public function getBlogPost(Node $site, int $limit, array $filterTags = [], string $sortBy = 'datePublished', string $sortDirection = 'DESC'): Nodes
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($site);

        $orderingDirection = strtoupper($sortDirection) === 'DESC'
            ? OrderingDirection::DESCENDING
            : OrderingDirection::ASCENDING;
        $ordering = Ordering::byProperty(PropertyName::fromString($sortBy), $orderingDirection);

        $nodeTypeCriteria = NodeTypeCriteria::createWithAllowedNodeTypeNames(
            NodeTypeNames::fromStringArray(['Neos.NeosIo:Post'])
        );

        if (empty($filterTags)) {
            return $subgraph->findDescendantNodes(
                $site->aggregateId,
                FindDescendantNodesFilter::create(
                    nodeTypes: $nodeTypeCriteria,
                    ordering: $ordering,
                    pagination: Pagination::fromLimitAndOffset($limit, 0),
                )
            );
        }

        // Collect the aggregate IDs of all posts that reference at least one of the filter tags
        $matchingPostIds = [];
        foreach ($filterTags as $filterTag) {
            $backReferences = $subgraph->findBackReferences(
                $filterTag->aggregateId,
                FindBackReferencesFilter::create(
                    nodeTypes: $nodeTypeCriteria,
                    referenceName: 'tags',
                )
            );
            foreach ($backReferences as $reference) {
                $matchingPostIds[$reference->node->aggregateId->value] = true;
            }
        }

        // Fetch all posts sorted, then filter by matched IDs and apply limit
        $allPosts = $subgraph->findDescendantNodes(
            $site->aggregateId,
            FindDescendantNodesFilter::create(
                nodeTypes: $nodeTypeCriteria,
                ordering: $ordering,
            )
        );

        $filteredPosts = $allPosts->filter(
            fn(Node $post) => isset($matchingPostIds[$post->aggregateId->value])
        );

        return Nodes::fromArray(
            array_slice($filteredPosts->map(fn(Node $node) => $node), 0, $limit)
        );
    }

    public function allowsCallOfMethod($methodName): true
    {
        return true;
    }
}
