<?php
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

use Neos\MarketPlace\Service\PackageVersion;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
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
     * @var PackageVersion
     * @Flow\Inject
     */
    protected $packageVersion;

    /**
     * @param string $packageType
     * @return string
     */
    public function packageTypeMapping($packageType)
    {
        if (isset($this->packageTypes[$packageType])) {
            return $this->packageTypes[$packageType];
        }
        return $packageType;
    }

    /**
     * @param NodeInterface $node
     * @return array
     */
    public function extractVersions(NodeInterface $node)
    {
        $data = [];
        $versions = $this->packageVersion->extractVersions($node);

        /** @var NodeInterface $versionNode */
        foreach ($versions as $versionNode) {
            $data[] = $this->prepareVersion($versionNode);
        }

        return $data;
    }

    /**
     * @param NodeInterface $versionNode
     * @return array
     */
    public function prepareVersion(NodeInterface $versionNode = null)
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
     * @param NodeInterface $node
     * @return array
     */
    public function extractMaintainers(NodeInterface $node)
    {
        $data = [];
        $query = new FlowQuery([$node]);
        $query = $query
            ->find('maintainers')
            ->find('[instanceof Neos.MarketPlace:Maintainer]');

        foreach ($query as $maintainerNode) {
            /** @var NodeInterface $maintainerNode */
            $data[] = [
                'name' => $maintainerNode->getProperty('title'),
                'email' => $maintainerNode->getProperty('email'),
                'homepage' => $maintainerNode->getProperty('homepage')
            ];
        }

        return $data;
    }

    /**
     * @param string $value
     * @return array
     */
    public function trimExplode($value)
    {
        return array_filter(array_map('trim', explode(',', $value)));
    }

}
