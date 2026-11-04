<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Tests\Unit\Domain\Model;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\MarketPlace\Domain\Model\NodeUpdateResult;
use PHPUnit\Framework\TestCase;

class NodeUpdateResultTest extends TestCase
{
    public function testSuccessWithoutChanges(): void
    {
        $result = NodeUpdateResult::success();

        self::assertTrue($result->successful);
        self::assertFalse($result->hasChanged());
        self::assertFalse($result->hasChangedOtherThan(['favers']));
        self::assertSame([], $result->changedProperties);
    }

    public function testSuccessWithChanges(): void
    {
        $result = NodeUpdateResult::success(['title']);

        self::assertTrue($result->successful);
        self::assertTrue($result->hasChanged());
        self::assertTrue($result->hasChangedOtherThan(['favers']));
        self::assertFalse($result->hasChangedOtherThan(['title']));
    }

    public function testOnlyTheGivenPropertiesChanged(): void
    {
        $result = NodeUpdateResult::success(['favers', 'downloadTotal']);

        self::assertTrue($result->hasChanged());
        self::assertFalse($result->hasChangedOtherThan(['favers', 'downloadTotal']));
        self::assertTrue($result->hasChangedOtherThan(['favers']));
    }

    public function testStructuralChangeAlwaysAffectsOtherProperties(): void
    {
        $result = NodeUpdateResult::structuralChange();

        self::assertTrue($result->successful);
        self::assertTrue($result->hasChanged());
        self::assertTrue($result->hasChangedOtherThan(['favers', 'downloadTotal']));
    }

    public function testFailure(): void
    {
        $result = NodeUpdateResult::failure();

        self::assertFalse($result->successful);
        self::assertFalse($result->hasChanged());
        self::assertFalse($result->hasChangedOtherThan(['favers']));
    }
}
