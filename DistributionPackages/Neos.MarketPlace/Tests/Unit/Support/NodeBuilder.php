<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Tests\Unit\Support;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Symfony\Component\Serializer\Serializer;

/**
 * Builds content repository nodes for unit tests.
 */
final class NodeBuilder
{
    /**
     * @param array<string, array{type: string, value: mixed}> $properties
     */
    public static function createNode(
        array $properties = [],
        string $nodeTypeName = 'Neos.MarketPlace:Package',
        ?NodeAggregateId $aggregateId = null,
        string $nodeName = 'package'
    ): Node {
        $dimensionSpacePoint = DimensionSpacePoint::fromArray(['language' => 'en']);
        $now = new \DateTimeImmutable('2026-04-01 12:00:00', new \DateTimeZone('UTC'));

        return Node::create(
            ContentRepositoryId::fromString('default'),
            WorkspaceName::fromString('live'),
            $dimensionSpacePoint,
            $aggregateId ?? NodeAggregateId::fromString('1a1e0e4c-0000-0000-0000-000000000001'),
            OriginDimensionSpacePoint::fromDimensionSpacePoint($dimensionSpacePoint),
            NodeAggregateClassification::CLASSIFICATION_REGULAR,
            NodeTypeName::fromString($nodeTypeName),
            new PropertyCollection(
                SerializedPropertyValues::fromArray($properties),
                new PropertyConverter(new TestSerializer())
            ),
            NodeName::fromString($nodeName),
            NodeTags::createEmpty(),
            Timestamps::create($now, $now, null, null),
            VisibilityConstraints::createEmpty()
        );
    }
}
