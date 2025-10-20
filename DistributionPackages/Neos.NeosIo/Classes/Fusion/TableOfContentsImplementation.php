<?php
declare(strict_types=1);

namespace Neos\NeosIo\Fusion;

use Neos\ContentRepository\Core\NodeType\NodeType;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Neos\Neos\Fusion\AbstractMenuItemsImplementation;
use Neos\Neos\Fusion\MenuItem;

class TableOfContentsImplementation extends AbstractMenuItemsImplementation
{

    #[Flow\Inject]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    /**
     * Runtime cache for the node type criteria to be applied
     */
    protected ?NodeTypeCriteria $nodeTypeCriteria = null;

    protected function getNodeTypeCriteria(): NodeTypeCriteria
    {
        if (!$this->nodeTypeCriteria) {
            $this->nodeTypeCriteria = NodeTypeCriteria::fromFilterString($this->getFilter());
        }
        return $this->nodeTypeCriteria;
    }

    /**
     * NodeType filter for potential nodes appearing in the menu
     */
    protected function getFilter(): string
    {
        return $this->fusionValue('filter');
    }

    /**
     * NodeType filter for nodes to be included in the menu
     */
    protected function getItemFilter(): string
    {
        return $this->fusionValue('itemFilter');
    }

    protected function buildItems(): array
    {
        $node = $this->getCurrentNode();
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        $childSubtree = $subgraph->findSubtree(
            $node->aggregateId,
            FindSubtreeFilter::create(
                nodeTypes: $this->getNodeTypeCriteria(),
                maximumLevels: 20
            )
        );
        if (!$childSubtree) {
            return [];
        }
        return $this->createHierarchy($this->buildMenuItemsFromSubtree($childSubtree));
    }

    /**
     * Creates a flat list of menu items that match the item filter.
     * The menu levels are determined from the heading levels in the node properties.
     *
     * @param MenuItem[] $items
     * @return MenuItem[]
     */
    protected function buildMenuItemsFromSubtree(Subtree $subtree, array &$items = []): array
    {
        foreach ($subtree->children as $childSubtree) {
            $this->buildMenuItemsFromSubtree($childSubtree, $items);
        }

        $node = $subtree->node;
        $nodeType = $this->getNodeType($node);
        if (!$nodeType?->isOfType($this->getItemFilter())) {
            return $items;
        }

        $sectionUri = $this->buildUri($node);
        if (!$sectionUri) {
            return $items;
        }

        [$label, $level] = $this->getLabelAndLevel($node);
        $items[] = new MenuItem(
            $node,
            null,
            $label,
            $level,
            [],
            '#' . $sectionUri,
        );
        return $items;
    }

    protected function getNodeType(Node $node): ?NodeType
    {
        $nodeTypeManager = $this->contentRepositoryRegistry->get($node->contentRepositoryId)->getNodeTypeManager();
        return $nodeTypeManager->getNodeType($node->nodeTypeName);
    }

    /**
     * @return array{0: string, 1: int}
     */
    protected function getLabelAndLevel(Node $node): array
    {
        $label = '';
        $level = 1;
        $propertyNames = $this->getLabelPropertyNames();
        foreach ($propertyNames as $propertyName) {
            if ($node->hasProperty($propertyName) && !empty($node->getProperty($propertyName))) {
                $label = $node->getProperty($propertyName);
                break;
            }
        }
        if (preg_match('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/', $label, $matches)) {
            $level = (int)$matches[1];
            $label = strip_tags($matches[2]);
        } else {
            $label = $this->nodeLabelGenerator->getLabel($node);
        }
        return [$label, $level];
    }

    /**
     * @return string[]
     */
    protected function getLabelPropertyNames(): array
    {
        return $this->fusionValue('labelPropertyNames');
    }

    /**
     * Iterates through the flat list of menu items and creates a hierarchy based on their menu levels.
     *
     * @param MenuItem[] $items
     * @return MenuItem[]
     */
    protected function createHierarchy(array $items): array
    {
        $parentLevel = 0;
        $parentIndex = 0;
        $childrenToMerge = [];
        foreach ($items as $index => $item) {
            if ($index === 0) {
                $parentLevel = $item->menuLevel;
                continue;
            }

            if ($item->menuLevel > $parentLevel) {
                $childrenToMerge[]= $item;
                unset($items[$index]);
                continue;
            }

            // Merge collected children into the parent item and recursively create hierarchy
            if ($childrenToMerge) {
                $parentItem = $items[$parentIndex];
                $items[$parentIndex] = new MenuItem(
                    $parentItem->node,
                    $parentItem->state,
                    $parentItem->label,
                    $parentItem->menuLevel,
                    $this->createHierarchy($childrenToMerge),
                    $parentItem->uri,
                );
                $childrenToMerge = [];
            }

            $parentIndex = $index;
            if ($item->menuLevel < $parentLevel) {
                $parentLevel = $item->menuLevel;
            }
        }
        return array_values($items);
    }
}
