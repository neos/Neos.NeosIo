<?php
namespace Neos\NeosIo\TYPO3CR\Transformations;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;
use TYPO3\TYPO3CR\Migration\Transformations\AbstractTransformation;

class ContainerTransformation extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

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
        $node = $this->nodeFactory->createFromNodeData($node, $this->contextFactory->create(array(
            'workspaceName' => $node->getWorkspace()->getName(),
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        )));
        if (!$node) {
            return;
        }
        $column0 = $node->getPrimaryChildNode();
        if (!$column0) {
            return;
        }
        foreach ($column0->getChildNodes() as $childNode) {
            $node = $childNode->copyAfter($node, NodePaths::generateRandomNodeName());
        }
        return $node;
    }
}
