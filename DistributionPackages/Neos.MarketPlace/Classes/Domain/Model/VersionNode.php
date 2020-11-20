<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Domain\Model;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Node;

/**
 * VersionNode
 *
 * @api
 */
class VersionNode extends Node
{
    /**
     * @return integer
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function getVersionNormalized(): int
    {
        return $this->getProperty('versionNormalized');
    }

    /**
     * @return integer
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function getVersion(): int
    {
        return $this->getProperty('version');
    }

    /**
     * @return integer
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function getStability(): int
    {
        return $this->getProperty('stability');
    }

    /**
     * @return \DateTime
     * @throws \Neos\ContentRepository\Exception\NodeException
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function getLastActivity(): \DateTime
    {
        return $this->getProperty('time');
    }
}
