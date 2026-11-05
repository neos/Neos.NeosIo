<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Utility;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;

/**
 * Compares property values with the ones that are already stored on a node.
 *
 * The content repository does not diff property values on its own, every write results in an event.
 * This helper allows to only write the properties that actually changed.
 */
final class PropertyDiff
{
    /**
     * Returns all properties that have to be written, because their value differs from the stored one.
     *
     * @param array<string, mixed> $properties
     * @return array<string, mixed> The properties that need to be written
     */
    public static function changedProperties(SerializedPropertyValues $storedProperties, array $properties): array
    {
        $changedProperties = [];
        foreach ($properties as $propertyName => $propertyValue) {
            if (!$storedProperties->propertyExists($propertyName)) {
                if ($propertyValue === null) {
                    // There is nothing to unset
                    continue;
                }
                $changedProperties[$propertyName] = $propertyValue;
                continue;
            }
            $serializedPropertyValue = $propertyValue instanceof \DateTimeInterface
                ? $propertyValue->format(\DateTimeInterface::ATOM)
                : $propertyValue;
            if ($storedProperties->getProperty($propertyName)?->value === $serializedPropertyValue) {
                continue;
            }
            $changedProperties[$propertyName] = $propertyValue;
        }
        return $changedProperties;
    }
}
