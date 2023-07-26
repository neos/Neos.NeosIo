<?php
declare(strict_types=1);

namespace Neos\NeosIo\ContentRepository\Transformations;

use Neos\ContentRepository\Domain\Model\NodeData;
use Neos\ContentRepository\Migration\Transformations\AbstractTransformation;

/**
 * Rename a given property and replace its value based on the previous value.
 */
class RenameAndUpdatePropertyTransformation extends AbstractTransformation
{
    protected string $oldPropertyName;
    protected string $newPropertyName;
    protected mixed $oldValue;
    protected mixed $newValue;

    public function setFrom(string $oldPropertyName): void
    {
        $this->oldPropertyName = $oldPropertyName;
    }

    public function setTo(string $newPropertyName): void
    {
        $this->newPropertyName = $newPropertyName;
    }

    public function setOldValue(mixed $oldValue): void
    {
        $this->oldValue = $oldValue;
    }

    public function setNewValue(mixed $newValue): void
    {
        $this->newValue = $newValue;
    }

    /**
     * Returns true if the given node has a property with the name to work on
     * and does not yet have a property with the name to rename that property to.
     */
    public function isTransformable(NodeData $node): bool
    {
        return ($node->hasProperty($this->oldPropertyName) && !$node->hasProperty($this->newPropertyName));
    }

    /**
     * Renames the configured property to the new name if it's value matches, if not it is removed
     */
    public function execute(NodeData $node): void
    {
        $oldPropertyValue = $node->getProperty($this->oldPropertyName);
        if ($oldPropertyValue === $this->oldValue) {
            $node->setProperty($this->newPropertyName, $this->newValue);
        }
        $node->removeProperty($this->oldPropertyName);
    }
}
