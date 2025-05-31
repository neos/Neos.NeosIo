<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Domain\Model;

enum MarketplaceNodeType: string
{
    case PACKAGE = 'Neos.MarketPlace:Package';
    case VENDOR = 'Neos.MarketPlace:Vendor';
    case MAINTAINER = 'Neos.MarketPlace:Maintainer';
    case VERSION = 'Neos.MarketPlace:Version';
    case VERSION_STABLE = 'Neos.MarketPlace:ReleasedVersion';
    case VERSION_DEV = 'Neos.MarketPlace:DevelopmentVersion';
    case VERSION_UNSTABLE = 'Neos.MarketPlace:PrereleasedVersion';
}
