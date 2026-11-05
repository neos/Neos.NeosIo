<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Service;

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
use Neos\Flow\Annotations as Flow;
use Neos\MarketPlace\Domain\Model\PackageSyncResult;
use Neos\MarketPlace\Domain\Model\Storage;
use Packagist\Api\Result\Package;
use Psr\Log\LoggerInterface;

/**
 * Package Importer
 *
 * @api
 */
#[Flow\Scope('singleton')]
class PackageImporter
{
    /**
     * @var array<string, bool> Processed packages list
     */
    protected array $processedPackages = [];

    /**
     * @var array<string, bool> Packages that were seen during the sync but not imported,
     *                           for example because they are abandoned or the import failed
     */
    protected array $skippedPackages = [];

    /**
     * @var LoggerInterface
     */
    #[Flow\Inject('Neos.MarketPlace:Logger')]
    protected $logger;

    public function __construct(
        protected PackageConverter $packageConverter,
        protected Storage          $storage)
    {
    }


    public function forceUpdates(bool $forceUpdates): void
    {
        $this->packageConverter->setForceUpdate($forceUpdates);
    }

    public function process(Package $package): PackageSyncResult
    {
        $result = $this->packageConverter->convert($package);
        $this->processedPackages[$package->getName()] = true;
        return $result;
    }

    /**
     * Marks a package as seen during the sync without importing it.
     *
     * This makes sure that the package is not removed by the cleanup, even though it was not imported.
     */
    public function skip(Package $package): void
    {
        $this->skippedPackages[$package->getName()] = true;
    }

    /**
     * Updates the vendors that were touched during the sync.
     */
    public function updateVendors(): void
    {
        $this->packageConverter->updateVendors();
    }

    public function updateIndex(): void
    {
        $this->packageConverter->updateIndex();
    }

    /**
     * Remove local package not preset in the processed packages list
     */
    public function cleanupPackages(?callable $callback = null): int
    {
        $count = 0;
        $packageNodes = $this->storage->getPackageNodes();
        $upstreamPackages = $this->getKnownPackages();
        foreach ($packageNodes as $packageNode) {
            if (in_array($packageNode->getProperty('title'), $upstreamPackages, true)) {
                continue;
            }

            if (!$this->storage->removeNode($packageNode)) {
                $this->logger->error('Could not remove package node ' . $packageNode->aggregateId->value);
                continue;
            }

            if ($callback !== null) {
                $callback($packageNode);
            }
            $this->emitPackageDeleted($packageNode);
            $count++;
        }
        return $count;
    }

    /**
     * Remove vendors without packages
     */
    public function cleanupVendors(?callable $callback = null): int
    {
        $count = 0;
        $vendorNodes = $this->storage->getVendorNodes();
        foreach ($vendorNodes as $vendorNode) {
            $packageCount = $this->storage->countPackageNodes($vendorNode->aggregateId);
            if ($packageCount > 0) {
                continue;
            }

            if (!$this->storage->removeNode($vendorNode)) {
                $this->logger->error('Could not remove vendor node ' . $vendorNode->aggregateId->value);
                continue;
            }

            if ($callback !== null) {
                $callback($vendorNode);
            }
            $this->emitVendorDeleted($vendorNode);
            $count++;
        }
        return $count;
    }

    /**
     * @return string[] List of processed packages
     */
    public function getProcessedPackages(): array
    {
        return array_keys($this->processedPackages);
    }

    /**
     * @return string[] List of packages that were seen during the sync but not imported
     */
    public function getSkippedPackages(): array
    {
        return array_keys($this->skippedPackages);
    }

    /**
     * @return string[] List of all packages that are known to still exist upstream
     */
    public function getKnownPackages(): array
    {
        return array_merge($this->getProcessedPackages(), $this->getSkippedPackages());
    }

    public function getProcessedPackagesCount(): int
    {
        return count($this->getProcessedPackages());
    }

    /**
     * Signals that a package node was deleted.
     */
    #[Flow\Signal]
    protected function emitPackageDeleted(Node $node): void
    {
    }

    /**
     * Signals that a package node was deleted.
     */
    #[Flow\Signal]
    protected function emitVendorDeleted(Node $node): void
    {
    }
}
