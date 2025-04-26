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
use Neos\MarketPlace\Service\PackageVersion;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Search\Eel;

/**
 * IndexingHelper
 */
class IndexingHelper extends Eel\IndexingHelper
{
    /**
     * @var array
     * @Flow\InjectConfiguration(path="typeMapping")
     */
    protected $packageTypes;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="compatibilityCheck")
     */
    protected $compatibilityCheck;

    /**
     * @var PackageVersion
     * @Flow\Inject
     */
    protected $packageVersion;

    /**
     * @param string|null $packageType
     * @return string
     */
    public function packageTypeMapping(?string $packageType): string
    {
        if ($packageType === null) {
            return '[null]';
        }
        return (string)($this->packageTypes[$packageType] ?? $packageType);
    }

    /**
     * @param Node $node
     * @return array
     * @throws \Neos\Eel\Exception
     */
    public function extractVersions(Node $node): array
    {
        $data = [];
        /** @var Node[] $versions */
        $versions = $this->packageVersion->extractVersions($node);

        foreach ($versions as $versionNode) {
            $data[] = $this->prepareVersion($versionNode);
        }

        return $data;
    }

    /**
     * @param Node|null $versionNode
     * @return array
     */
    public function prepareVersion(?Node $versionNode = null): array
    {
        if ($versionNode === null) {
            return [];
        }
        /** @var \DateTime $time */
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
            'time' => $time ? $time->format('Y-m-d\TH:i:sP') : null,
            'timestamp' => $time ? $time->getTimestamp() : 0,
        ];
    }

    /**
     * @param array<Node> $versionNodes
     * @return array<string>
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
            } catch (\Exception $e) {
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
                } catch (\Exception $e) {
                    // Exceptions can be thrown on strings like "self.version"
                    // might be looked at more closely
                    continue;
                }
            }
        }

        return array_values(array_unique($compatibleVersions));
    }

    /**
     * @param Node $node
     * @return array
     * @throws \Neos\Eel\Exception
     */
    public function extractMaintainers(Node $node): array
    {
        $data = [];
        $query = new FlowQuery([$node]);
        $query = $query
            ->find('maintainers')
            ->find('[instanceof Neos.MarketPlace:Maintainer]');

        foreach ($query as $maintainerNode) {
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
     * @param string|null $value
     * @return array
     */
    public function trimExplode(?string $value): array
    {
        return array_filter(array_map('trim', explode(',', (string)$value)));
    }

}
