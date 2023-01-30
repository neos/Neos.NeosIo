<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 *
 * The file has been amended with handling of requests to the
 * sandstorm maps API to cache requests via a "proxy".
 */

use Neos\Cache\Backend\FileBackend;
use Neos\Cache\EnvironmentConfiguration;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Core\Bootstrap;

const MAPS_CACHE_LIFETIME = 24 * 60 * 60; // 24 hours
const MAPS_BASE_URI = 'https://maps-api.sandstorm.de/';

$rootPath = $_SERVER['FLOW_ROOTPATH'] ?? false;
if ($rootPath === false && isset($_SERVER['REDIRECT_FLOW_ROOTPATH'])) {
    $rootPath = $_SERVER['REDIRECT_FLOW_ROOTPATH'];
}
if ($rootPath === false) {
    $rootPath = __DIR__ . '/../';
} elseif (substr($rootPath, -1) !== '/') {
    $rootPath .= '/';
}

$composerAutoloader = require($rootPath . 'Packages/Libraries/autoload.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['REQUEST_URI'], '/_maptiles/') === 0) {
    proxyMap($_ENV['SANDSTORM_MAPS_API_KEY'] ?: 'unset');
    exit();
}

$context = Bootstrap::getEnvironmentConfigurationSetting('FLOW_CONTEXT') ?: 'Development';
$bootstrap = new Bootstrap($context, $composerAutoloader);
$bootstrap->run();

/**
 * Proxy requests to sandstorm map server
 */
function proxyMap(string $apiKey): void
{
    $environment = new EnvironmentConfiguration('maptiles-proxy', '/tmp/maptiles/');
    $backend = new FileBackend($environment);
    $cache = new StringFrontend('maptiles', $backend);
    $backend->setCache($cache);

    // identify request headers
    $requestHeaders = [];
    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $headerName = str_replace('_', ' ', substr($key, 5));
            $headerName = str_replace(' ', '-', ucwords(strtolower($headerName)));
            if (in_array($headerName, ['Accept-Encoding', 'Accept-Language', 'Accept',])) {
                $requestHeaders[] = "$headerName: $value";
            }
        }
    }

    // identify path from url
    preg_match('/^\/_maptiles\/(?P<path>.*)$/', $_SERVER['REQUEST_URI'], $matches);
    if ($matches['path']) {
        $requestUrl = MAPS_BASE_URI . $matches['path'] . '?t=' . $apiKey;
    } else {
        header('HTTP/1.1 404');
        die();
    }

    $cacheIdentifier = md5($requestUrl . implode('', $requestHeaders));
    $response = $cache->get($cacheIdentifier);

    if (!$response) {
        $curlHandle = curl_init($requestUrl);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $requestHeaders);   // (re-)send headers
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);     // return response
        curl_setopt($curlHandle, CURLOPT_HEADER, true);       // enabled response headers
        $response = curl_exec($curlHandle);
        curl_close($curlHandle);

        $cache->set($cacheIdentifier, $response, [], MAPS_CACHE_LIFETIME);
    }

    // split response to header and content
    [$responseHeaders, $responseContent] = preg_split('/(\r\n){2}/', $response, 2);

    // (re-)send the headers
    $responseHeaders = preg_split('/\r\n/', $responseHeaders);
    foreach ($responseHeaders as $responseHeader) {
        if (strpos($responseHeader, 'Transfer-Encoding:') !== 0) {
            header($responseHeader, false);
        }
    }

    // finally, output the content
    echo $responseContent;
}
