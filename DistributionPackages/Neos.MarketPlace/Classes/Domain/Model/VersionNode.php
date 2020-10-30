<?php
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
     */
    public function getVersionNormalized()
    {
        return $this->getProperty('versionNormalized');
    }

    /**
     * @return integer
     */
    public function getVersion()
    {
        return $this->getProperty('version');
    }

    /**
     * @return integer
     */
    public function getStability()
    {
        return $this->getProperty('stability');
    }

    /**
     * @return \DateTime
     */
    public function getLastActivity()
    {
        return $this->getProperty('time');
    }

}
