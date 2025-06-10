<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Eel;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Composer\Package\Version\VersionParser;
use Neos\Eel\ProtectedContextAwareInterface;

/**
 * Helper for packages in the Neos.MarketPlace package.
 */
class PackageHelper implements ProtectedContextAwareInterface
{

    public function sanitiseVersionArgument(?string $version): ?string
    {
        $version = trim($version ?? '');

        if ($version === '') {
            return null;
        }

        // Ensure the version is a valid semantic version
        try {
            $parser = new VersionParser();
            $parser->normalize($version);
        } catch (\UnexpectedValueException) {
            // If the version is not valid, return the original version string
            return null;
        }

        return $version;
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
