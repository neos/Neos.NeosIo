<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Tests\Unit\Utility;

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
use Neos\MarketPlace\Utility\PropertyDiff;
use PHPUnit\Framework\TestCase;

class PropertyDiffTest extends TestCase
{
    public function testAllPropertiesAreChangedIfNothingIsStoredYet(): void
    {
        $changed = PropertyDiff::changedProperties(
            SerializedPropertyValues::createEmpty(),
            ['title' => 'neos/neos', 'favers' => 42]
        );

        self::assertSame(['title' => 'neos/neos', 'favers' => 42], $changed);
    }

    public function testNullValuesForUnknownPropertiesAreIgnored(): void
    {
        $changed = PropertyDiff::changedProperties(
            SerializedPropertyValues::createEmpty(),
            ['sourceUrl' => null]
        );

        self::assertSame([], $changed);
    }

    public function testUnchangedStringValuesAreNotWritten(): void
    {
        $changed = PropertyDiff::changedProperties(
            $this->storedProperties(['title' => ['type' => 'string', 'value' => 'neos/neos']]),
            ['title' => 'neos/neos']
        );

        self::assertSame([], $changed);
    }

    public function testChangedStringValuesAreWritten(): void
    {
        $changed = PropertyDiff::changedProperties(
            $this->storedProperties(['title' => ['type' => 'string', 'value' => 'neos/neos']]),
            ['title' => 'neos/flow']
        );

        self::assertSame(['title' => 'neos/flow'], $changed);
    }

    public function testUnchangedIntegerValuesAreNotWritten(): void
    {
        $changed = PropertyDiff::changedProperties(
            $this->storedProperties(['favers' => ['type' => 'integer', 'value' => 42]]),
            ['favers' => 42]
        );

        self::assertSame([], $changed);
    }

    public function testChangedIntegerValuesAreWritten(): void
    {
        $changed = PropertyDiff::changedProperties(
            $this->storedProperties(['favers' => ['type' => 'integer', 'value' => 42]]),
            ['favers' => 43]
        );

        self::assertSame(['favers' => 43], $changed);
    }

    public function testUnchangedBooleanValuesAreNotWritten(): void
    {
        $changed = PropertyDiff::changedProperties(
            $this->storedProperties(['stability' => ['type' => 'boolean', 'value' => true]]),
            ['stability' => true]
        );

        self::assertSame([], $changed);
    }

    public function testUnchangedDateTimeValuesAreNotWritten(): void
    {
        $changed = PropertyDiff::changedProperties(
            $this->storedProperties(['time' => ['type' => 'DateTime', 'value' => '2026-04-01T19:42:26+00:00']]),
            ['time' => \DateTime::createFromFormat('Y-m-d\TH:i:sO', '2026-04-01T19:42:26+00:00')]
        );

        self::assertSame([], $changed);
    }

    public function testChangedDateTimeValuesAreWrittenWithTheOriginalValue(): void
    {
        $dateTime = \DateTime::createFromFormat('Y-m-d\TH:i:sO', '2026-05-02T08:00:00+00:00');
        $changed = PropertyDiff::changedProperties(
            $this->storedProperties(['time' => ['type' => 'DateTime', 'value' => '2026-04-01T19:42:26+00:00']]),
            ['time' => $dateTime]
        );

        self::assertSame(['time' => $dateTime], $changed);
    }

    public function testOnlyChangedPropertiesAreWritten(): void
    {
        $changed = PropertyDiff::changedProperties(
            $this->storedProperties([
                'title' => ['type' => 'string', 'value' => 'neos/neos'],
                'favers' => ['type' => 'integer', 'value' => 42],
                'downloadTotal' => ['type' => 'integer', 'value' => 100]
            ]),
            ['title' => 'neos/neos', 'favers' => 43, 'downloadTotal' => 100]
        );

        self::assertSame(['favers' => 43], $changed);
    }

    public function testNullValuesUnsetExistingProperties(): void
    {
        $changed = PropertyDiff::changedProperties(
            $this->storedProperties(['sourceUrl' => ['type' => 'string', 'value' => 'https://example.com']]),
            ['sourceUrl' => null]
        );

        self::assertSame(['sourceUrl' => null], $changed);
    }

    /**
     * @param array<string, array{type: string, value: mixed}> $properties
     */
    private function storedProperties(array $properties): SerializedPropertyValues
    {
        return SerializedPropertyValues::fromArray($properties);
    }
}
