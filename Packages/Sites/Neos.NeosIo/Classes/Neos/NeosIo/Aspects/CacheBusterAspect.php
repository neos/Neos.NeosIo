<?php
namespace Neos\NeosIo\Aspects;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Fusion\FusionObjects\ResourceUriImplementation;

/**
 * @Flow\Aspect
 */
class CacheBusterAspect
{
    /**
     * @Flow\Around("method(Neos\FluidAdaptor\ViewHelpers\Uri\ResourceViewHelper->render())")
     *
     * @param JoinPointInterface $joinPoint The current joinpoint
     *
     * @return string The result of the target method if it has not been intercepted
     */
    public function addModificationTime(JoinPointInterface $joinPoint)
    {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        if ($joinPoint->getMethodArgument('resource') !== null) {
            return $result;
        }

        return $this->getModifiedResourceUri($result, $joinPoint->getMethodArgument('path'), $joinPoint->getMethodArgument('package'));
    }

    /**
     * @Flow\Around("method(Neos\Fusion\FusionObjects\ResourceUriImplementation->evaluate())")
     *
     * @param JoinPointInterface $joinPoint The current joinpoint
     *
     * @return string The result of the target method if it has not been intercepted
     */
    public function addModificationTimeToResourceUri(JoinPointInterface $joinPoint)
    {
        /** @var ResourceUriImplementation $proxy */
        $proxy = $joinPoint->getProxy();

        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        if ($proxy->getResource() !== null) {
            return $result;
        }

        return $this->getModifiedResourceUri($result, $proxy->getPath(), $proxy->getPackage());
    }

    /**
     * @param string $uri
     * @param string $path
     * @param string $package
     * @return string
     */
    protected function getModifiedResourceUri($uri, $path, $package)
    {
        if (strpos($path, 'resource://') === 0) {
            $resourcePath = $path;
        } elseif ($package !== null) {
            $resourcePath = 'resource://' . $package . '/Public/' . $path;
        } else {
            return $uri;
        }

        if (!is_dir($resourcePath) && strpos($uri, 'bust') === false) {
            try {
                $hash = 'bust=' . substr(sha1_file($resourcePath), 0, 8);

                if (strpos($uri, '?') === false) {
                    return $uri . '?' . $hash;
                } else {
                    return $uri . '&' . $hash;
                }
            } catch (\Exception $e) {
            }
        }
        return $uri;
    }
}
