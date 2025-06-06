<?php
declare(strict_types=1);

namespace Neos\NeosIo\Fusion;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\FrontendRouting\DimensionResolution\DimensionResolverFactoryInterface;
use Neos\Neos\FrontendRouting\DimensionResolution\RequestToDimensionSpacePointContext;

class ExtractDimensionsImplementation extends AbstractFusionObject
{
    #[Flow\Inject()]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject()]
    protected ObjectManagerInterface $objectManager;

    #[Flow\Inject()]
    protected SiteRepository $siteRepository;

    public function evaluate()
    {
        /** @var Node $siteNode */
        $siteNode = $this->fusionValue('site');

        $site = $this->siteRepository->findOneByNodeName(SiteNodeName::fromNodeName($siteNode->name));
        $siteConfiguration = $site->getConfiguration();

        /** @var DimensionResolverFactoryInterface $factory */
        $factory = $this->objectManager->get($siteConfiguration->contentDimensionResolverFactoryClassName);
        $contentDimensionResolver = $factory->create(ContentRepositoryId::fromString('default'), $siteConfiguration);

        $requestToDimensionSpacepointContext = $contentDimensionResolver->fromRequestToDimensionSpacePoint(
            RequestToDimensionSpacePointContext::fromUriPathAndRouteParametersAndResolvedSite(
                $this->fusionValue('uriPath'),
                RouteParameters::createEmpty(),
                $site
            )
        );

        return $requestToDimensionSpacepointContext->resolvedDimensionSpacePoint->toLegacyDimensionArray();
    }
}

