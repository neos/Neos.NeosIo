<?php
namespace Neos\NeosIo\TYPO3CR\Transformations;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Migration\Transformations\AbstractTransformation;

class HeadlineTransformation extends AbstractTransformation
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
