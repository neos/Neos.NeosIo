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

use Neos\MarketPlace\Domain\Model\PackageSyncState;
use PHPUnit\Framework\TestCase;

class PackageSyncStateTest extends TestCase
{
    public function testRoundTrip(): void
    {
        $state = new PackageSyncState(1700000000, 'abc123');
        $restored = PackageSyncState::fromCacheValue($state->toCacheValue());

        self::assertNotNull($restored);
        self::assertSame(1700000000, $restored->syncedAt);
        self::assertSame('abc123', $restored->versionsHash);
    }

    public function testLegacyCacheValueOnlyContainsTheTimestamp(): void
    {
        $restored = PackageSyncState::fromCacheValue('1700000000');

        self::assertNotNull($restored);
        self::assertSame(1700000000, $restored->syncedAt);
        self::assertNull($restored->versionsHash);
    }

    public function testEmptyCacheValue(): void
    {
        self::assertNull(PackageSyncState::fromCacheValue(false));
        self::assertNull(PackageSyncState::fromCacheValue(''));
        self::assertNull(PackageSyncState::fromCacheValue(null));
    }
}
