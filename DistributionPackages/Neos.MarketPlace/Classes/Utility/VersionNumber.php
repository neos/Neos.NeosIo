<?php
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

use Packagist\Api\Result\Package;
use Neos\Flow\Annotations as Flow;

/**
 * Version Number Utility
 */
class VersionNumber
{

    /**
     * @param string $versionNormalized
     * @return boolean
     */
    public static function isVersionStable($versionNormalized)
    {
        $versionNormalized = explode('-', $versionNormalized);
        return isset($versionNormalized[1]) ? false : true;
    }

    /**
     * @param string $versionNormalized
     * @return string
     */
    public static function getStabilityLevel($versionNormalized)
    {
        $versionNormalized = explode('-', $versionNormalized);
        if (count($versionNormalized) === 0) {
            return 'stable';
        }
        if ($versionNormalized[0] === 'dev') {
            return 'dev';
        }
        return isset($versionNormalized[1]) ? preg_replace('/[0-9]+/', '', strtolower($versionNormalized[1])) : 'stable';
    }

    /**
     * @param string $versionNormalized
     * @return integer
     */
    public static function toInteger($versionNormalized)
    {
        $versionNormalized = explode('-', $versionNormalized);
        $versionParts = explode('.', $versionNormalized[0]);
        $version = $versionParts[0];
        for ($i = 1; $i < 4; $i++) {
            if (!empty($versionParts[$i])) {
                $version .= str_pad((int)$versionParts[$i], 3, '0', STR_PAD_LEFT);
            } else {
                $version .= '000';
            }
        }
        return (int)$version;
    }
}
