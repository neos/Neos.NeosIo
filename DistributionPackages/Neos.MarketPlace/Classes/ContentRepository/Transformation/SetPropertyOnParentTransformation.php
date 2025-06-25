<?php
declare(strict_types=1);

namespace Neos\MarketPlace\ContentRepository\Transformation;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\NodeMigration\Transformation\GlobalTransformationInterface;
use Neos\ContentRepository\NodeMigration\Transformation\NodeAggregateBasedTransformationInterface;
use Neos\ContentRepository\NodeMigration\Transformation\NodeBasedTransformationInterface;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationFactoryInterface;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationStep;

class SetPropertyOnParentTransformation implements TransformationFactoryInterface
{
    /**
     * @param array{properties: array{ from: string, to: string }[]} $settings
     */
    public function build(
        array             $settings,
        ContentRepository $contentRepository,
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface
    {
        return new class (
            $settings['properties'],
            $contentRepository,
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                /**
                 * Property name to set on the parent node
                 * @var array{ from: string, to: string }[] $properties
                 */
                private readonly array             $properties,
                private readonly ContentRepository $contentRepository,
            )
            {
            }

            public function execute(
                Node                   $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                WorkspaceName          $workspaceNameForWriting
            ): TransformationStep
            {
                $properties = array_reduce(
                    $this->properties,
                    static function(array $properties, array $propertyMapping) use ($node) {
                        $propertyValue = $node->getProperty($propertyMapping['from']);
                        $properties[$propertyMapping['to']] = $propertyValue;
                        return $properties;
                    },
                    []
                );
                if (!$properties) {
                    return TransformationStep::createEmpty();
                }
                $parentNode = $this->contentRepository
                    ->getContentGraph($node->workspaceName)
                    ->findParentNodeAggregateByChildOriginDimensionSpacePoint(
                        $node->aggregateId,
                        $node->originDimensionSpacePoint
                    );
                if (!$parentNode) {
                    return TransformationStep::createEmpty();
                }
                return TransformationStep::fromCommand(
                    SetNodeProperties::create(
                        $workspaceNameForWriting,
                        $parentNode->nodeAggregateId,
                        $node->originDimensionSpacePoint,
                        PropertyValuesToWrite::fromArray($properties),
                    )
                );
            }
        };
    }
}
