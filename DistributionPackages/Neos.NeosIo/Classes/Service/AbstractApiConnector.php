<?php
declare(strict_types=1);

namespace Neos\NeosIo\Service;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use GuzzleHttp\Psr7\Uri;
use Neos\Cache\Exception as CacheException;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Http\Client\Browser;
use Neos\Flow\Http\Client\CurlEngine;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
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
     * This should be overridden for the implementation.
     *
     * Define "@Flow\InjectConfiguration(path='<implementationName>')" in your custom implementation
     * @var array{ username: string, password: string, apiUrl: string, actions: array<string, string>, parameters: array<string, mixed>}|null
     */
    protected ?array $apiSettings;

    /**
     * @var VariableFrontend
     */
    #[Flow\Inject]
    protected $apiCache;

    /**
     * @var array<string, mixed>
     */
    protected array $objectCache = [];

    /**
     * @var LoggerInterface
     */
    #[Flow\Inject]
    protected $logger;

    /**
     * Creates a valid cache identifier.
     */
    protected function getCacheKey(string $identifier): string
    {
        return sha1(self::class . '__' . $identifier);
    }

    protected function getItem(string $cacheKey): mixed
    {
        if (array_key_exists($cacheKey, $this->objectCache)) {
            return $this->objectCache[$cacheKey];
        }
        $item = $this->apiCache->get($cacheKey);
        $this->objectCache[$cacheKey] = $item;

        return $item;
    }

    /**
     * @param string[] $tags
     * @throws CacheException
     */
    protected function setItem(string $cacheKey, mixed $value, array $tags = []): void
    {
        $this->objectCache[$cacheKey] = $value;
        $this->apiCache->set($cacheKey, $value, $tags);
    }

    protected function unsetItem(string $cacheKey): void
    {
        unset($this->objectCache[$cacheKey]);
        $this->apiCache->remove($cacheKey);
    }

    /**
     * Retrieves data from the api.
     *
     * @param array<string, mixed> $additionalParameters
     * @return array<string|int, mixed>|false
     * @throws \JsonException|InfiniteRedirectionException
     */
    protected function fetchJsonData(string $actionName, array $additionalParameters = []): array|false
    {
        $browser = $this->getBrowser();
        $requestUri = $this->buildRequestUri($actionName, $additionalParameters);
        $response = $browser->request($requestUri);

        if ($response->getStatusCode() !== 200) {
            $this->logger->error(sprintf('Get request to Api failed with code "%s"!', $response->getStatusCode()),
                LogEnvironment::fromMethodName(__METHOD__));

            return false;
        }

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Json encodes data and posts it to the api
     *
     * @param array<string, mixed> $additionalParameters
     * @param array<string|int, mixed> $data
     */
    protected function postJsonData(string $actionName, array $additionalParameters, array $data): bool
    {
        $browser = $this->getBrowser();
        $browser->addAutomaticRequestHeader('Content-Type', 'application/json');
        $requestUri = $this->buildRequestUri($actionName, $additionalParameters);
        try {
            $response = $browser->request($requestUri, 'POST', [], [], [], json_encode($data));
        } catch (InfiniteRedirectionException) {
            return false;
        }

        if (!in_array($response->getStatusCode(), [200, 204])) {
            $this->logger->error(sprintf('Post request to Api failed with message "%s"!', $response->getStatusCode()),
                LogEnvironment::fromMethodName(__METHOD__));
            return false;
        }
        return true;
    }

    /**
     * Returns a browser instance with curlengine and authentication parameters set
     */
    protected function getBrowser(): Browser
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
     * @param array<string, mixed> $additionalParameters
     */
    protected function buildRequestUri(string $actionName, array $additionalParameters = []): Uri
    {
        $requestUri = new Uri($this->apiSettings['apiUrl']);
        $requestUri = $requestUri->withPath($requestUri->getPath() . $this->apiSettings['actions'][$actionName]);
        return $requestUri->withQuery(http_build_query(array_merge($this->apiSettings['parameters'], $additionalParameters)));
    }
}
