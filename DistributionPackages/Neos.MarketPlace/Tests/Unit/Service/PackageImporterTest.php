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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\MarketPlace\Domain\Model\PackageSyncResult;
use Neos\MarketPlace\Domain\Model\Storage;
use Neos\MarketPlace\Service\PackageConverter;
use Neos\MarketPlace\Service\PackageImporter;
use Neos\MarketPlace\Tests\Unit\Support\NodeBuilder;
use Packagist\Api\Result\Package;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PackageImporterTest extends TestCase
{
    private Storage&MockObject $storage;
    private PackageConverter&MockObject $packageConverter;
    private PackageImporter $packageImporter;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(Storage::class);
        $this->packageConverter = $this->createMock(PackageConverter::class);
        $this->packageImporter = new PackageImporter(
            $this->packageConverter,
            $this->storage
        );
    }

    public function testProcessedPackagesAreKnown(): void
    {
        $this->packageConverter->method('convert')->willReturn(PackageSyncResult::Changed);

        $this->packageImporter->process($this->buildPackage('neos/neos'));

        self::assertSame(['neos/neos'], $this->packageImporter->getProcessedPackages());
        self::assertSame(['neos/neos'], $this->packageImporter->getKnownPackages());
    }

    public function testSkippedPackagesAreKnownButNotProcessed(): void
    {
        $this->packageImporter->skip($this->buildPackage('neos/abandoned'));

        self::assertSame([], $this->packageImporter->getProcessedPackages());
        self::assertSame(['neos/abandoned'], $this->packageImporter->getSkippedPackages());
        self::assertSame(['neos/abandoned'], $this->packageImporter->getKnownPackages());
    }

    public function testCleanupKeepsProcessedAndSkippedPackages(): void
    {
        $this->packageConverter->method('convert')->willReturn(PackageSyncResult::Changed);
        $this->packageImporter->process($this->buildPackage('neos/neos'));
        $this->packageImporter->skip($this->buildPackage('neos/abandoned'));

        $this->storage->method('getPackageNodes')->willReturn(Nodes::fromArray([
            $this->buildPackageNode('neos/neos'),
            $this->buildPackageNode('neos/abandoned'),
            $this->buildPackageNode('neos/removed-upstream'),
        ]));

        $removedPackages = [];
        $this->storage->method('removeNode')->willReturnCallback(
            static function ($node) use (&$removedPackages): bool {
                $removedPackages[] = $node->getProperty('title');
                return true;
            }
        );

        $count = $this->packageImporter->cleanupPackages();

        self::assertSame(1, $count);
        self::assertSame(['neos/removed-upstream'], $removedPackages);
    }

    private function buildPackage(string $name): Package
    {
        $package = new Package();
        $package->fromArray(['name' => $name]);
        return $package;
    }

    private function buildPackageNode(string $title): Node
    {
        return NodeBuilder::createNode(
            ['title' => ['type' => 'string', 'value' => $title]],
            'Neos.MarketPlace:Package',
            NodeAggregateId::fromString(sprintf('5e5c3c8a-0000-0000-0000-%012d', crc32($title)))
        );
    }
}
