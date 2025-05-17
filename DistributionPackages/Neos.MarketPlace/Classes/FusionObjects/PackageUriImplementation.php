<?php
declare(strict_types=1);

namespace Neos\MarketPlace\FusionObjects;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\ServerRequest;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\MarketPlace\Domain\Model\Slug;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Package TypoScript Implementation
 *
 * @api
 */
class PackageUriImplementation extends AbstractFusionObject
{
    #[Flow\Inject]
    protected NodeUriBuilderFactory $nodeUriBuilderFactory;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function getPackageKey(): string
    {
        return $this->fusionValue('packageKey');
    }

    public function getNode(): Node
    {
        return $this->fusionValue('node');
    }

    /**
     * @return string The rendered URI or NULL if no URI could be resolved for the given node
     * @throws NoMatchingRouteException
     */
    public function evaluate(): string
    {
        $packageKey = $this->getPackageKey();
        $packageKeyParts = explode('-', $packageKey);
        if (isset($packageKeyParts[0], $packageKeyParts[1]) && $packageKeyParts[0] === 'ext') {
            return sprintf('https://php.net/manual-lookup.php?pattern=%s&scope=quickref', urlencode($packageKeyParts[1]));
        }
        $title = Slug::create($packageKey);

        $node = $this->getNode();
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        $packageNodes = $subgraph->findDescendantNodes($node->aggregateId, FindDescendantNodesFilter::create(nodeTypes: NodeTypeCriteria::create(NodeTypeNames::fromStringArray(['Neos.MarketPlace:Package']), NodeTypeNames::createEmpty()), propertyValue: PropertyValueEquals::create(PropertyName::fromString('uriPathSegment'), $title, false)));
        $packageNode = $packageNodes->first();
        if ($packageNode) {
            $possibleRequest = $this->runtime->fusionGlobals->get('request');
            if ($possibleRequest instanceof ActionRequest) {
                $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest($possibleRequest);
            } else {
                // unfortunately, the uri-builder always needs a request at hand and cannot build uris without it
                // this will improve with a reformed uri building:
                // https://github.com/neos/flow-development-collection/issues/3354
                $nodeUriBuilder = $this->nodeUriBuilderFactory->forActionRequest(ActionRequest::fromHttpRequest(ServerRequest::fromGlobals()));
            }

            return (string)$nodeUriBuilder->uriFor(
                NodeAddress::fromNode($packageNode)
            );
        }
        return 'https://packagist.org/packages/' . $this->getPackageKey();
    }
}
