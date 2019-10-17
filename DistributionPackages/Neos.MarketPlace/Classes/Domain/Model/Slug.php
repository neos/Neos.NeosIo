<?php
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
use Packagist\Api\Result\Package;
use Neos\Flow\Annotations as Flow;

/**
 * Package Tree by vendor
 *
 * @api
 */
class Slug
{
    /**
     * @param string $string
     * @return string
     */
    public static function create($string)
    {
        $slugify = new Slugify();
        return $slugify->slugify($string);
    }
}
