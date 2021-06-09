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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\MarketPlace\Domain\Model\Storage;
use Packagist\Api\Result\Package;

/**
 * Package Importer
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PackageImporter
{
    private bool $forceUpdates = false;

    private Storage $storage;

    private array $processedPackages = [];

    public function useStorage(Storage $storage): void
    {
        $this->storage = $storage;
    }

    public function forceUpdates(bool $forceUpdates): void
    {
        $this->forceUpdates = $forceUpdates;
    }

    private function getConverter(): PackageConverter
    {
        if (!isset($this->converter)) {
            if (!isset($this->storage)) {
                throw new \RuntimeException('No storage set', 1616084519);
            }
            $this->converter = new PackageConverter($this->storage, $this->forceUpdates);
        }

        return $this->converter;
    }

    public function process(Package $package): NodeInterface
    {
        $node = $this->getConverter()->convert($package);
        $this->processedPackages[$package->getName()] = true;
        return $node;
    }

    /**
     * Remove local package not preset in the processed packages list
     *
     * @param Storage $storage
     * @param callable|null $callback function called after the package removal
     * @return integer
     * @throws \Neos\Eel\Exception
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\MarketPlace\Exception
     */
    public function cleanupPackages(Storage $storage, callable $callback = null): int
    {
        $count = 0;
        $storageNode = $storage->node();
        $query = new FlowQuery([$storageNode]);
        $query = $query->find('[instanceof Neos.MarketPlace:Package]');
        $upstreamPackages = $this->getProcessedPackages();
        foreach ($query as $package) {
            /** @var NodeInterface $package */
            if (in_array($package->getProperty('title'), $upstreamPackages, true)) {
                continue;
            }
            $package->remove();
            if ($callback !== null) {
                $callback($package);
            }
            $this->emitPackageDeleted($package);
            $count++;
        }
        return $count;
    }

    /**
     * Remove vendors without packages
     *
     * @param Storage $storage
     * @param callable|null $callback function called after the vendor removal
     * @return integer
     * @throws \Neos\Eel\Exception
     * @throws \Neos\MarketPlace\Exception
     */
    public function cleanupVendors(Storage $storage, ?callable $callback = null): int
    {
        $count = 0;
        $storageNode = $storage->node();
        $query = new FlowQuery([$storageNode]);
        $query = $query->find('[instanceof Neos.MarketPlace:Vendor]');
        foreach ($query as $vendor) {
            /** @var NodeInterface $vendor */
            $hasPackageQuery = new FlowQuery([$vendor]);
            $packageCount = $hasPackageQuery->find('[instanceof Neos.MarketPlace:Package]')->count();
            if ($packageCount > 0) {
                continue;
            }
            $vendor->remove();
            if ($callback !== null) {
                $callback($vendor);
            }
            $this->emitVendorDeleted($vendor);
            $count++;
        }
        return $count;
    }

    public function getProcessedPackages(): array
    {
        return array_keys(array_filter($this->processedPackages));
    }

    public function getProcessedPackagesCount(): int
    {
        return count($this->getProcessedPackages());
    }

    /**
     * Signals that a package node was deleted.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @return void
     */
    protected function emitPackageDeleted(NodeInterface $node): void
    {
    }

    /**
     * Signals that a package node was deleted.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @return void
     */
    protected function emitVendorDeleted(NodeInterface $node): void
    {
    }
}
