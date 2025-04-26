<?php
namespace Neos\NeosIo\ContentRepository\Transformations;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;

// TODO 9.0 migration: You need to convert your AbstractTransformation to an implementation of Neos\ContentRepository\NodeMigration\Transformation\TransformationFactoryInterface
class HeadlineTransformation
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
        $headlineTag = substr($node->getProperty('type'), 0, 2);
        $node->setProperty('title', sprintf('<%1$s>%2$s</%1$s>', $headlineTag, $node->getProperty('title')));
        return $node;
    }
}
