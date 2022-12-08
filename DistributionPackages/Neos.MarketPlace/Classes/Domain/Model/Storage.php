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

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\Flow\Annotations as Flow;
use Neos\MarketPlace\Exception;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\ContentContextFactory;

/**
 * Storage
 *
 * @api
 */
class Storage
{
    /**
     * @var ContentContextFactory
     * @Flow\Inject
     */
    protected $contextFactory;

    /**
     * @var NodeTypeManager
     * @Flow\Inject
     */
    protected $nodeTypeManager;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="repository")
     */
    protected $repository;

    protected string $workspaceName;

    /**
     * @var NodeInterface
     */
    protected $node;

    public function __construct(string $workspaceName = 'live')
    {
        $this->workspaceName = $workspaceName;
    }

    /**
     * @throws Exception
     */
    public function node(): NodeInterface
    {
        if ($this->node === null) {
            $context = $this->createContext($this->workspaceName);
            $this->node = $context->getNodeByIdentifier($this->repository['identifier']);
            if ($this->node === null) {
                throw new Exception('Repository node not found', 1457507995);
            }
        }
        return $this->node;
    }

    /**
     * @throws Exception
     * @throws NodeTypeNotFoundException
     */
    public function createVendor(string $vendor): NodeInterface
    {
        $vendor = Slug::create($vendor);
        $node = $this->node()->getNode($vendor);

        if ($node === null) {
            $node = $this->node()->createNode($vendor, $this->nodeTypeManager->getNodeType('Neos.MarketPlace:Vendor'));
            $node->setProperty('uriPathSegment', $vendor);
            $node->setProperty('title', $vendor);
        }

        return $node;
    }

    /**
     * Creates a content context for given workspace and language identifiers
     *
     * @return ContentContext|Context
     */
    protected function createContext(string $workspaceName): ContentContext
    {
        $contextProperties = [
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ];

        return $this->contextFactory->create($contextProperties);
    }
}
