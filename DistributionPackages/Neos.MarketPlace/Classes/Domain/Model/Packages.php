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

use Neos\MarketPlace\Domain\Repository\PackageRepository;
use Neos\Flow\Annotations as Flow;

/**
 * Packages
 *
 * @api
 */
class Packages
{
    /**
     * @var PackageRepository
     * @Flow\Inject
     */
    protected $packageRepository;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="typeMapping")
     */
    protected $packageTypes;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="vendorMapping")
     */
    protected $vendors;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="packageBlackList")
     */
    protected $packageBlackList;

    /**
     * @var array
     */
    protected $processedPackages = [];

    /**
     * @return \Generator
     */
    public function packages()
    {
        $vendors = array_keys(array_filter($this->vendors));
        foreach ($vendors as $vendor) {
            $packages = $this->packageRepository->findByVendor($vendor);
            foreach ($packages as $packageKey) {
                if ($this->isPackageBlacklisted($packageKey) || in_array($packageKey, $this->processedPackages)) {
                    continue;
                }
                $package = $this->packageRepository->findByPackageKey($packageKey);
                yield $package;
                $this->processedPackages[] = $packageKey;
            }
        }

        $packageTypes = array_keys(array_filter($this->packageTypes));
        foreach ($packageTypes as $type) {
            $packages = $this->packageRepository->findByPackageType($type);
            foreach ($packages as $packageKey) {
                if ($this->isPackageBlacklisted($packageKey) || in_array($packageKey, $this->processedPackages)) {
                    continue;
                }
                $package = $this->packageRepository->findByPackageKey($packageKey);
                yield $package;
                $this->processedPackages[] = $packageKey;
            }
        }
    }

    /**
     * @param string $packageKey
     * @return boolean
     */
    protected function isPackageBlacklisted($packageKey)
    {
        $blacklist = array_keys(array_filter($this->packageBlackList));
        return in_array($packageKey, $blacklist);
    }
}
