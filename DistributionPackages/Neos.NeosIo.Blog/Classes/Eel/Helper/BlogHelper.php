<?php

namespace Neos\NeosIo\Blog\Eel\Helper;

use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\Ordering;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering\OrderingDirection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
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
    public function getPosts(Node $site, int $limit, array $filterTags = [], string $sortBy = 'datePublished', string $sortDirection = 'DESC'): Nodes
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($site);

        $orderingDirection = strtoupper($sortDirection) === 'DESC'
            ? OrderingDirection::DESCENDING
            : OrderingDirection::ASCENDING;
        $ordering = Ordering::byProperty(PropertyName::fromString($sortBy), $orderingDirection);

        $nodeTypeCriteria = NodeTypeCriteria::createWithAllowedNodeTypeNames(
            NodeTypeNames::fromStringArray(['Neos.NeosIo:Post'])
        );

        /**
         * WHY handle get post depending on tag count?:
         * - 0 tags: Just find all posts (simple descendant query)
         * - 1 tag: Find posts via backreferences of tag
         * - >1 tags: Performance hit! Find posts via backreferences of each tag, then sort & limit in PHP (because filtering by referenc(s) is not possible (yet?))
         */
        return match (count($filterTags)) {
            0 => $this->findAllPosts($subgraph, $site, $nodeTypeCriteria, $ordering, $limit),
            1 => $this->findPostsByTag($subgraph, $filterTags[0], $nodeTypeCriteria, $ordering, $limit),
            default => $this->findPostsByMultipleTags($subgraph, $filterTags, $nodeTypeCriteria, $orderingDirection, $sortBy, $limit),
        };
    }

    private function findAllPosts(
        ContentSubgraphInterface $subgraph,
        Node $site,
        NodeTypeCriteria $nodeTypeCriteria,
        Ordering $ordering,
        int $limit,
    ): Nodes {
        return $subgraph->findDescendantNodes(
            $site->aggregateId,
            FindDescendantNodesFilter::create(
                nodeTypes: $nodeTypeCriteria,
                ordering: $ordering,
                pagination: Pagination::fromLimitAndOffset($limit, 0),
            )
        );
    }

    private function findPostsByTag(
        ContentSubgraphInterface $subgraph,
        Node $tag,
        NodeTypeCriteria $nodeTypeCriteria,
        Ordering $ordering,
        int $limit,
    ): Nodes {
        $references = $subgraph->findBackReferences(
            $tag->aggregateId,
            FindBackReferencesFilter::create(
                nodeTypes: $nodeTypeCriteria,
                referenceName: 'tags',
                ordering: $ordering,
                pagination: Pagination::fromLimitAndOffset($limit, 0),
            )
        );

        return Nodes::fromArray(
            array_map(fn($reference) => $reference->node, iterator_to_array($references))
        );
    }

    /**
     * @param array<Node> $filterTags
     */
    private function findPostsByMultipleTags(
        ContentSubgraphInterface $subgraph,
        array $filterTags,
        NodeTypeCriteria $nodeTypeCriteria,
        OrderingDirection $orderingDirection,
        string $sortBy,
        int $limit,
    ): Nodes {
        // Collect matching post nodes from back-references (deduplicated by aggregate ID)
        $matchingPosts = [];
        foreach ($filterTags as $filterTag) {
            $backReferences = $subgraph->findBackReferences(
                $filterTag->aggregateId,
                FindBackReferencesFilter::create(
                    nodeTypes: $nodeTypeCriteria,
                    referenceName: 'tags',
                )
            );
            foreach ($backReferences as $reference) {
                $matchingPosts[$reference->node->aggregateId->value] = $reference->node;
            }
        }

        // Sort in PHP — because we need to
        $matchingPosts = array_values($matchingPosts);
        usort($matchingPosts, function (Node $a, Node $b) use ($sortBy, $orderingDirection) {
            $aVal = $a->getProperty($sortBy);
            $bVal = $b->getProperty($sortBy);
            $cmp = $aVal <=> $bVal;
            return $orderingDirection === OrderingDirection::DESCENDING ? -$cmp : $cmp;
        });

        return Nodes::fromArray(array_slice($matchingPosts, 0, $limit));
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
