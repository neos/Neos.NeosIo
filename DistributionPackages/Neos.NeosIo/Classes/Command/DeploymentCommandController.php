<?php
declare(strict_types=1);

namespace Neos\NeosIo\Command;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Cli\CommandController;

/**
 * Runs on every container start, including pods added by the autoscaler.
 * Flushing the content cache on each of these starts leaves the site with a
 * cold cache under load, so only the first start of a deployment flushes it.
 * The marker cache must be shared by all pods of an instance.
 */
#[Flow\Scope('singleton')]
final class DeploymentCommandController extends CommandController
{
    private const string MARKER_CACHE_IDENTIFIER = 'NeosNeosIo_DeploymentMarker';
    private const string MARKER_ENTRY_IDENTIFIER = 'contentCacheFlushedForDeployment';

    #[Flow\Inject]
    protected CacheManager $cacheManager;

    /**
     * Flush the content cache once per deployment
     *
     * Flushes the content cache unless it was already flushed for the given
     * deployment. Without a deployment identifier the cache is always flushed.
     *
     * @param string $deploymentIdentifier Identifier of the current deployment, e.g. $BEACH_DEPLOYMENT_JOB_IDENTIFIER
     */
    public function flushContentCacheCommand(string $deploymentIdentifier = ''): void
    {
        $markerCache = $this->cacheManager->getCache(self::MARKER_CACHE_IDENTIFIER);
        if ($deploymentIdentifier !== '' && $markerCache->get(self::MARKER_ENTRY_IDENTIFIER) === $deploymentIdentifier) {
            $this->outputLine('Content cache was already flushed for deployment "%s".', [$deploymentIdentifier]);
            return;
        }

        $this->cacheManager->getCache('Neos_Fusion_Content')->flush();

        if ($deploymentIdentifier === '') {
            $this->outputLine('Flushed content cache (no deployment identifier given).');
            return;
        }
        $markerCache->set(self::MARKER_ENTRY_IDENTIFIER, $deploymentIdentifier);
        $this->outputLine('Flushed content cache for deployment "%s".', [$deploymentIdentifier]);
    }
}
