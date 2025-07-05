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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\MarketPlace\Domain\Model\LogAction;
use Neos\MarketPlace\Domain\Model\Packages;
use Neos\MarketPlace\Service\PackageImporter;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Packagist\Api\Client;
use Packagist\Api\Result\Package;
use Psr\Log\LoggerInterface;

/**
 * MarketPlace Command Controller
 */
class MarketPlaceCommandController extends CommandController
{
    #[Flow\Inject]
    protected PackageImporter $importer;

    /**
     * @var LoggerInterface
     */
    #[Flow\Inject('Neos.MarketPlace:Logger')]
    protected $logger;

    /**
     * @var ThrowableStorageInterface
     */
    #[Flow\Inject]
    protected $throwableStorage;

    #[Flow\Inject]
    protected PersistenceManager $persistenceManager;

    #[Flow\Inject]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    /**
     * Sync packages from Packagist
     *
     * @param string $package Sync only the given package
     * @param boolean $force Force sync even if the package is not update on packagist
     */
    public function syncCommand(string $package = '', bool $force = false, int $limit = 0, bool $dontCountSkippedPackages = true): void
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

        $this->importer->forceUpdates($force);

        if ($package === '') {
            $this->logger->info(sprintf('action=%s', LogAction::FULL_SYNC_STARTED->value), LogEnvironment::fromMethodName(__METHOD__));
            $packages = new Packages();
            foreach ($packages->packages() as $packagistPackage) {
                $this->logger->info(sprintf('action=%s package=%s', LogAction::SINGLE_PACKAGE_SYNC_STARTED->value, $packagistPackage->getName()), LogEnvironment::fromMethodName(__METHOD__));
                $timer = microtime(true);

                if ($packagistPackage->isAbandoned()) {
                    $this->outputFormatted('<info>Skipping <b>%s</b> (abandoned)</info>', [$packagistPackage->getName()], 2);
                    continue;
                }

                try {
                    $result = $this->processPackage($packagistPackage, $count);
                    if (!$result && $limit > 0 && $dontCountSkippedPackages) {
                        $limit++;
                    }
                    $this->logger->info(sprintf('action=%s package=%s duration=%f', LogAction::SINGLE_PACKAGE_SYNC_FINISHED->value, $packagistPackage->getName(), $elapsedTime($timer)), LogEnvironment::fromMethodName(__METHOD__));
                } catch (\Exception $exception) {
                    $this->logger->error(sprintf('action=%s package=%s duration=%f', LogAction::SINGLE_PACKAGE_SYNC_FAILED->value, $packagistPackage->getName(), $elapsedTime($timer)), LogEnvironment::fromMethodName(__METHOD__));
                    $logMessage = $this->throwableStorage->logThrowable($exception);
                    $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
                    $hasError = true;
                }

                if ($limit > 0 && $count === $limit) {
                    $this->outputLine('Stopping as limit is reached');
                    break;
                }
            }
            if ($limit === 0) {
                $this->cleanupPackages();
                $this->cleanupVendors();
            }
            $this->logger->info(sprintf('action=%s duration=%f', LogAction::FULL_SYNC_FINISHED->value, $elapsedTime()), LogEnvironment::fromMethodName(__METHOD__));

            $this->outputLine();
            $this->outputLine('%d package(s) synced with success', [$this->importer->getProcessedPackagesCount()]);

            $this->outputLine('Updating search index');
            $this->importer->updateIndex();
        } else {
            $this->logger->info(sprintf('action=%s package=%s', LogAction::SINGLE_PACKAGE_SYNC_STARTED->value, $package), LogEnvironment::fromMethodName(__METHOD__));
            $client = new Client();
            try {
                /** @var Package $packagistPackage */
                $packagistPackage = $client->get($package);
                $this->processPackage($packagistPackage, $count);
                $this->logger->info(sprintf('action=%s package=%s duration=%f', LogAction::SINGLE_PACKAGE_SYNC_FINISHED->value, $package, $elapsedTime()), LogEnvironment::fromMethodName(__METHOD__));
            } catch (\Exception $exception) {
                $this->logger->error(sprintf('action=%s package=%s duration=%f', LogAction::SINGLE_PACKAGE_SYNC_FAILED->value, $package, $elapsedTime()), LogEnvironment::fromMethodName(__METHOD__));
                $logMessage = $this->throwableStorage->logThrowable($exception);
                $this->logger->error($logMessage, LogEnvironment::fromMethodName(__METHOD__));
                $hasError = true;
            }

            $this->outputLine();
            if ($hasError) {
                $this->outputLine('Package "%s" import failed', [$package]);
            } else {
                $this->outputLine('Package "%s" imported with success', [$package]);
            }
            $this->outputLine('Updating search index');
            $this->importer->updateIndex();
        }

        if ($hasError) {
            $this->outputLine();
            $this->outputLine('Check your log, we have some trouble to sync some pages ...');
        }

        $this->outputLine();
        $this->outputLine('Peak memory usage: %d MB', [round(memory_get_peak_usage(true) / 1024 / 1024)]);
        $this->outputLine('Duration: %f seconds', [$elapsedTime()]);
    }

    private function processPackage(Package $package, int &$count): bool
    {
        $count++;
        $this->outputFormatted('%d/ <b>%s</b> (%d versions)', [$count, $package->getName(), count($package->getVersions())], 2);

        $result = $this->importer->process($package);
        if (!$result) {
            $this->outputFormatted('<i>Skipped package</i>', [], 4);
        }
        if ($count % 10 === 0) {
            $this->outputFormatted('<b>Flush persistence</b>', [], 2);
            $this->persistenceManager->persistAll();
        }
        return $result;
    }

    /**
     * Remove packages that don't exist on Packagist
     */
    protected function cleanupPackages(): void
    {
        $this->outputLine();
        $this->outputLine('Cleanup packages ...');
        $this->outputLine('--------------------');
        $count = $this->importer->cleanupPackages(function (Node $package) {
            $this->outputLine('%s deleted', [$this->nodeLabelGenerator->getLabel($package)]);
        });
        if ($count > 0) {
            $this->outputFormatted('Deleted %d package(s)', [$count], 2);
        }
    }

    /**
     * Remove vendors that don't exist on Packagist or contains no packages
     */
    protected function cleanupVendors(): void
    {
        $this->outputLine();
        $this->outputLine('Cleanup vendors ...');
        $this->outputLine('-------------------');
        $count = $this->importer->cleanupVendors(function (Node $vendor) {
            $this->outputLine('%s deleted', [$this->nodeLabelGenerator->getLabel($vendor)]);
        });
        if ($count > 0) {
            $this->outputFormatted('Deleted %d vendor(s)', [$count], 2);
        }
    }
}
