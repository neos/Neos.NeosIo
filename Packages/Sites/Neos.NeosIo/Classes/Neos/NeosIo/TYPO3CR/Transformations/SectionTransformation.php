<?php
namespace Neos\NeosIo\TYPO3CR\Transformations;

use Neos\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Migration\Transformations\AbstractTransformation;

class SectionTransformation extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

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
