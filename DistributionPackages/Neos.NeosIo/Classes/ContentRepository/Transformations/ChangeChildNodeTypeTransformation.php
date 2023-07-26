<?php
declare(strict_types=1);

namespace Neos\NeosIo\ContentRepository\Transformations;

use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\Neos\Controller\CreateContentContextTrait;

class ChangeChildNodeTypeTransformation extends AbstractTransformation
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    protected string $newType;

    public function setNewType(string $newType): void
    {
        $this->newType = $newType;
    }

    /**
     * If the given node has the property this transformation should work on, this
     * returns true if the given NodeType is registered with the NodeTypeManager and is not abstract.
     */
    public function isTransformable(NodeData $node): bool
    {
        return $this->nodeTypeManager->hasNodeType($this->newType)
            && !$this->nodeTypeManager->getNodeType($this->newType)->isAbstract();
    }

    /**
     * Change the Node Type on the given node.
     */
    public function execute(NodeData $node): void
    {
        $contentContext = $this->createContentContext($node->getWorkspace()->getName());
        $parentNode = $contentContext->getNodeByIdentifier($node->getIdentifier());
        if (!$parentNode) {
            return;
        }
        $nodeType = $this->nodeTypeManager->getNodeType($this->newType);
        foreach ($parentNode->getChildNodes() as $childNode) {
            $childNode->setNodeType($nodeType);
        }
    }
}
