<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Command;

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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\MarketPlace\Domain\Model\LogAction;
use Neos\MarketPlace\Domain\Model\Packages;
use Neos\MarketPlace\Domain\Model\Storage;
use Neos\MarketPlace\Service\PackageImporter;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Packagist\Api\Client;
use Packagist\Api\Result\Package;
use Psr\Log\LoggerInterface;

/**
 * MarketPlace Command Controller
 */
class MarketPlaceCommandController extends CommandController
{
    /**
     * @var PackageImporter
     * @Flow\Inject
     */
    protected $importer;

    /**
     * @var ContentContextFactory
     * @Flow\Inject
     */
    protected $contextFactory;

    /**
     * @var LoggerInterface
     * @Flow\Inject
     */
    protected $logger;

    /**
     * @var ThrowableStorageInterface
     * @Flow\Inject
     */
    protected $throwableStorage;

    /**
     * @var PersistenceManager
     * @Flow\Inject
     */
    protected $persistenceManager;

    /**
     * Sync packages from Packagist
     *
     * @param string|null $package Sync only the given package
     * @param boolean $force Force sync even if the package is not update on packagist
     * @return void
     */
    public function syncCommand(string $package = '', bool $force = false): void
    {
        $beginTime = microtime(true);

        $hasError = false;
        $elapsedTime = static function ($timer = null) use ($beginTime) {
            return microtime(true) - ($timer ?: $beginTime);
        };
        $count = 0;
        $this->outputLine();
        $this->outputLine('Synchronize with Packagist ...');
        $this->outputLine('------------------------------');
        $storage = new Storage();
        $this->importer->useStorage($storage);
        $this->importer->forceUpdates($force);

        $process = function (Package $package) use (&$count) {
            $count++;
            $this->outputLine(sprintf('  %d/ %s (%s)', $count, $package->getName(), $package->getTime()));
            $this->importer->process($package);

            if ($count % 10 === 0) {
                $this->persistenceManager->persistAll();
                foreach ($this->contextFactory->getInstances() as $contextInstance) {
                    $contextInstance->getFirstLevelNodeCache()->flush();
                }
                $this->persistenceManager->clearState();
            }
        };

        if ($package === '') {
            $this->logger->info(sprintf('action=%s', LogAction::FULL_SYNC_STARTED), LogEnvironment::fromMethodName(__METHOD__));
            $packages = new Packages();
            foreach ($packages->packages() as $packagistPackage) {
                $this->logger->info(sprintf('action=%s package=%s', LogAction::SINGLE_PACKAGE_SYNC_STARTED, $packagistPackage->getName()), LogEnvironment::fromMethodName(__METHOD__));
                $timer = microtime(true);
                try {
                    $process($packagistPackage);
                    $this->logger->info(sprintf('action=%s package=%s duration=%f', LogAction::SINGLE_PACKAGE_SYNC_FINISHED, $packagistPackage->getName(), $elapsedTime($timer)), LogEnvironment::fromMethodName(__METHOD__));
                } catch (\Exception $exception) {
                    $this->logger->error(sprintf('action=%s package=%s duration=%f', LogAction::SINGLE_PACKAGE_SYNC_FAILED, $packagistPackage->getName(), $elapsedTime($timer)), LogEnvironment::fromMethodName(__METHOD__));
                    $logMessage = $this->throwableStorage->logThrowable($exception);
                    $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
                    $hasError = true;
                }
            }
            $this->cleanupPackages($storage);
            $this->cleanupVendors($storage);
            $this->logger->info(sprintf('action=%s duration=%f', LogAction::FULL_SYNC_FINISHED, $elapsedTime()), LogEnvironment::fromMethodName(__METHOD__));

            $this->outputLine();
            $this->outputLine(sprintf('%d package(s) imported with success', $this->importer->getProcessedPackagesCount()));
        } else {
            $this->logger->info(sprintf('action=%s package=%s', LogAction::SINGLE_PACKAGE_SYNC_STARTED, $package), LogEnvironment::fromMethodName(__METHOD__));
            $client = new Client();
            try {
                $packagistPackage = $client->get($package);
                $process($packagistPackage);
                $this->logger->info(sprintf('action=%s package=%s duration=%f', LogAction::SINGLE_PACKAGE_SYNC_FINISHED, $package, $elapsedTime()), LogEnvironment::fromMethodName(__METHOD__));
            } catch (\Exception $exception) {
                $this->logger->error(sprintf('action=%s package=%s duration=%f', LogAction::SINGLE_PACKAGE_SYNC_FAILED, $package, $elapsedTime()), LogEnvironment::fromMethodName(__METHOD__));
                $logMessage = $this->throwableStorage->logThrowable($exception);
                $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
                $hasError = true;
            }

            $this->outputLine();
            if ($hasError) {
                $this->outputLine(sprintf('Package "%s" import failed', $package));
            } else {
                $this->outputLine(sprintf('Package "%s" imported with success', $package));
            }
        }

        if ($hasError) {
            $this->outputLine();
            $this->outputLine('Check your log, we have some trouble to sync some pages ...');
        }

        $this->outputLine();
        $this->outputLine(sprintf('Duration: %f seconds', $elapsedTime()));
    }

    /**
     * Remove packages that don't exist on Packagist
     *
     * @param Storage $storage
     */
    protected function cleanupPackages(Storage $storage): void
    {
        $this->outputLine();
        $this->outputLine('Cleanup packages ...');
        $this->outputLine('--------------------');
        $count = $this->importer->cleanupPackages($storage, function (NodeInterface $package) {
            $this->outputLine(sprintf('%s deleted', $package->getLabel()));
        });
        if ($count > 0) {
            $this->outputLine(sprintf('  Deleted %d package(s)', $count));
        }
    }

    /**
     * Remove vendors that don't exist on Packagist or contains no packages
     *
     * @param Storage $storage
     */
    protected function cleanupVendors(Storage $storage): void
    {
        $this->outputLine();
        $this->outputLine('Cleanup vendors ...');
        $this->outputLine('-------------------');
        $count = $this->importer->cleanupVendors($storage, function (NodeInterface $vendor) {
            $this->outputLine(sprintf('%s deleted', $vendor->getLabel()));
        });
        if ($count > 0) {
            $this->outputLine(sprintf('  Deleted %d vendor(s)', $count));
        }
    }
}
