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

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Command\SetNodeReferences;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesForName;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Dto\NodeReferencesToWrite;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Command\ChangeNodeAggregateType;
use Neos\ContentRepository\Core\Feature\NodeTypeChange\Dto\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\ContentRepository\Core\Feature\Security\Exception\AccessDenied;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\PropertyValue\Criteria\PropertyValueEquals;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\EventStore\Exception\ConcurrencyException;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\WorkspacePublishingService;
use Neos\Neos\Domain\SubtreeTagging\NeosVisibilityConstraints;
use Packagist\Api\Result\Package;
use Packagist\Api\Result\Package\Maintainer;
use Psr\Log\LoggerInterface;

/**
 * Storage
 *
 * @api
 */
#[Flow\Scope('singleton')]
class Storage
{

    public const string DATE_FORMAT = 'Y-m-d\TH:i:sO';

    /**
     * @var LoggerInterface
     */
    #[Flow\Inject('Neos.MarketPlace:Logger')]
    protected $logger;

    #[Flow\InjectConfiguration('repository.identifier')]
    protected string $repositoryIdentifier;

    protected ?NodeAggregateId $storageRootNodeAggregateId = null;

    protected ContentRepository $contentRepository;

    protected WorkspaceName $workspaceName;

    protected ContentSubgraphInterface $subGraph;

    /**
     * @var array<string, NodeAggregateId>
     */
    protected array $vendorCache = [];

    public function __construct(
        protected ContentRepositoryRegistry  $contentRepositoryRegistry,
        protected WorkspacePublishingService $workspacePublishingService,
    )
    {
        $this->workspaceName = WorkspaceName::fromString('live');
    }

    public function getNodeByAggregateId(
        NodeAggregateId $vendorNodeAggregateId
    ): ?Node
    {
        return $this->subGraph->findNodeById($vendorNodeAggregateId);
    }

    /**
     * @throws AccessDenied
     */
    protected function initializeObject(): void
    {
        $this->contentRepository = $this->contentRepositoryRegistry->get(
            ContentRepositoryId::fromString('default')
        );
        $this->subGraph = $this->contentRepository->getContentGraph($this->workspaceName)->getSubgraph(
            DimensionSpacePoint::fromArray(['language' => 'en']),
            NeosVisibilityConstraints::excludeRemoved()
        );
        $this->storageRootNodeAggregateId = NodeAggregateId::fromString($this->repositoryIdentifier);
    }

    /**
     * Returns the vendor node aggregate id for the given vendor name.
     * If the vendor node does not exist, it will be created.
     */
    public function getOrCreateVendorNode(string $vendorName): ?NodeAggregateId
    {
        if (!$this->storageRootNodeAggregateId) {
            return null;
        }
        if (array_key_exists($vendorName, $this->vendorCache)) {
            return $this->vendorCache[$vendorName];
        }
        $vendorName = Slug::create($vendorName);

        // Find the vendor node by name
        $node = $this->subGraph->findChildNodes(
            $this->storageRootNodeAggregateId,
            FindChildNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::fromStringArray([MarketplaceNodeType::VENDOR->value]
                    )),
                propertyValue: PropertyValueEquals::create(
                    PropertyName::fromString('title'),
                    $vendorName,
                    true
                )
            )
        )->first();

        if ($node) {
            $this->vendorCache[$vendorName] = $node->aggregateId;
            return $node->aggregateId;
        }

        $vendorNodeAggregateId = NodeAggregateId::create();
        if (!$this->handleCommandWithRetry(
            CreateNodeAggregateWithNode::create(
                $this->workspaceName,
                $vendorNodeAggregateId,
                NodeTypeName::fromString(MarketplaceNodeType::VENDOR->value),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($this->subGraph->getDimensionSpacePoint()),
                $this->storageRootNodeAggregateId,
                initialPropertyValues: PropertyValuesToWrite::fromArray([
                    'uriPathSegment' => $vendorName,
                    'title' => $vendorName,
                ]),
            )
        )) {
            return null;
        }
        $this->vendorCache[$vendorName] = $vendorNodeAggregateId;
        return $vendorNodeAggregateId;
    }

    public function getVendorNodes(): Nodes
    {
        if (!$this->storageRootNodeAggregateId) {
            return Nodes::createEmpty();
        }
        return $this->subGraph->findChildNodes(
            $this->storageRootNodeAggregateId,
            FindChildNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::fromStringArray([MarketplaceNodeType::VENDOR->value]
                    )),
            )
        );
    }

    /**
     * Returns the number of package nodes for the given vendor node aggregate id or all if none is given.
     */
    public function countPackageNodes(?NodeAggregateId $vendorAggregateId = null): int
    {
        if (!$this->storageRootNodeAggregateId) {
            return 0;
        }
        return $this->subGraph->countDescendantNodes(
            $vendorAggregateId ?? $this->storageRootNodeAggregateId,
            CountDescendantNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::fromStringArray([MarketplaceNodeType::PACKAGE->value])
                )
            )
        );
    }

    public function getPackageNode(
        Package         $package,
        NodeAggregateId $vendorNodeAggregateId
    ): ?Node
    {
        // Find the vendor node by name
        return $this->subGraph->findChildNodes(
            $vendorNodeAggregateId,
            FindChildNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::fromStringArray([MarketplaceNodeType::PACKAGE->value])
                ),
                propertyValue: PropertyValueEquals::create(
                    PropertyName::fromString('title'),
                    $package->getName(),
                    false
                )
            )
        )->first();
    }

    /**
     * @throws AccessDenied
     */
    public function createPackageNode(Package $package, NodeAggregateId $vendorNodeAggregateId): ?Node
    {
        $nodeAggregateId = NodeAggregateId::create();
        $workspaceName = WorkspaceName::forLive();
        $this->handleCommandWithRetry(
            CreateNodeAggregateWithNode::create(
                $workspaceName,
                $nodeAggregateId,
                NodeTypeName::fromString(MarketplaceNodeType::PACKAGE->value),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($this->subGraph->getDimensionSpacePoint()),
                $vendorNodeAggregateId,
                null,
                PropertyValuesToWrite::fromArray([
                    'uriPathSegment' => Slug::create($package->getName()),
                    'title' => $package->getName(),
                    'description' => $package->getDescription(),
                    'time' => \DateTime::createFromFormat(Storage::DATE_FORMAT, $package->getTime()),
                    'type' => $package->getType(),
                    'repository' => $package->getRepository(),
                    'favers' => $package->getFavers()
                ])
            )
        );
        return $this->subGraph->findNodeById($nodeAggregateId);
    }

    /**
     * Returns all package nodes for the given vendor node aggregate id.
     * If no vendor node aggregate id is provided, it will return all package nodes in the storage.
     */
    public function getPackageNodes(
        ?NodeAggregateId $vendorNodeAggregateId = null,
    ): Nodes
    {
        if (!$this->storageRootNodeAggregateId) {
            return Nodes::createEmpty();
        }
        if ($vendorNodeAggregateId) {
            return $this->subGraph->findChildNodes(
                $vendorNodeAggregateId,
                FindChildNodesFilter::create(
                    NodeTypeCriteria::createWithAllowedNodeTypeNames(
                        NodeTypeNames::fromStringArray([MarketplaceNodeType::PACKAGE->value])
                    ),
                )
            );
        }
        return $this->subGraph->findDescendantNodes(
            $this->storageRootNodeAggregateId,
            FindDescendantNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::fromStringArray([MarketplaceNodeType::PACKAGE->value])
                ),
            )
        );
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function updateNode(
        Node                      $node,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        array                     $properties
    ): bool
    {
        // Skip properties that are already set to the same value
        $serializedNodeProperties = $node->properties->serialized();
        foreach ($properties as $propertyName => $propertyValue) {
            if (!$serializedNodeProperties->propertyExists($propertyName)) {
                if ($propertyValue === null) {
                    // If the property does not exist and the value is null, we can skip it
                    unset($properties[$propertyName]);
                }
                continue;
            }
            if ($propertyValue instanceof \DateTimeInterface) {
                $propertyValue = $propertyValue->format(\DateTimeInterface::ATOM);
            }
            if ($serializedNodeProperties->getProperty($propertyName)?->value === $propertyValue) {
                unset($properties[$propertyName]);
            }
        }
        if (empty($properties)) {
            // No properties to update
            return true;
        }
        return $this->handleCommandWithRetry(
            SetNodeProperties::create(
                $this->workspaceName,
                $node->aggregateId,
                $originDimensionSpacePoint,
                PropertyValuesToWrite::fromArray($properties),
            )
        );
    }

    public function createOrUpdateMaintainerNode(
        Maintainer $maintainer,
        Node       $packageNode
    ): bool
    {
        $maintainerNode = $this->getPackageMaintainerNode(
            $packageNode->aggregateId,
            $maintainer->getName()
        );
        $properties = [
            'title' => $maintainer->getName(),
            'email' => $maintainer->getEmail(),
            'homepage' => $maintainer->getHomepage()
        ];

        if ($maintainerNode) {
            $this->updateNode(
                $maintainerNode,
                $maintainerNode->originDimensionSpacePoint,
                $properties
            );
            return true;
        }

        $maintainersNode = $this->subGraph->findNodeByPath(
            NodeName::fromString('maintainers'),
            $packageNode->aggregateId
        );
        if ($maintainersNode === null) {
            return false;
        }

        $maintainerNodeAggregateId = NodeAggregateId::create();
        return $this->handleCommandWithRetry(
            CreateNodeAggregateWithNode::create(
                $this->workspaceName,
                $maintainerNodeAggregateId,
                NodeTypeName::fromString(MarketplaceNodeType::MAINTAINER->value),
                $maintainersNode->originDimensionSpacePoint,
                $maintainersNode->aggregateId,
                initialPropertyValues: PropertyValuesToWrite::fromArray($properties)
            )
        );
    }

    public function getPackageMaintainerNodes(
        NodeAggregateId $packageNodeAggregateId
    ): Nodes
    {
        $maintainersNode = $this->subGraph->findNodeByPath(
            NodeName::fromString('maintainers'),
            $packageNodeAggregateId
        );
        if ($maintainersNode === null) {
            return Nodes::createEmpty();
        }
        return $this->subGraph->findChildNodes(
            $maintainersNode->aggregateId,
            FindChildNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::fromStringArray([MarketplaceNodeType::MAINTAINER->value])
                )
            )
        );
    }

    public function getPackageVersionsNode(
        NodeAggregateId $packageNodeAggregateId
    ): ?Node
    {
        return $this->subGraph->findNodeByPath(
            NodeName::fromString('versions'),
            $packageNodeAggregateId
        );
    }

    public function getPackageVersionNodes(
        NodeAggregateId $versionsNodeAggregateId
    ): Nodes
    {
        return $this->subGraph->findChildNodes(
            $versionsNodeAggregateId,
            FindChildNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::fromStringArray([MarketplaceNodeType::VERSION->value])
                )
            )
        );
    }

    public function getPackageVersionNode(
        NodeAggregateId $versionsNodeAggregateId,
        string          $version
    ): ?Node
    {
        return $this->subGraph->findChildNodes(
            $versionsNodeAggregateId,
            FindChildNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::fromStringArray([MarketplaceNodeType::VERSION->value])
                ),
                propertyValue: PropertyValueEquals::create(
                    PropertyName::fromString('version'),
                    $version,
                    true
                )
            )
        )->first();
    }

    public function getPackageMaintainerNode(
        NodeAggregateId $packageNodeAggregateId,
        string          $maintainerName
    ): ?Node
    {
        $maintainersNode = $this->subGraph->findNodeByPath(
            NodeName::fromString('maintainers'),
            $packageNodeAggregateId
        );
        if ($maintainersNode === null) {
            return null;
        }
        return $this->subGraph->findChildNodes(
            $maintainersNode->aggregateId,
            FindChildNodesFilter::create(
                NodeTypeCriteria::createWithAllowedNodeTypeNames(
                    NodeTypeNames::fromStringArray([MarketplaceNodeType::MAINTAINER->value])
                ),
                propertyValue: PropertyValueEquals::create(
                    PropertyName::fromString('title'),
                    $maintainerName,
                    true
                )
            )
        )->first();
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function updateChildNode(
        NodeAggregateId $parentNodeAggregateId,
        NodeName        $childNodeName,
        array           $properties,
    ): bool
    {
        $childNode = $this->subGraph->findNodeByPath(
            $childNodeName,
            $parentNodeAggregateId
        );
        if ($childNode) {
            return $this->updateNode(
                $childNode,
                $childNode->originDimensionSpacePoint,
                $properties
            );
        }
        return false;
    }

    /**
     * @param array<string, mixed> $properties
     */
    public function createOrUpdateVersionNode(
        NodeAggregateId     $versionsNodeAggregateId,
        string              $versionString,
        MarketplaceNodeType $nodeType,
        array               $properties,
    ): ?NodeAggregateId
    {
        $versionNode = $this->getPackageVersionNode(
            $versionsNodeAggregateId,
            $versionString
        );
        if ($versionNode) {
            $this->updateNode(
                $versionNode,
                $versionNode->originDimensionSpacePoint,
                $properties
            );
            $newNodeTypeName = NodeTypeName::fromString($nodeType->value);
            if ($versionNode->nodeTypeName !== $newNodeTypeName) {
                $this->handleCommandWithRetry(
                    ChangeNodeAggregateType::create(
                        $this->workspaceName,
                        $versionNode->aggregateId,
                        $newNodeTypeName,
                        NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy::STRATEGY_PROMISED_CASCADE
                    )
                );
            }
            return $versionNode->aggregateId;
        }
        $versionNodeAggregateId = NodeAggregateId::create();
        if (!$this->handleCommandWithRetry(
            CreateNodeAggregateWithNode::create(
                $this->workspaceName,
                $versionNodeAggregateId,
                NodeTypeName::fromString($nodeType->value),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($this->subGraph->getDimensionSpacePoint()),
                $versionsNodeAggregateId,
                initialPropertyValues: PropertyValuesToWrite::fromArray($properties),
            )
        )) {
            return null;
        }
        return $versionNodeAggregateId;
    }

    public function removeNode(
        Node $node
    ): bool
    {
        return $this->handleCommandWithRetry(
            RemoveNodeAggregate::create(
                $node->workspaceName,
                $node->aggregateId,
                $node->dimensionSpacePoint,
                NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS
            )
        );
    }

    public function getReadmeNode(NodeAggregateId $packageNodeAggregateId): ?Node
    {
        return $this->subGraph->findNodeByPath(
            NodePath::fromString('readme'),
            $packageNodeAggregateId
        );
    }

    public function updateNodeReference(
        Node                      $node,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        ReferenceName             $referenceName,
        ?NodeAggregateId          $referenceAggregateId = null
    ): void
    {
        $existingReferences = $this->subGraph->findReferences(
            $node->aggregateId,
            FindReferencesFilter::create(referenceName: $referenceName)
        );
        if ($existingReferences->getNodes()->first()?->aggregateId === $referenceAggregateId) {
            return; // No change needed, the reference is up-to-date
        }
        $references = NodeReferencesToWrite::create(
            NodeReferencesForName::fromTargets(
                $referenceName,
                $referenceAggregateId ?
                    NodeAggregateIds::fromArray([$referenceAggregateId]) : NodeAggregateIds::createEmpty(),
            )
        );
        $this->handleCommandWithRetry(
            SetNodeReferences::create(
                $this->workspaceName,
                $node->aggregateId,
                $originDimensionSpacePoint,
                $references
            )
        );
    }

    protected function handleCommandWithRetry(CommandInterface $command): bool
    {
        try {
            $this->contentRepository->handle($command);
            /** @phpstan-ignore catch.neverThrown */
        } catch (ConcurrencyException) {
            // Rebase the target workspace and try again
            $this->workspacePublishingService->rebaseWorkspace(
                $this->contentRepository->id,
                $this->workspaceName,
                RebaseErrorHandlingStrategy::STRATEGY_FORCE,
            );
            $this->contentRepository->handle($command);
        } catch (AccessDenied) {
            $this->logger->error('Access denied while executing command');
            return false;
        }
        return true;
    }
}
