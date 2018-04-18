<?php
namespace Neos\NeosIo\Fusion;

use Neos\Neos\Fusion\MenuImplementation;

/**
 * 2nd-level menu for the documentation pages. Pulls its sub-items from the headlines on the current page.
 */
class DocumentationPageSubMenuImplementation extends MenuImplementation
{

    /**
     * Exactly similar to the parent method, except for it calling buildMenuItemRecursiveWithDefinedChildnodes()
     * instead of the default buildMenuItemRecursive()
     *
     * @param array $menuLevelCollection
     * @return array
     */
    protected function buildMenuLevelRecursive(array $menuLevelCollection)
    {
        $items = array();
        foreach ($menuLevelCollection as $currentNode) {
            $item = $this->buildMenuItemRecursiveWithDefinedChildnodes($currentNode);
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
     * @return array
     */
    protected function buildMenuItemRecursiveWithDefinedChildnodes(array $currentNodeAndChildNodes)
    {
        $currentNode = $currentNodeAndChildNodes['node'];
        $label = $currentNodeAndChildNodes['label'];
        $anchorName = $currentNodeAndChildNodes['anchorName'] ?? '';
        $subItems = $currentNodeAndChildNodes['subItems'] ?? [];

        if ($this->isNodeHidden($currentNode)) {
            return null;
        }

        $item = array(
            'node' => $currentNode,
            'state' => self::STATE_NORMAL,
            'anchorName' => $anchorName,
            'label' => $label,
            'menuLevel' => $this->currentLevel
        );

        $item['state'] = $this->calculateItemState($currentNode);
        if (count($subItems) > 0) {
            $this->currentLevel++;
            $item['subItems'] = $this->buildMenuLevelRecursive($subItems);
            $this->currentLevel--;
        }

        return $item;
    }
}
