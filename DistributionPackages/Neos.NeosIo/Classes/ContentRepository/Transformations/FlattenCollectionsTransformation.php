<?php
declare(strict_types=1);

namespace Neos\NeosIo\ContentRepository\Transformations;

use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;
use Neos\Flow\Annotations as Flow;

/**
 * Move content of elements with a single content collection to the parent to cleanup the content tree
 */
class FlattenCollectionsTransformation extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    public function isTransformable(NodeData $node): bool
    {
        $numberOfChildNodes = $node->getNumberOfChildNodes(
            'Neos.Neos:ContentCollection',
            $node->getWorkspace(),
            $node->getDimensionValues()
        );
        return ($numberOfChildNodes === 1);
    }

    public function execute(NodeData $node): void
    {
        $parentNode = $this->nodeFactory->createFromNodeData($node, $this->nodeFactory->createContextMatchingNodeData($node));
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
     * @param NodeInterface[] $children
     */
    protected function moveChildNodesToParent(array $children, NodeInterface $parentNode): void
    {
        foreach ($children as $childNode) {
            if ($childNode instanceof NodeInterface) {
                try {
                    $childNode->moveInto($parentNode);
                } catch (\Exception $exception) {
                    \Neos\Flow\var_dump([
                        'contextPath' => $parentNode->getContextPath(),
                        'workspace' => $parentNode->getWorkspace()->getName(),
                        'dimensions' => json_encode($parentNode->getDimensions()),
                        'nodetype' => $parentNode->getNodeType()->getName(),
                        'exception' => $exception->getMessage(),
                    ], 'Could not move node ' . $parentNode->getIdentifier());
                }
            }
        }
    }
}
