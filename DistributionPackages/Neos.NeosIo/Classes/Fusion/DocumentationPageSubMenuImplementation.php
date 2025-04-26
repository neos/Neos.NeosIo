<?php
namespace Neos\NeosIo\Fusion;

use Neos\Neos\Fusion\MenuItem;
use Neos\Neos\Fusion\MenuItemsImplementation;

/**
 * 2nd-level menu for the documentation pages. Pulls its sub-items from the headlines on the current page.
 */
class DocumentationPageSubMenuImplementation extends MenuItemsImplementation
{

    protected function buildItems(): array
    {
        return $this->buildMenuLevelRecursive($this->getItemCollection(), 0);
    }

    /**
     * Exactly similar to the parent method, except for it calling buildMenuItemRecursiveWithDefinedChildnodes()
     * instead of the default buildMenuItemRecursive()
     *
     * @param array $menuLevelCollection
     * @return array
     */
    protected function buildMenuLevelRecursive(array $menuLevelCollection, int $level)
    {
        $items = array();
        foreach ($menuLevelCollection as $currentNode) {
            $item = $this->buildMenuItemRecursiveWithDefinedChildnodes($currentNode, $level);
            if ($item === null) {
                continue;
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Prepare the menu item with state and sub items if this isn't the last menu level.
     * !!! This operates on an array with the node itself and its children instead of a NodeInterface directly.
     *
     * @param array $currentNodeAndChildNodes An array of the form ['node' => NodeInterface, 'label' => string, 'anchorName' => string, 'subItems' => array]
     */
    protected function buildMenuItemRecursiveWithDefinedChildnodes(array $currentNodeAndChildNodes, int $level): ?MenuItem
    {
        $currentNode = $currentNodeAndChildNodes['node'];
        $label = $currentNodeAndChildNodes['label'];
        // TODO 9.0 migration: The new MenuItem doesn't allow to add additional information.
        $anchorName = $currentNodeAndChildNodes['anchorName'] ?? '';
        $linkAttributes = $currentNodeAndChildNodes['linkAttributes'] ?? '';
        $subItems = $currentNodeAndChildNodes['subItems'] ?? [];

        if ($this->isNodeHidden($currentNode)) {
            return null;
        }

        $children = [];
        if (count($subItems) > 0) {
            $children = $this->buildMenuLevelRecursive($subItems, $level + 1);
        }

        return new MenuItem(
            $currentNode,
            $this->calculateItemState($currentNode),
            $label,
            $level,
            $children,
            $this->buildUri($currentNode)
        );
    }
}
