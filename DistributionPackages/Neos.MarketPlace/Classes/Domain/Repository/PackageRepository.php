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

use Neos\Flow\Annotations as Flow;
use Packagist\Api\Client;
use Packagist\Api\Result\Package;

/**
 * Handle request to Packagist to get package informations
 * @api
 */
#[Flow\Scope('singleton')]
class PackageRepository
{
    /**
     * The client is reused for all requests, otherwise every single request would
     * set up a new connection (including the TLS handshake) to packagist.org.
     */
    protected Client $client;

    /**
     * @var array<string, string[]>
     */
    protected array $packageListCache = [];

    public function __construct()
    {
        $this->client = new Client();
    }

    public function findByPackageKey(string $packageKey): Package
    {
        /** @phpstan-ignore return.type */
        return $this->client->get($packageKey);
    }

    /**
     * @return string[]
     */
    public function findByPackageType(string $type): array
    {
        return $this->findAll(['type' => $type]);
    }

    /**
     * @return string[]
     */
    public function findByVendor(string $vendor): array
    {
        return $this->findAll(['vendor' => $vendor]);
    }

    /**
     * @param array<string, string> $filters
     * @return string[]
     */
    protected function findAll(array $filters): array
    {
        $cacheKey = implode('|', array_map(static fn (string $key, string $value): string => $key . '=' . $value, array_keys($filters), array_values($filters)));
        if (!array_key_exists($cacheKey, $this->packageListCache)) {
            $packages = [];
            $response = $this->client->all($filters);
            if (is_array($response)) {
                foreach ($response as $package) {
                    if (is_string($package)) {
                        $packages[] = $package;
                    }
                }
            }
            $this->packageListCache[$cacheKey] = $packages;
        }
        return $this->packageListCache[$cacheKey];
    }
}
