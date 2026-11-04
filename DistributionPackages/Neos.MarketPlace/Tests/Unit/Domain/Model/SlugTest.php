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

use Neos\MarketPlace\Domain\Model\Slug;
use PHPUnit\Framework\TestCase;

class SlugTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function slugDataProvider(): array
    {
        return [
            'package name' => ['neos/neos', 'neos-neos'],
            'upper case' => ['Neos/Neos', 'neos-neos'],
            'version' => ['1.0.0', '1-0-0'],
            'dev branch' => ['dev-master', 'dev-master'],
        ];
    }

    /**
     * @dataProvider slugDataProvider
     */
    public function testCreate(string $input, string $expected): void
    {
        self::assertSame($expected, Slug::create($input));
    }

    public function testCreateIsStableAcrossCalls(): void
    {
        self::assertSame(Slug::create('neos/neos'), Slug::create('neos/neos'));
    }
}
