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

use Neos\Flow\Annotations as Flow;

/**
 * Version Number Utility
 */
#[Flow\Proxy(false)]
class VersionNumber
{

    public static function isVersionStable(string $versionNormalized): bool
    {
        $versionParts = explode('-', $versionNormalized);
        return !isset($versionParts[1]);
    }

    public static function getStabilityLevel(string $versionNormalized): string
    {
        $versionNormalizedParts = explode('-', $versionNormalized);
        /** @noinspection PhpDuplicateMatchArmBodyInspection */
        return match (true) {
            !$versionNormalized => 'stable',
            $versionNormalizedParts[0] === 'dev' => 'dev',
            isset($versionNormalizedParts[1]) => (string)preg_replace('/[\d]+/', '', strtolower($versionNormalizedParts[1])),
            default => 'stable',
        };
    }

    public static function toInteger(string $versionNormalized): int
    {
        $versionNormalizedParts = explode('-', $versionNormalized);
        $versionParts = explode('.', $versionNormalizedParts[0]);
        $version = $versionParts[0];
        for ($i = 1; $i < 4; $i++) {
            if (!empty($versionParts[$i])) {
                $version .= str_pad((string)(int)$versionParts[$i], 3, '0', STR_PAD_LEFT);
            } else {
                $version .= '000';
            }
        }
        return (int)$version;
    }
}
