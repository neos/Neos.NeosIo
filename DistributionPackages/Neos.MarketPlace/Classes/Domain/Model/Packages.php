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

use Neos\MarketPlace\Domain\Repository\PackageRepository;
use Neos\Flow\Annotations as Flow;

/**
 * Packages
 *
 * @api
 */
class Packages
{
    #[Flow\Inject]
    protected PackageRepository $packageRepository;

    /**
     * @var array<string, string>
     */
    #[Flow\InjectConfiguration('typeMapping')]
    protected array $packageTypes;

    /**
     * @var array<string, bool>
     */
    #[Flow\InjectConfiguration('vendorMapping')]
    protected array $vendors;

    /**
     * @var array<string, bool>
     */
    #[Flow\InjectConfiguration('packageBlackList')]
    protected array $packageBlackList;

    /**
     * @var string[]
     */
    protected array $processedPackages = [];

    public function packages(): \Generator
    {
        $vendors = array_keys(array_filter($this->vendors));
        foreach ($vendors as $vendor) {
            $packages = $this->packageRepository->findByVendor($vendor);
            foreach ($packages as $packageKey) {
                if ($this->isPackageBlacklisted($packageKey) || in_array($packageKey, $this->processedPackages, true)) {
                    continue;
                }
                yield $this->packageRepository->findByPackageKey($packageKey);
                $this->processedPackages[] = $packageKey;
            }
        }

        $packageTypes = array_keys(array_filter($this->packageTypes));
        foreach ($packageTypes as $type) {
            $packages = $this->packageRepository->findByPackageType($type);
            foreach ($packages as $packageKey) {
                if ($this->isPackageBlacklisted($packageKey) || in_array($packageKey, $this->processedPackages, true)) {
                    continue;
                }
                yield $this->packageRepository->findByPackageKey($packageKey);
                $this->processedPackages[] = $packageKey;
            }
        }
    }

    protected function isPackageBlacklisted(string $packageKey): bool
    {
        $blacklist = array_keys(array_filter($this->packageBlackList));
        return in_array($packageKey, $blacklist, true);
    }
}
