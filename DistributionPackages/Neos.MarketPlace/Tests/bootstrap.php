<?php

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

$rootPath = dirname(__DIR__, 3);
$autoloadFile = $rootPath . '/Packages/Libraries/autoload.php';

if (!file_exists($autoloadFile)) {
    fwrite(STDERR, PHP_EOL . 'Neos.MarketPlace unit test bootstrap error: The composer autoloader was not found at "' . $autoloadFile . '". Please run "composer install" first.' . PHP_EOL);
    exit(1);
}

require_once($autoloadFile);

// The test classes are not part of the composer autoloader, so they are registered manually
spl_autoload_register(static function (string $className): void {
    $prefix = 'Neos\\MarketPlace\\Tests\\Unit\\';
    if (!str_starts_with($className, $prefix)) {
        return;
    }
    $file = __DIR__ . '/Unit/' . str_replace('\\', '/', substr($className, strlen($prefix))) . '.php';
    if (file_exists($file)) {
        require_once($file);
    }
});
