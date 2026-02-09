<?php
declare(strict_types=1);

namespace Neos\NeosIo\Eel\Helper;

use Neos\ContentRepository\Core\DimensionSpace\AbstractDimensionSpacePoint;
use Neos\Eel\ProtectedContextAwareInterface;

class NodeHelper implements ProtectedContextAwareInterface
{

    /** @return array<string,array<int,string>> */
    public function dimensionSpacePointArray(AbstractDimensionSpacePoint $dimensionSpacePoint): array
    {
        return $dimensionSpacePoint->toLegacyDimensionArray();
    }

    /**
     * @param string $methodName
     */
    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
