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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\MarketPlace\Exception;

/**
 * Storage
 *
 * @api
 */
class Storage
{
//    /**
//     * @var \Neos\ContentRepository\Core\NodeType\NodeTypeManager
//     * @Flow\Inject
//     */
//    protected $nodeTypeManager;

    /**
     * @var array
     * @Flow\InjectConfiguration(path="repository")
     */
    protected $repository;

    protected string $workspaceName;

    /**
     * @var Node
     */
    protected $node;

    public function __construct(string $workspaceName = 'live')
    {
        $this->workspaceName = $workspaceName;
    }

    /**
     * @throws Exception
     */
    public function node(): Node
    {
        if ($this->node === null) {
            // TODO 9.0 migration: We need to use the ContentRepository Registry here to fetch the node by Identifier
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
    public function createVendor(string $vendor): Node
    {
        $vendor = Slug::create($vendor);
        $node = $this->node()->getNode($vendor);

        if ($node === null) {
            // TODO 9.0 migration: Creation of nodes needs to be rewitten to Commands.
            $node = $this->node()->createNode($vendor, $this->nodeTypeManager->getNodeType('Neos.MarketPlace:Vendor'));
            $node->setProperty('uriPathSegment', $vendor);
            $node->setProperty('title', $vendor);
        }

        return $node;
    }

    /**
     * Creates a content context for given workspace and language identifiers
     *
     * @return \Neos\Rector\ContentRepository90\Legacy\LegacyContextStub|\Neos\Rector\ContentRepository90\Legacy\LegacyContextStub
     */
    protected function createContext(string $workspaceName): \Neos\Rector\ContentRepository90\Legacy\LegacyContextStub
    {
        // TODO 9.0 migration: Context does not exist anymore.
        $contextProperties = [
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        ];

        return new \Neos\Rector\ContentRepository90\Legacy\LegacyContextStub($contextProperties);
    }
}
