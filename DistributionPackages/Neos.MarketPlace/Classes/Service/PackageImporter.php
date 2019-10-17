<?php
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

use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\MarketPlace\Domain\Model\Storage;
use Neos\MarketPlace\Domain\Repository\PackageRepository;
use Neos\MarketPlace\Property\TypeConverter\PackageConverter;
use Packagist\Api\Result\Package;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\PropertyMapper;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * Package Importer
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PackageImporter implements PackageImporterInterface
{
    /**
     * @var PackageRepository
     * @Flow\Inject
     */
    protected $packageRepository;

    /**
     * @var PropertyMapper
     * @Flow\Inject
     */
    protected $propertyMapper;

    /**
     * @var array
     */
    protected $processedPackages = [];

    /**
     * {@inheritdoc}
     */
    public function process(Package $package, Storage $storage, $force = false)
    {
        $configuration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $configuration->setTypeConverterOption(
            PackageConverter::class,
            PackageConverter::STORAGE,
            $storage
        );
        $configuration->setTypeConverterOption(
            PackageConverter::class,
            PackageConverter::FORCE,
            $force
        );
        $node = $this->propertyMapper->convert($package, NodeInterface::class, $configuration);
        $this->processedPackages[$package->getName()] = true;
        return $node;
    }

    /**
     * Remove local package not preset in the processed packages list
     *
     * @param Storage $storage
     * @param callable $callback function called after the package removal
     * @return integer
     */
    public function cleanupPackages(Storage $storage, callable $callback = null)
    {
        $count = 0;
        $storageNode = $storage->node();
        $query = new FlowQuery([$storageNode]);
        $query = $query->find('[instanceof Neos.MarketPlace:Package]');
        $upstreamPackages = $this->getProcessedPackages();
        foreach ($query as $package) {
            /** @var NodeInterface $package */
            if (in_array($package->getProperty('title'), $upstreamPackages)) {
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
     * @param callable $callback function called after the vendor removal
     * @return integer
     */
    public function cleanupVendors(Storage $storage, callable $callback = null)
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

    /**
     * @return array
     */
    public function getProcessedPackages() {
        return array_keys(array_filter($this->processedPackages));
    }

    /**
     * @return integer
     */
    public function getProcessedPackagesCount() {
        return count($this->getProcessedPackages());
    }

    /**
     * Signals that a package node was deleted.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @return void
     */
    protected function emitPackageDeleted(NodeInterface $node)
    {
    }

    /**
     * Signals that a package node was deleted.
     *
     * @Flow\Signal
     * @param NodeInterface $node
     * @return void
     */
    protected function emitVendorDeleted(NodeInterface $node)
    {
    }
}
