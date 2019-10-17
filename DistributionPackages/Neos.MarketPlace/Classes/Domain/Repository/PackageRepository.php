<?php
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
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PackageRepository
{
    /**
     * @param string $packageKey
     * @return Package
     */
    public function findByPackageKey($packageKey)
    {
        $client = new Client();
        return $client->get($packageKey);
    }

    /**
     * @param string $type
     * @return array
     */
    public function findByPackageType($type)
    {
        $client = new Client();
        return $client->all(['type' => $type]);
    }

    /**
     * @param string $vendor
     * @return array
     */
    public function findByVendor($vendor)
    {
        $client = new Client();
        return $client->all(['vendor' => $vendor]);
    }
}
