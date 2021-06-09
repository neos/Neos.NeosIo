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

use Neos\MarketPlace\Domain\Model\Slug;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeSearchServiceInterface;
use Neos\Neos\Exception as NeosException;
use Neos\Neos\Service\LinkingService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

/**
 * Package TypoScript Implementation
 *
 * @api
 */
class PackageUriImplementation extends AbstractFusionObject
{
    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @Flow\Inject
     * @var NodeSearchServiceInterface
     */
    protected $nodeSearchService;

    /**
     * @return string
     */
    public function getPackageKey(): string
    {
        return $this->fusionValue('packageKey');
    }

    /**
     * @return NodeInterface
     */
    public function getNode(): NodeInterface
    {
        return $this->fusionValue('node');
    }

    /**
     * @return string The rendered URI or NULL if no URI could be resolved for the given node
     * @throws NeosException
     * @throws \Neos\Flow\Http\Exception
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function evaluate(): string
    {
        $packageKey = $this->getPackageKey();
        $packageKeyParts = explode('-', $packageKey);
        if (isset($packageKeyParts[0]) && $packageKeyParts[0] === 'ext' && isset($packageKeyParts[1])) {
            return sprintf('http://php.net/manual-lookup.php?pattern=%s&scope=quickref', urlencode($packageKeyParts[1]));
        }
        $title = Slug::create($packageKey);
        $packageNodes = $this->nodeSearchService->findByProperties(['uriPathSegment' => $title], ['Neos.MarketPlace:Package'], $this->getNode()->getContext());
        $packageNode = reset($packageNodes);
        if ($packageNode) {
            return $this->linkingService->createNodeUri(
                $this->runtime->getControllerContext(),
                $packageNode,
                $this->getNode()
            );
        }
        return 'https://packagist.org/packages/' . $this->getPackageKey();
    }
}
