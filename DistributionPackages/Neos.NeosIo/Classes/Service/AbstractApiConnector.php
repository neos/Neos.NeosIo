<?php

namespace Neos\NeosIo\Service;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;
use Neos\Flow\Http\Uri;
use Neos\Flow\Log\Utility\LogEnvironment;
use Psr\Log\LoggerInterface;

/**
 * Abstract base class for api connectors.
 *
 * Requires settings like:
 *
 * Vendor:
 *   Package:
 *     <implementationName>:
 *       apiUrl: 'https://my.rest.api/v2'
 *       timeout: 30
 *       parameters: # Are optional and will be added to each request
 *         api_key: 'xyz'
 *         format: 'json'
 *       actions:
 *         xyz: 'my_action.php'
 */
abstract class AbstractApiConnector
{
    /**
     * This should be overriden for the implementation.
     *
     * @Flow\Inject(setting="<implementationName>")
     *
     * @var array
     */
    protected $apiSettings;

    /**
     * @Flow\Inject
     *
     * @var VariableFrontend
     */
    protected $apiCache;

    /**
     * @var array
     */
    protected $objectCache = [];

    /**
     * @Flow\Inject
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Creates a valid cache identifier.
     *
     * @param $identifier
     *
     * @return string
     */
    protected function getCacheKey($identifier)
    {
        return sha1(self::class . '__' . $identifier);
    }

    /**
     * @param string $cacheKey
     *
     * @return mixed
     */
    protected function getItem($cacheKey)
    {
        if (array_key_exists($cacheKey, $this->objectCache)) {
            return $this->objectCache[$cacheKey];
        }
        $item = $this->apiCache->get($cacheKey);
        $this->objectCache[$cacheKey] = $item;

        return $item;
    }

    /**
     * @param string $cacheKey
     * @param mixed $value
     * @param array $tags
     */
    protected function setItem($cacheKey, $value, $tags = array())
    {
        $this->objectCache[$cacheKey] = $value;
        $this->apiCache->set($cacheKey, $value, $tags);
    }

    /**
     * @param string $cacheKey
     */
    protected function unsetItem($cacheKey)
    {
        unset($this->objectCache[$cacheKey]);
        $this->apiCache->remove($cacheKey);
    }

    /**
     * Retrieves data from the api.
     *
     * @param string $actionName
     * @param array $additionalParameters
     * @return array
     */
    protected function fetchJsonData($actionName, array $additionalParameters = [])
    {
        $browser = $this->getBrowser();
        $requestUri = $this->buildRequestUri($actionName, $additionalParameters);
        $response = $browser->request($requestUri, 'GET');

        if ($response->getStatusCode() !== 200) {
            $this->logger->error(sprintf('Get request to Api failed with code "%s"!', $response->getStatus()),
                LogEnvironment::fromMethodName(__METHOD__));
        }

        return $response !== false ? json_decode($response, true) : $response;
    }

    /**
     * Json encodes data and posts it to the api
     *
     * @param string $actionName
     * @param array $additionalParameters
     * @param array $data
     * @return bool
     */
    protected function postJsonData($actionName, array $additionalParameters, array $data)
    {
        $browser = $this->getBrowser();
        $browser->addAutomaticRequestHeader('Content-Type', 'application/json');
        $requestUri = $this->buildRequestUri($actionName, $additionalParameters);
        $response = $browser->request($requestUri, 'POST', [], [], [], json_encode($data));

        if (!in_array($response->getStatusCode(), [200, 204])) {
            $this->logger->error(sprintf('Post request to Api failed with message "%s"!', $response->getStatus()),
                LogEnvironment::fromMethodName(__METHOD__));
            return false;
        }
        return true;
    }

    /**
     * Returns a browser instance with curlengine and authentication parameters set
     *
     * @return Browser
     */
    protected function getBrowser()
    {
        $browser = new Browser();
        $browser->setRequestEngine(new CurlEngine());

        if (array_key_exists('username', $this->apiSettings) && !empty($this->apiSettings['username'])
            && array_key_exists('password', $this->apiSettings) && !empty($this->apiSettings['password'])
        ) {
            $browser->addAutomaticRequestHeader('Authorization',
                'Basic ' . base64_encode($this->apiSettings['username'] . ':' . $this->apiSettings['password']));
        }

        return $browser;
    }

    /**
     * @param string $actionName
     * @param array $additionalParameters
     *
     * @return Uri
     */
    protected function buildRequestUri($actionName, array $additionalParameters = [])
    {
        $requestUri = new Uri($this->apiSettings['apiUrl']);
        $requestUri->setPath($requestUri->getPath() . $this->apiSettings['actions'][$actionName]);
        $requestUri->setQuery(http_build_query(array_merge($this->apiSettings['parameters'], $additionalParameters)));
        return $requestUri;
    }
}
