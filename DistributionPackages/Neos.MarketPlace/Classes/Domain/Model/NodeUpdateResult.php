<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Domain\Model;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Describes the outcome of a node update.
 *
 * The content repository does not diff property values on its own, every write results in an event.
 * This result therefore allows the importer to distinguish between "the node was actually changed"
 * and "the values were already up to date".
 *
 * @api
 */
final readonly class NodeUpdateResult
{
    /**
     * @param string[] $changedProperties Names of the properties that were actually written
     */
    private function __construct(
        public bool $successful,
        public bool $changed,
        public array $changedProperties
    ) {
    }

    /**
     * @param string[] $changedProperties
     */
    public static function success(array $changedProperties = []): self
    {
        return new self(true, $changedProperties !== [], $changedProperties);
    }

    /**
     * A change that is not bound to a single property, for example a changed node type.
     */
    public static function structuralChange(): self
    {
        return new self(true, true, []);
    }

    public static function failure(): self
    {
        return new self(false, false, []);
    }

    public function hasChanged(): bool
    {
        return $this->changed;
    }

    /**
     * Whether the change affects anything besides the given properties.
     *
     * @param string[] $propertyNames
     */
    public function hasChangedOtherThan(array $propertyNames): bool
    {
        if (!$this->changed) {
            return false;
        }
        if ($this->changedProperties === []) {
            // a structural change always affects other properties
            return true;
        }
        return array_diff($this->changedProperties, $propertyNames) !== [];
    }
}
