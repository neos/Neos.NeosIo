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
    #[Flow\InjectConfiguration('typeMapping')]
    protected array $packageTypes;

    #[Flow\InjectConfiguration("compatibilityCheck")]
    protected array $compatibilityCheck;

    #[Flow\Inject]
    protected Storage $storage;

    public function packageTypeMapping(?string $packageType): string
    {
        if ($packageType === null) {
            return '[null]';
        }
        return (string)($this->packageTypes[$packageType] ?? $packageType);
    }

    public function prepareVersion(?Node $versionNode = null): array
    {
        if ($versionNode === null) {
            return [];
        }
        /** @var \DateTime|null $time */
        $time = $versionNode->getProperty('time');
        return [
            'name' => $versionNode->getProperty('name'),
            'description' => $versionNode->getProperty('description'),
            'keywords' => $this->trimExplode($versionNode->getProperty('keywords')),
            'homepage' => $versionNode->getProperty('homepage'),
            'version' => $versionNode->getProperty('version'),
            'versionNormalized' => $versionNode->getProperty('versionNormalized'),
            'stability' => $versionNode->getProperty('stability'),
            'stabilityLevel' => $versionNode->getProperty('stabilityLevel'),
            'time' => $time?->format('Y-m-d\TH:i:sP'),
            'timestamp' => $time ? $time->getTimestamp() : 0,
        ];
    }

    /**
     * @param Node[] $versionNodes
     * @return string[]
     */
    public function extractCompatibility(array $versionNodes = [], ?string $packageName = null): array
    {
        if (!$versionNodes || !array_key_exists($packageName, $this->compatibilityCheck)) {
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

    public function extractMaintainers(Node $packageNode): array
    {
        $data = [];
        $maintainerNodes = $this->storage->getPackageMaintainerNodes($packageNode->aggregateId);
        foreach ($maintainerNodes as $maintainerNode) {
            /** @var Node $maintainerNode */
            $data[] = [
                'name' => $maintainerNode->getProperty('title'),
                'email' => $maintainerNode->getProperty('email'),
                'homepage' => $maintainerNode->getProperty('homepage')
            ];
        }

        return $data;
    }

    /**
     * @return string[]
     */
    public function trimExplode(?string $value): array
    {
        return array_filter(array_map('trim', explode(',', (string)$value)));
    }

}
