<?php
declare(strict_types=1);

/**
 * This php config automatically adds paths for all packages in the DistributionPackages folder to the
 * phpstan configuration which is not possible in yaml / neon.
 */

// Find all package folders with a classes folder and add them to the list of included paths
$paths = [];
foreach (glob(__DIR__ . '/DistributionPackages/*', GLOB_ONLYDIR) as $packageDir) {
    $classesPath = "$packageDir/Classes";
    if (is_dir($classesPath)) {
        $paths[] = $classesPath;
    }
}

return [
    'parameters' => [
        'paths' => $paths,
    ],
];
