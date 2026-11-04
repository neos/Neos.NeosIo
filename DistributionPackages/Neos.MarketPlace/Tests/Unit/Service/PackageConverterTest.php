<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Tests\Unit\Service;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\ElasticSearch\ContentRepositoryAdaptor\Indexer\NodeIndexer;
use Neos\Cache\Frontend\StringFrontend;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\MarketPlace\Domain\Model\NodeUpdateResult;
use Neos\MarketPlace\Domain\Model\PackageSyncResult;
use Neos\MarketPlace\Domain\Model\Storage;
use Neos\MarketPlace\Service\PackageConverter;
use Neos\MarketPlace\Tests\Unit\Support\NodeBuilder;
use Packagist\Api\Result\Package;
use Packagist\Api\Result\Package\Downloads;
use Packagist\Api\Result\Package\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class PackageConverterTest extends TestCase
{
    private const VENDOR_AGGREGATE_ID = '2b2f0f5d-0000-0000-0000-000000000002';
    private const PACKAGE_AGGREGATE_ID = '1a1e0e4c-0000-0000-0000-000000000001';
    private const VERSIONS_AGGREGATE_ID = '3c3a1a6e-0000-0000-0000-000000000003';

    private Storage&MockObject $storage;
    private NodeIndexer&MockObject $nodeIndexer;
    private StringFrontend&MockObject $packageSyncCache;
    private PackageConverter $packageConverter;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(Storage::class);
        $this->nodeIndexer = $this->createMock(NodeIndexer::class);
        $this->packageSyncCache = $this->createMock(StringFrontend::class);

        $this->packageConverter = new PackageConverter($this->storage, $this->nodeIndexer);
        $this->setInjectedProperty($this->packageConverter, 'logger', new NullLogger());
        $this->setInjectedProperty($this->packageConverter, 'packageSyncCache', $this->packageSyncCache);
    }

    public function testPackageIsNotTouchedIfItDoesNotRequireAnUpdate(): void
    {
        $this->storage->expects(self::never())->method('getPackageNode');
        $this->storage->expects(self::never())->method('updateNode');
        $this->nodeIndexer->expects(self::never())->method('indexNode');

        $this->givenPackageState('neos/neos', new \DateTimeImmutable('2099-01-01T00:00:00+00:00'), time());

        self::assertSame(
            PackageSyncResult::NotRequired,
            $this->packageConverter->convert($this->buildPackage())
        );
    }

    public function testPackageWithoutAnyChangeIsNotReIndexed(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success());
        $this->givenVersionUpdateResult(NodeUpdateResult::success());

        $this->nodeIndexer->expects(self::never())->method('indexNode');

        self::assertSame(
            PackageSyncResult::Unchanged,
            $this->packageConverter->convert($this->buildPackage())
        );
    }

    public function testPackageWithOnlyChangedMetricsIsNotReIndexed(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success(['favers', 'downloadTotal']));
        $this->givenVersionUpdateResult(NodeUpdateResult::success());

        $this->nodeIndexer->expects(self::never())->method('indexNode');

        // The metrics did change, so the package is reported as changed
        self::assertSame(
            PackageSyncResult::Changed,
            $this->packageConverter->convert($this->buildPackage())
        );
    }

    public function testChangedPackageIsReIndexed(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success(['description']));
        $this->givenVersionUpdateResult(NodeUpdateResult::success());

        $this->nodeIndexer->expects(self::once())->method('indexNode');

        self::assertSame(
            PackageSyncResult::Changed,
            $this->packageConverter->convert($this->buildPackage())
        );
    }

    public function testChangedVersionIsReIndexed(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success(['favers']));
        $this->givenVersionUpdateResult(NodeUpdateResult::success(['description']));

        $this->nodeIndexer->expects(self::once())->method('indexNode');

        self::assertSame(
            PackageSyncResult::Changed,
            $this->packageConverter->convert($this->buildPackage())
        );
    }

    public function testRemovedVersionIsRemovedAndReIndexed(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success(['favers']));
        $this->givenVersionUpdateResult(NodeUpdateResult::success());

        $this->storage->expects(self::once())->method('removeNode')->willReturn(true);
        $this->nodeIndexer->expects(self::once())->method('indexNode');

        // The upstream package does not contain the version anymore
        self::assertSame(
            PackageSyncResult::Changed,
            $this->packageConverter->convert($this->buildPackage([]))
        );
    }

    public function testExistingVersionIsNotRemoved(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success());
        $this->givenVersionUpdateResult(NodeUpdateResult::success());

        $this->storage->expects(self::never())->method('removeNode');
        $this->nodeIndexer->expects(self::never())->method('indexNode');

        self::assertSame(
            PackageSyncResult::Unchanged,
            $this->packageConverter->convert($this->buildPackage())
        );
    }

    public function testPackageIsMarkedAsSyncedAfterTheImport(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success(['description']));
        $this->givenVersionUpdateResult(NodeUpdateResult::success());

        $this->packageSyncCache->expects(self::once())->method('set')->with('neos-neos');

        $this->packageConverter->convert($this->buildPackage());
    }

    public function testPackageIsNotMarkedAsSyncedIfTheImportFailed(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->storage->method('updateNode')->willReturn(NodeUpdateResult::failure());

        $this->packageSyncCache->expects(self::never())->method('set');

        self::assertSame(
            PackageSyncResult::NotRequired,
            $this->packageConverter->convert($this->buildPackage())
        );
    }

    public function testPackageIsNotMarkedAsSyncedIfTheVersionImportFailed(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success(['description']));
        $this->givenVersionUpdateResult(NodeUpdateResult::failure());

        $this->packageSyncCache->expects(self::never())->method('set');
        $this->nodeIndexer->expects(self::never())->method('indexNode');

        self::assertSame(
            PackageSyncResult::NotRequired,
            $this->packageConverter->convert($this->buildPackage())
        );
    }

    public function testVendorIsUpdatedOnlyOnceAfterTheSync(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success(['description']));
        $this->givenVersionUpdateResult(NodeUpdateResult::success());

        $vendorNode = NodeBuilder::createNode(
            ['title' => ['type' => 'string', 'value' => 'neos'], 'lastActivity' => ['type' => 'DateTime', 'value' => '2026-01-01T00:00:00+00:00']],
            'Neos.MarketPlace:Vendor',
            NodeAggregateId::fromString(self::VENDOR_AGGREGATE_ID),
            'neos'
        );
        $this->storage->method('getNodeByAggregateId')->willReturn($vendorNode);
        $this->storage->method('getPackageNodes')->willReturn(Nodes::fromArray([$this->buildPackageNode()]));

        $updatedProperties = [];
        $this->storage->method('updateNode')->willReturnCallback(
            function (Node $node, $originDimensionSpacePoint, array $properties) use (&$updatedProperties): NodeUpdateResult {
                $updatedProperties[] = $properties;
                return NodeUpdateResult::success(array_keys($properties));
            }
        );

        $this->packageConverter->convert($this->buildPackage());

        // The vendor is not updated while the package is processed
        self::assertCount(1, $updatedProperties);

        $this->packageConverter->updateVendors();

        self::assertCount(2, $updatedProperties);
        self::assertArrayHasKey('lastActivity', $updatedProperties[1]);
    }

    public function testVendorWithAnUpToDateLastActivityIsNotUpdated(): void
    {
        $this->givenPackageNode($this->buildPackageNode());
        $this->givenVersionNodes([$this->buildVersionNode('1.0.0')]);
        $this->givenPackageUpdateResult(NodeUpdateResult::success(['description']));
        $this->givenVersionUpdateResult(NodeUpdateResult::success());

        $vendorNode = NodeBuilder::createNode(
            ['title' => ['type' => 'string', 'value' => 'neos'], 'lastActivity' => ['type' => 'DateTime', 'value' => '2099-01-01T00:00:00+00:00']],
            'Neos.MarketPlace:Vendor',
            NodeAggregateId::fromString(self::VENDOR_AGGREGATE_ID),
            'neos'
        );
        $this->storage->method('getNodeByAggregateId')->willReturn($vendorNode);
        $this->storage->method('getPackageNodes')->willReturn(Nodes::fromArray([$this->buildPackageNode()]));

        $updatedProperties = [];
        $this->storage->method('updateNode')->willReturnCallback(
            function (Node $node, $originDimensionSpacePoint, array $properties) use (&$updatedProperties): NodeUpdateResult {
                $updatedProperties[] = $properties;
                return NodeUpdateResult::success(array_keys($properties));
            }
        );

        $this->packageConverter->convert($this->buildPackage());
        $this->packageConverter->updateVendors();

        self::assertCount(1, $updatedProperties);
    }

    private function givenPackageState(string $packageName, \DateTimeInterface $lastActivity, int $lastSync): void
    {
        $this->setInjectedProperty($this->packageConverter, 'packagesState', [
            $packageName => [
                'lastActivity' => $lastActivity,
                'lastSync' => $lastSync,
            ]
        ]);
    }

    private function givenPackageNode(Node $packageNode): void
    {
        $this->storage->method('getOrCreateVendorNode')->willReturn(NodeAggregateId::fromString(self::VENDOR_AGGREGATE_ID));
        $this->storage->method('getPackageNode')->willReturn($packageNode);
        $this->storage->method('getPackageVersionsNode')->willReturn(
            NodeBuilder::createNode([], 'Neos.Neos:ContentCollection', NodeAggregateId::fromString(self::VERSIONS_AGGREGATE_ID), 'versions')
        );
    }

    /**
     * @param Node[] $versionNodes
     */
    private function givenVersionNodes(array $versionNodes): void
    {
        $this->storage->method('getPackageVersionNodes')->willReturn(Nodes::fromArray($versionNodes));
    }

    private function givenPackageUpdateResult(NodeUpdateResult $result): void
    {
        $this->storage->method('updateNode')->willReturnCallback(
            static function (Node $node, $originDimensionSpacePoint, array $properties) use ($result): NodeUpdateResult {
                if (array_key_exists('lastActivity', $properties)) {
                    return NodeUpdateResult::success(['lastActivity']);
                }
                return $result;
            }
        );
    }

    private function givenVersionUpdateResult(NodeUpdateResult $result): void
    {
        $this->storage->method('createOrUpdateVersionNode')->willReturn($result);
    }

    private function buildPackageNode(): Node
    {
        return NodeBuilder::createNode([
            'title' => ['type' => 'string', 'value' => 'neos/neos'],
            'lastActivity' => ['type' => 'DateTime', 'value' => '2026-04-01T19:42:26+00:00'],
        ], 'Neos.MarketPlace:Package', NodeAggregateId::fromString(self::PACKAGE_AGGREGATE_ID), 'neos-neos');
    }

    private function buildVersionNode(string $version): Node
    {
        return NodeBuilder::createNode([
            'version' => ['type' => 'string', 'value' => $version],
            'time' => ['type' => 'DateTime', 'value' => '2026-04-01T19:42:26+00:00'],
            'stabilityLevel' => ['type' => 'string', 'value' => 'stable'],
        ], 'Neos.MarketPlace:Version', NodeAggregateId::fromString('4d4b2b7f-0000-0000-0000-000000000004'), 'version-' . str_replace('.', '-', $version));
    }

    /**
     * @param Version[]|null $versions
     */
    private function buildPackage(?array $versions = null): Package
    {
        $package = new Package();
        $package->fromArray([
            'name' => 'neos/neos',
            'description' => 'The Neos content repository and content management system',
            'time' => '2026-04-01T19:42:26+00:00',
            'type' => 'neos-package',
            'repository' => 'https://github.com/neos/neos',
            'favers' => 42,
            'abandoned' => false,
            'downloads' => (new Downloads())->setTotal(1000)->setMonthly(100)->setDaily(10),
            'maintainers' => [],
            'versions' => $versions ?? [$this->buildVersion()],
        ]);
        return $package;
    }

    private function buildVersion(): Version
    {
        $version = new Version();
        $version->fromArray([
            'version' => '1.0.0',
            'versionNormalized' => '1.0.0.0',
            'description' => 'Version 1.0.0',
            'type' => 'neos-package',
            'time' => '2026-04-01T19:42:26+00:00',
            'keywords' => [],
            'licenses' => ['MIT'],
            'require' => ['php' => '^8.4'],
            'requireDev' => [],
            'provide' => [],
            'bin' => [],
            'suggest' => [],
            'conflict' => [],
            'replace' => [],
        ]);
        return $version;
    }

    private function setInjectedProperty(object $object, string $propertyName, mixed $value): void
    {
        $reflectionProperty = new \ReflectionProperty($object, $propertyName);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }
}
