<?php
declare(strict_types=1);

namespace Neos\NeosIo\ContentRepository\Transformations;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;
use Neos\Neos\Controller\CreateContentContextTrait;

/**
 * Remove empty content collections that are leftover after the FlattenCollectionsTransformation
 */
class RemoveEmptyCollectionsTransformation extends AbstractTransformation
{
    use CreateContentContextTrait;

    public function isTransformable(NodeData $node): bool
    {
        $numberOfChildNodes = $node->getNumberOfChildNodes('Neos.Neos:ContentCollection', $node->getWorkspace(), $node->getDimensions());
        return ($numberOfChildNodes > 0);
    }

    public function execute(NodeData $node): void
    {
        $contentContext = $this->createContentContext('live', []);
        $parentNode = $contentContext->getNodeByIdentifier($node->getIdentifier());
        if (!$parentNode) {
            return;
        }
        $contentCollections = $parentNode->getChildNodes('Neos.Neos:ContentCollection');

        foreach ($contentCollections as $contentCollection) {
            if ($contentCollection->hasChildNodes() === false) {
                $contentCollection->remove();
            }
        }
    }
}
