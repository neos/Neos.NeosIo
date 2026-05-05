<?php
declare(strict_types=1);

namespace Neos\NeosIo\Command;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\NodeVariation\Exception\DimensionSpacePointIsAlreadyOccupied;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints;
use Neos\Neos\Ui\Domain\Model\Feedback\Operations\UpdateWorkspaceInfo;
use Shel\Neos\Terminal\Command\TerminalCommandInterface;
use Shel\Neos\Terminal\Domain\CommandContext;
use Shel\Neos\Terminal\Domain\CommandInvocationResult;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;

class SyncNodeCommand implements TerminalCommandInterface
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    #[Flow\Inject]
    protected ObjectManagerInterface $objectManager;

    public static function getCommandName(): string
    {
        return 'syncNode';
    }

    public static function getCommandDescription(): string
    {
        return 'Reports nodes whose German (de) dimension variant is missing. Pass --sync to recreate them.';
    }

    public static function getCommandUsage(): string
    {
        return 'syncNode ' . self::getInputDefinition()->getSynopsis();
    }

    public static function getInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('node', InputArgument::OPTIONAL),
            new InputOption('sync', null, InputOption::VALUE_NONE, 'Actually create the missing German variants instead of just reporting them.'),
        ]);
    }

    public function invokeCommand(string $argument, CommandContext $commandContext): CommandInvocationResult
    {
        $input = new StringInput($argument);
        $input->bind(self::getInputDefinition());

        try {
            $input->validate();
        } catch (RuntimeException $e) {
            return new CommandInvocationResult(false, $e->getMessage());
        }

        // Use the focused node as root, falling back to the document node.
        $rootNode = $commandContext->getFocusedNode() ?? $commandContext->getDocumentNode();

        if (!$rootNode) {
            return new CommandInvocationResult(false, 'No node is selected. Please focus a node first.');
        }

        // Ensure the node is in the English (source) dimension
        $nodeDimension = $rootNode->dimensionSpacePoint->coordinates['language'] ?? null;
        if ($nodeDimension !== 'en') {
            return new CommandInvocationResult(
                false,
                sprintf('The selected node is in dimension "%s". Please select a node in the English (en) dimension.', $nodeDimension ?? 'unknown')
            );
        }

        $contentRepository = $this->contentRepositoryRegistry->get(
            ContentRepositoryId::fromString('default')
        );

        $workspaceName = $rootNode->workspaceName;
        $contentGraph = $contentRepository->getContentGraph($workspaceName);

        $enDimensionSpacePoint = DimensionSpacePoint::fromArray(['language' => 'en']);
        $deDimensionSpacePoint = DimensionSpacePoint::fromArray(['language' => 'de']);

        $enSubgraph = $contentGraph->getSubgraph($enDimensionSpacePoint, NeosVisibilityConstraints::excludeRemoved());
        $deSubgraph = $contentGraph->getSubgraph($deDimensionSpacePoint, NeosVisibilityConstraints::excludeRemoved());

        $enOrigin = OriginDimensionSpacePoint::fromDimensionSpacePoint($enDimensionSpacePoint);
        $deOrigin = OriginDimensionSpacePoint::fromDimensionSpacePoint($deDimensionSpacePoint);

        $createdCount = 0;
        $alreadyExistsCount = 0;
        $shineThroughCount = 0;
        $missingLabels = [];
        $shineThroughLabels = [];
        $deOnlyLabels = [];
        $errorMessages = [];
        $isSyncMode = (bool)$input->getOption('sync');

        $nodeTypeManager = $contentRepository->getNodeTypeManager();

        // Collect all nodes to check: the root node itself + all non-document descendants
        $nodesToCheck = $this->collectAllNodes($rootNode, $enSubgraph, $nodeTypeManager);

        foreach ($nodesToCheck as $node) {
            // Skip the root node itself — we only want to sync content children
            if ($node->aggregateId->equals($rootNode->aggregateId)) {
                continue;
            }

            $deVariant = $deSubgraph->findNodeById($node->aggregateId);

            if ($deVariant !== null) {
                $deOriginLanguage = $deVariant->originDimensionSpacePoint->coordinates['language'] ?? null;
                if ($deOriginLanguage === 'de') {
                    // A real DE variant exists — nothing to do.
                    $alreadyExistsCount++;
                } else {
                    // Found in DE subgraph but originates from EN — this node is shining through.
                    // EN changes are automatically reflected in DE. No CreateNodeVariant needed.
                    $shineThroughCount++;
                    if (!$isSyncMode) {
                        $shineThroughLabels[] = sprintf('%s (%s)', $this->nodeLabelGenerator->getLabel($node), $node->nodeTypeName->value);
                    }
                }
                continue;
            }

            // Node is completely absent from DE — DE hierarchy relation is missing.
            // CreateNodeVariant is the only way to restore it; this will create an independent DE copy.
            $label = sprintf('%s (%s)', $this->nodeLabelGenerator->getLabel($node), $node->nodeTypeName->value);

            if (!$isSyncMode) {
                $missingLabels[] = $label;
                continue;
            }

            try {
                $contentRepository->handle(
                    CreateNodeVariant::create(
                        $workspaceName,
                        $node->aggregateId,
                        $enOrigin,
                        $deOrigin,
                    )
                );
                $createdCount++;
            } catch (DimensionSpacePointIsAlreadyOccupied) {
                // Already created implicitly as a tethered child when its parent was varied.
                $alreadyExistsCount++;
            } catch (\Throwable $e) {
                $errorMessages[] = sprintf('Failed to create DE variant for "%s": %s', $label, $e->getMessage());
            }
        }

        // Check for DE-only nodes: descendants in DE that have no EN counterpart
        $deRootNode = $deSubgraph->findNodeById($rootNode->aggregateId);
        if ($deRootNode !== null) {
            $deNodesToCheck = $this->collectAllNodes($deRootNode, $deSubgraph, $nodeTypeManager);
            foreach ($deNodesToCheck as $deNode) {
                if ($deNode->aggregateId->equals($rootNode->aggregateId)) {
                    continue;
                }
                $enCounterpart = $enSubgraph->findNodeById($deNode->aggregateId);
                if ($enCounterpart === null) {
                    $deOnlyLabels[] = sprintf('%s (%s)', $this->nodeLabelGenerator->getLabel($deNode), $deNode->nodeTypeName->value);
                }
            }
        }

        $lines = [];
        $rootLabel = sprintf('"%s" (%s)', $this->nodeLabelGenerator->getLabel($rootNode), $rootNode->nodeTypeName->value);

        if (!$isSyncMode) {
            $lines[] = sprintf('Dry run on node %s', $rootLabel);
            $lines[] = sprintf('Dry run — %d node(s) have a real DE variant, %d node(s) are shining through from EN.', $alreadyExistsCount, $shineThroughCount);
            if ($shineThroughLabels) {
                $lines[] = sprintf('%d node(s) are already shining through (EN changes auto-reflect in DE):', count($shineThroughLabels));
                foreach ($shineThroughLabels as $shineThroughLabel) {
                    $lines[] = '  ✦ ' . $shineThroughLabel;
                }
            }
            if ($missingLabels) {
                $lines[] = sprintf('%d node(s) are completely absent from DE (no hierarchy relation exists):', count($missingLabels));
                foreach ($missingLabels as $missingLabel) {
                    $lines[] = '  · ' . $missingLabel;
                }
                $lines[] = 'Run "syncNode --sync" to recreate them as independent DE copies.';
                $lines[] = 'Note: once created, EN changes will NOT automatically appear in DE for these nodes.';
            } else {
                $lines[] = 'No nodes are completely absent from DE.';
            }
            if ($deOnlyLabels) {
                $lines[] = sprintf('%d node(s) exist in DE but have no EN counterpart (orphaned DE-only nodes):', count($deOnlyLabels));
                foreach ($deOnlyLabels as $deOnlyLabel) {
                    $lines[] = '  ✗ ' . $deOnlyLabel;
                }
            } else {
                $lines[] = 'No orphaned DE-only nodes found.';
            }
        } else {
            $lines[] = sprintf('Sync run on node %s', $rootLabel);
            $lines[] = sprintf('✓ Created %d missing German variant(s) (independent DE copies).', $createdCount);
            $lines[] = sprintf('· %d node(s) already had a real DE variant, %d node(s) are shining through from EN.', $alreadyExistsCount, $shineThroughCount);
            if ($deOnlyLabels) {
                $lines[] = sprintf('⚠ %d node(s) exist in DE but have no EN counterpart (orphaned DE-only nodes):', count($deOnlyLabels));
                foreach ($deOnlyLabels as $deOnlyLabel) {
                    $lines[] = '  ✗ ' . $deOnlyLabel;
                }
            }
            if ($errorMessages) {
                $lines[] = sprintf('✗ %d error(s):', count($errorMessages));
                foreach ($errorMessages as $msg) {
                    $lines[] = '  ' . $msg;
                }
            }
        }

        $success = empty($errorMessages);

        $uiFeedback = [];
        if ($isSyncMode) {
            $uiFeedback[] = new UpdateWorkspaceInfo(
                $rootNode->contentRepositoryId,
                $workspaceName,
            );
        }

        return new CommandInvocationResult($success, implode("\n", $lines), $uiFeedback);
    }

    /**
     * Recursively collects the given node and all its descendant nodes from the EN subgraph,
     * skipping any nodes of type Neos.Neos:Document (and their subtrees).
     *
     * @param list<Node> $carry
     * @return list<Node>
     */
    private function collectAllNodes(Node $node, ContentSubgraphInterface $enSubgraph, NodeTypeManager $nodeTypeManager, array $carry = []): array
    {
        $carry[] = $node;

        $children = $enSubgraph->findChildNodes($node->aggregateId, FindChildNodesFilter::create());
        foreach ($children as $child) {
            $childNodeType = $nodeTypeManager->getNodeType($child->nodeTypeName);
            if ($childNodeType?->isOfType(NodeTypeName::fromString('Neos.Neos:Document'))) {
                continue;
            }
            $carry = $this->collectAllNodes($child, $enSubgraph, $nodeTypeManager, $carry);
        }

        return $carry;
    }
}
