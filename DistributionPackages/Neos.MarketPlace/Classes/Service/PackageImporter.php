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

use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\Security\Exception\AccessDenied;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
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
    protected array $processedPackages = [];

    /**
     * @var LoggerInterface
     */
    #[Flow\Inject('Neos.MarketPlace:Logger')]
    protected $logger;

    public function __construct(
        protected PackageConverter          $packageConverter,
        protected ContentRepositoryRegistry $contentRepositoryRegistry,
        protected Storage                   $storage)
    {
    }


    public function forceUpdates(bool $forceUpdates): void
    {
        $this->packageConverter->setForceUpdate($forceUpdates);
    }

    public function process(Package $package): bool
    {
        $processed = $this->packageConverter->convert($package);
        $this->processedPackages[$package->getName()] = true;
        return $processed;
    }

    /**
     * Remove local package not preset in the processed packages list
     */
    public function cleanupPackages(?callable $callback = null): int
    {
        $count = 0;
        $packageNodes = $this->storage->getPackageNodes();
        $upstreamPackages = $this->getProcessedPackages();
        foreach ($packageNodes as $packageNode) {
            if (in_array($packageNode->getProperty('title'), $upstreamPackages, true)) {
                continue;
            }

            try {
                $this->contentRepositoryRegistry->get($packageNode->contentRepositoryId)
                    ->handle(
                        RemoveNodeAggregate::create(
                            $packageNode->workspaceName,
                            $packageNode->aggregateId,
                            $packageNode->dimensionSpacePoint, NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS
                        )
                    );
            } catch (AccessDenied $e) {
                $this->logger->error('Access denied while trying to remove package node: ' . $e->getMessage());
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
            $packageCount = $this->storage->countPackageNodes($vendorNode);
            if ($packageCount > 0) {
                continue;
            }

            try {
                $this->contentRepositoryRegistry->get($vendorNode->contentRepositoryId)
                    ->handle(
                        RemoveNodeAggregate::create(
                            $vendorNode->workspaceName,
                            $vendorNode->aggregateId,
                            $vendorNode->dimensionSpacePoint, NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS
                        )
                    );
            } catch (AccessDenied $e) {
                $this->logger->error('Access denied while trying to remove vendor node: ' . $e->getMessage());
            }

            if ($callback !== null) {
                $callback($vendorNode);
            }
            $this->emitVendorDeleted($vendorNode);
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
