<?php
declare(strict_types=1);

namespace Neos\NeosIo\ContentRepository\Transformations;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;
use Neos\Neos\Controller\CreateContentContextTrait;

/**
 * Move content of elements with a single content collection to the parent to cleanup the content tree
 */
class FlattenCollectionsTransformation extends AbstractTransformation
{
    use CreateContentContextTrait;

    public function isTransformable(NodeData $node): bool
    {
        $numberOfChildNodes = $node->getNumberOfChildNodes('Neos.Neos:ContentCollection', $node->getWorkspace(), $node->getDimensions());
        return ($numberOfChildNodes === 1);
    }

    public function execute(NodeData $node): void
    {
        $contentContext = $this->createContentContext('live');
        $parentNode = $contentContext->getNodeByIdentifier($node->getIdentifier());
        if (!$parentNode) {
            return;
        }

        $contentCollections = $parentNode->getChildNodes('Neos.Neos:ContentCollection');

        foreach ($contentCollections as $contentCollection) {
            if ($contentCollection->hasChildNodes() && $contentCollection->getNodeType()->getName() === 'Neos.Neos:ContentCollection') {
                $this->moveChildNodesToSlide($contentCollection->getChildNodes(), $parentNode);
            }
        }
    }

    /**
     * @param NodeInterface[] $children
     */
    protected function moveChildNodesToSlide(array $children, NodeInterface $parentNode): void
    {
        foreach ($children as $childNode) {
            if ($childNode instanceof NodeInterface) {
                $childNode->moveInto($parentNode);
            }
        }
    }
}
