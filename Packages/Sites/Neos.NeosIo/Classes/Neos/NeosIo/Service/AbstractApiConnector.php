<?php

namespace Neos\NeosIo\Service;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Http\Client\Browser;
use TYPO3\Flow\Http\Client\CurlEngine;
use TYPO3\Flow\Http\Uri;

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
    protected $objectCache = array();

    /**
     * @Flow\Inject
     *
     * @var \TYPO3\Flow\Log\SystemLoggerInterface
     */
    protected $systemLogger;

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
     * Retrieves data from the api.
     *
     * @param string $actionName
     * @param array $additionalParameters
     * @return array
     */
    protected function fetchJsonData($actionName, $additionalParameters = [])
    {
        $browser = new Browser();
        $browser->setRequestEngine(new CurlEngine());

        if (array_key_exists('username', $this->apiSettings) && !empty($this->apiSettings['username'])
            && array_key_exists('password', $this->apiSettings) && !empty($this->apiSettings['password'])
        ) {
            $browser->addAutomaticRequestHeader('Authorization', 'Basic ' . base64_encode($this->apiSettings['username'] . ':' . $this->apiSettings['password']));
        }

        $requestUri = $this->buildRequestUri($actionName, $additionalParameters);
        $response = $browser->request($requestUri, 'GET');

        if ($response->getStatusCode() !== 200) {
            $this->systemLogger->log('Request to Api failed!', LOG_ERR, 1453193835);
        }

        return $response !== false ? json_decode($response, true) : $response;
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
        $requestUri->setPath($this->apiSettings['actions'][$actionName]);
        $requestUri->setQuery(http_build_query(array_merge($this->apiSettings['parameters'], $additionalParameters)));
        return $requestUri;
    }
}
