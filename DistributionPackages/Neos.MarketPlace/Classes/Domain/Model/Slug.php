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

use Cocur\Slugify\Slugify;
use Neos\Flow\Annotations as Flow;

/**
 * Package Tree by vendor
 *
 * @api
 */
#[Flow\Proxy(false)]
class Slug
{
    public static function create(string $string): string
    {
        return (new Slugify())->slugify($string);
    }
}
