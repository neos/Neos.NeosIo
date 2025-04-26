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

    private array $processedPackages = [];

    private $packageConverter;

    public function forceUpdates(bool $forceUpdates): void
    {
        $this->forceUpdates = $forceUpdates;
    }

    public function process(Package $package): bool
    {
        if (!$this->packageConverter) {
            $this->packageConverter = new PackageConverter($this->forceUpdates);
        }
        $processed = $this->packageConverter->convert($package);
        $this->processedPackages[$package->getName()] = true;
        return $processed;
    }

    /**
     * Remove local package not preset in the processed packages list
     *
     * @throws \Neos\Eel\Exception
     * @throws \Neos\MarketPlace\Exception
     */
    public function cleanupPackages(?callable $callback = null): int
    {
        $count = 0;
        $storageNode = (new Storage())->node();
        $query = new FlowQuery([$storageNode]);
        $query = $query->find('[instanceof Neos.MarketPlace:Package]');
        $upstreamPackages = $this->getProcessedPackages();
        foreach ($query as $package) {
            /** @var Node $package */
            if (in_array($package->getProperty('title'), $upstreamPackages, true)) {
                continue;
            }
            // TODO 9.0 migration: !! Node::remove() is not supported by the new CR. Use the "RemoveNodeAggregate" command to remove a node.
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
     * @throws \Neos\Eel\Exception
     * @throws \Neos\MarketPlace\Exception
     */
    public function cleanupVendors(?callable $callback = null): int
    {
        $count = 0;
        $storageNode = (new Storage())->node();
        $query = new FlowQuery([$storageNode]);
        $query = $query->find('[instanceof Neos.MarketPlace:Vendor]');
        foreach ($query as $vendor) {
            /** @var Node $vendor */
            $hasPackageQuery = new FlowQuery([$vendor]);
            $packageCount = $hasPackageQuery->find('[instanceof Neos.MarketPlace:Package]')->count();
            if ($packageCount > 0) {
                continue;
            }
            // TODO 9.0 migration: !! Node::remove() is not supported by the new CR. Use the "RemoveNodeAggregate" command to remove a node.
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
     * @param Node $node
     * @return void
     */
    protected function emitPackageDeleted(Node $node): void
    {
    }

    /**
     * Signals that a package node was deleted.
     *
     * @Flow\Signal
     * @param Node $node
     * @return void
     */
    protected function emitVendorDeleted(Node $node): void
    {
    }
}
