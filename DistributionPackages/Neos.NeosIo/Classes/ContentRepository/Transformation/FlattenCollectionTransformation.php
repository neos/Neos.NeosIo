<?php
declare(strict_types=1);

namespace Neos\NeosIo\ContentRepository\Transformation;

use Neos\ContentRepository\Core\CommandHandler\Commands;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeMove\Command\MoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeMove\Dto\RelationDistributionStrategy;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\NodeMigration\Transformation\GlobalTransformationInterface;
use Neos\ContentRepository\NodeMigration\Transformation\NodeAggregateBasedTransformationInterface;
use Neos\ContentRepository\NodeMigration\Transformation\NodeBasedTransformationInterface;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationFactoryInterface;
use Neos\ContentRepository\NodeMigration\Transformation\TransformationStep;
use Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints;

class FlattenCollectionTransformation implements TransformationFactoryInterface
{
    /**
     * @param array{collectionNodeName: string} $settings
     */
    public function build(
        array             $settings,
        ContentRepository $contentRepository,
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface
    {
        return new class (
            $settings['collectionNodeName'],
            $contentRepository,
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                private readonly string            $collectionNodeName,
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
                $subgraph = $this->contentRepository
                    ->getContentGraph($node->workspaceName)
                    ->getSubgraph(
                        $node->dimensionSpacePoint,
                        NeosVisibilityConstraints::excludeRemoved()
                    );
                $collectionNode = $subgraph->findNodeByPath(
                    NodeName::fromString($this->collectionNodeName),
                    $node->aggregateId
                );

                if (!$collectionNode) {
                    return TransformationStep::createEmpty();
                }

                $commands = Commands::fromArray($subgraph->findChildNodes(
                    $collectionNode->aggregateId,
                    FindChildNodesFilter::create()
                )->map(function (Node $childNode) use ($node, $workspaceNameForWriting) {
                    return MoveNodeAggregate::create(
                        $workspaceNameForWriting,
                        $node->dimensionSpacePoint,
                        $childNode->aggregateId,
                        RelationDistributionStrategy::STRATEGY_GATHER_ALL,
                        $node->aggregateId
                    );
                }));

                return TransformationStep::fromCommands($commands);
            }
        };
    }
}
