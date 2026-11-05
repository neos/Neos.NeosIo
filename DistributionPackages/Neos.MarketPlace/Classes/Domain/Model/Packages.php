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
use Packagist\Api\Result\Package;

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
    #[Flow\InjectConfiguration('packageIgnoreList')]
    protected array $packageIgnoreList;

    /**
     * @var array<string, bool>
     */
    protected array $processedPackages = [];

    /**
     * @var string[]|null
     */
    protected ?array $ignoredPackages = null;

    public function packages(): \Generator
    {
        $vendors = array_keys(array_filter($this->vendors));
        foreach ($vendors as $vendor) {
            yield from $this->packagesForKeys($this->packageRepository->findByVendor($vendor));
        }

        $packageTypes = array_keys(array_filter($this->packageTypes));
        foreach ($packageTypes as $type) {
            yield from $this->packagesForKeys($this->packageRepository->findByPackageType($type));
        }
    }

    /**
     * @param string[] $packageKeys
     * @return \Generator<int, Package>
     */
    protected function packagesForKeys(array $packageKeys): \Generator
    {
        foreach ($packageKeys as $packageKey) {
            if (isset($this->processedPackages[$packageKey]) || $this->isPackageIgnored($packageKey)) {
                continue;
            }
            $this->processedPackages[$packageKey] = true;
            yield $this->packageRepository->findByPackageKey($packageKey);
        }
    }

    protected function isPackageIgnored(string $packageKey): bool
    {
        $this->ignoredPackages ??= array_keys(array_filter($this->packageIgnoreList));
        return in_array($packageKey, $this->ignoredPackages, true);
    }
}
