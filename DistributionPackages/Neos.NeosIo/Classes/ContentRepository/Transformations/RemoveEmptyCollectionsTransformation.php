<?php
declare(strict_types=1);

namespace Neos\NeosIo\ContentRepository\Transformations;

use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;
use Neos\Flow\Annotations as Flow;

/**
 * Remove empty content collections that are leftover after the FlattenCollectionsTransformation
 */
class RemoveEmptyCollectionsTransformation extends AbstractTransformation
{
    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    public function isTransformable(NodeData $node): bool
    {
        $numberOfChildCollections = $node->getNumberOfChildNodes(
            'Neos.Neos:ContentCollection',
            $node->getWorkspace(),
            $node->getDimensionValues()
        );
        return ($numberOfChildCollections > 0);
    }

    public function execute(NodeData $node): void
    {
        $parentNode = $this->nodeFactory->createFromNodeData($node, $this->nodeFactory->createContextMatchingNodeData($node));
        if (!$parentNode) {
            return;
        }
        $contentCollections = $parentNode->getChildNodes('Neos.Neos:ContentCollection');

        foreach ($contentCollections as $contentCollection) {
            if ($contentCollection->hasChildNodes() === false && $contentCollection->getNodeType()->getName() === 'Neos.Neos:ContentCollection') {
                try {
                    $contentCollection->remove();
                } catch (\Exception $exception) {
                    \Neos\Flow\var_dump([
                        'dimensions' => json_encode($parentNode->getDimensions()),
                        'nodetype' => $parentNode->getNodeType()->getName(),
                        'exception' => $exception->getMessage(),
                    ], 'Could not remove node at ' . $parentNode->getContextPath());
                }
            }
        }
    }
}
