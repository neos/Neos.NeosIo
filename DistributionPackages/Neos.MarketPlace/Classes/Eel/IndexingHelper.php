<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Eel;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Composer\Semver\Semver;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Search\Eel;
use Neos\Flow\Annotations as Flow;
use Neos\MarketPlace\Domain\Model\Storage;

/**
 * IndexingHelper
 */
class IndexingHelper extends Eel\IndexingHelper
{
    /**
     * @var array<string, string>
     */
    #[Flow\InjectConfiguration('typeMapping')]
    protected array $packageTypes;

    /**
     * @var array<string, string[]>
     */
    #[Flow\InjectConfiguration("compatibilityCheck")]
    protected array $compatibilityCheck;

    #[Flow\Inject]
    protected Storage $storage;

    public function packageTypeMapping(?string $packageType): string
    {
        if ($packageType === null) {
            return '[null]';
        }
        return $this->packageTypes[$packageType] ?? $packageType;
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareVersion(?Node $versionNode = null): array
    {
        if ($versionNode === null) {
            return [];
        }
        return $this->buildVersionData($versionNode);
    }

    /**
     * Returns the data of the last version that was calculated during the import.
     *
     * @return array<string, mixed>
     */
    public function prepareVersionData(?string $versionData = null): array
    {
        if (!$versionData) {
            return [];
        }
        $decoded = json_decode($versionData, true);
        if (!is_array($decoded)) {
            return [];
        }
        return $decoded;
    }

    /**
     * Returns the compatibility that was calculated during the import.
     *
     * @return string[]
     */
    public function prepareCompatibility(?string $compatibility = null): array
    {
        if (!$compatibility) {
            return [];
        }
        $decoded = json_decode($compatibility, true);
        if (!is_array($decoded)) {
            return [];
        }
        return array_values(array_map('strval', $decoded));
    }

    /**
     * @param Node[] $versionNodes
     * @return string[]
     */
    public function extractCompatibility(array $versionNodes = [], ?string $packageName = null): array
    {
        if (!$versionNodes || !$packageName || !array_key_exists($packageName, $this->compatibilityCheck)) {
            return [];
        }

        $compatibleVersions = [];
        foreach ($versionNodes as $versionNode) {
            $requireJson = $versionNode->getProperty('require');

            if (!$requireJson) {
                continue;
            }

            try {
                $require = json_decode($requireJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Exception) {
                continue;
            }

            if (!array_key_exists($packageName, $require)) {
                continue;
            }

            foreach ($this->compatibilityCheck[$packageName] as $version) {
                try {
                    if (Semver::satisfies($version, $require[$packageName])) {
                        $compatibleVersions[]= $version;
                    }
                } catch (\Exception) {
                    // Exceptions can be thrown on strings like "self.version"
                    // might be looked at more closely
                    continue;
                }
            }
        }

        return array_values(array_unique($compatibleVersions));
    }

    /**
     * @return string[]
     */
    public function trimExplode(?string $value): array
    {
        return array_filter(array_map('trim', explode(',', (string)$value)));
    }

    /**
     * Builds the data of a version that is stored in the search index.
     *
     * @param Node|array<string, mixed>|null $version Either a version node or the properties of a version
     * @return array<string, mixed>
     */
    public function buildVersionData(Node|array|null $version): array
    {
        if ($version === null) {
            return [];
        }
        if ($version instanceof Node) {
            $version = [
                'description' => $version->getProperty('description'),
                'keywords' => $version->getProperty('keywords'),
                'homepage' => $version->getProperty('homepage'),
                'version' => $version->getProperty('version'),
                'versionNormalized' => $version->getProperty('versionNormalized'),
                'stability' => $version->getProperty('stability'),
                'stabilityLevel' => $version->getProperty('stabilityLevel'),
                'time' => $version->getProperty('time'),
            ];
        }

        $time = $version['time'] ?? null;
        return [
            'description' => $version['description'] ?? null,
            'keywords' => $this->trimExplode(is_string($version['keywords'] ?? null) ? $version['keywords'] : null),
            'homepage' => $version['homepage'] ?? null,
            'version' => $version['version'] ?? null,
            'versionNormalized' => $version['versionNormalized'] ?? null,
            'stability' => $version['stability'] ?? null,
            'stabilityLevel' => $version['stabilityLevel'] ?? null,
            'time' => $time instanceof \DateTimeInterface ? $time->format('Y-m-d\TH:i:sP') : null,
            'timestamp' => $time instanceof \DateTimeInterface ? $time->getTimestamp() : 0,
        ];
    }

}
