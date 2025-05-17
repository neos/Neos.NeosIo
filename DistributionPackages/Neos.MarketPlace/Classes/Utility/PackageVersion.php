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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\Flow\Annotations as Flow;

/**
 * Package Version Utility
 * @api
 */
#[Flow\Proxy(false)]
class PackageVersion
{

    /**
     * Extracts the last (stable) version from a list of versions
     */
    public static function extractLastVersion(Nodes $versionNodes): ?Node
    {
        try {
            $versions = iterator_to_array($versionNodes->getIterator());
        } catch (\Exception) {
            return null;
        }
        usort($versions, static function (Node $a, Node $b) {
            return $b->getProperty('versionNormalized') <=> $a->getProperty('versionNormalized');
        });
        $stableVersions = array_filter($versions, static function (Node $version) {
            return $version->getProperty('stability') === true;
        });
        if (count($stableVersions) > 0) {
            return $stableVersions[0] ?? null;
        }
        return $versions[0] ?? null;
    }
}
