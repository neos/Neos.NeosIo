<?php
namespace Neos\NeosIo\ContentRepository\Transformations;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;

// TODO 9.0 migration: You need to convert your AbstractTransformation to an implementation of Neos\ContentRepository\NodeMigration\Transformation\TransformationFactoryInterface
class SectionTransformation
{
    /**
     * @param NodeData $node
     * @return boolean
     */
    public function isTransformable(NodeData $node)
    {
        return true;
    }

    /**
     * Change the property on the given node.
     *
     * @param NodeData $node
     * @return NodeData
     */
    public function execute(NodeData $node)
    {
        $node = $this->nodeFactory->createFromNodeData($node, $this->nodeFactory->createContextMatchingNodeData($node));
        $column0 = $node->getPrimaryChildNode();
        $column0->setName('contents');
        return $node;
    }
}
