<?php
declare(strict_types=1);

namespace Neos\NeosIo\ContentRepository\Transformations;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;
use Neos\Neos\Controller\CreateContentContextTrait;

// TODO 9.0 migration: You need to convert your AbstractTransformation to an implementation of Neos\ContentRepository\NodeMigration\Transformation\TransformationFactoryInterface
class FlattenCollectionsTransformation
{

    public function isTransformable(NodeData $node): bool
    {
        $numberOfChildNodes = $node->getNumberOfChildNodes('Neos.Neos:ContentCollection', $node->getWorkspace(), $node->getDimensions());
        return ($numberOfChildNodes === 1);
    }

    public function execute(NodeData $node): void
    {
        $contentContext = $this->createContentContext($node->getWorkspace()->getName(), $node->getDimensionValues());
        $parentNode = $contentContext->getNodeByIdentifier($node->getIdentifier());
        if (!$parentNode) {
            return;
        }

        $contentCollections = $parentNode->getChildNodes('Neos.Neos:ContentCollection');

        foreach ($contentCollections as $contentCollection) {
            if ($contentCollection->hasChildNodes() && $contentCollection->getNodeType()->getName() === 'Neos.Neos:ContentCollection') {
                $this->moveChildNodesToParent($contentCollection->getChildNodes(), $parentNode);
            }
        }
    }

    /**
     * @param \Neos\ContentRepository\Core\Projection\ContentGraph\Node[] $children
     */
    protected function moveChildNodesToParent(array $children, \Neos\ContentRepository\Core\Projection\ContentGraph\Node $parentNode): void
    {
        foreach ($children as $childNode) {
            if ($childNode instanceof \Neos\ContentRepository\Core\Projection\ContentGraph\Node) {
                // TODO 9.0 migration: !! Node::moveInto() is not supported by the new CR. Use the "MoveNodeAggregate" command to move a node.

                $childNode->moveInto($parentNode);
            }
        }
    }
}
