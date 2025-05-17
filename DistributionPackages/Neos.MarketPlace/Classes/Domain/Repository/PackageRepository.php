<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Domain\Repository;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Packagist\Api\Client;
use Packagist\Api\Result\Package;
use Neos\Flow\Annotations as Flow;

/**
 * Handle request to Packagist to get package informations
 * @api
 */
#[Flow\Scope('singleton')]
class PackageRepository
{
    public function findByPackageKey(string $packageKey): Package
    {
        return (new Client())->get($packageKey);
    }

    public function findByPackageType(string $type): array
    {
        return (new Client())->all(['type' => $type]);
    }

    public function findByVendor(string $vendor): array
    {
        return (new Client())->all(['vendor' => $vendor]);
    }
}
