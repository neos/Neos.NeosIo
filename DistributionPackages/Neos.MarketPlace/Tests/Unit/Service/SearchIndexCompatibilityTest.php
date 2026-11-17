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

use Composer\Semver\Semver;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\MarketPlace\Eel\IndexingHelper;
use Neos\MarketPlace\Tests\Unit\Support\NodeBuilder;
use PHPUnit\Framework\TestCase;

/**
 * The compatibility that is calculated during the import must be identical to the compatibility
 * that was calculated from the version nodes while building the search index.
 */
class SearchIndexCompatibilityTest extends TestCase
{
    private IndexingHelper $indexingHelper;

    protected function setUp(): void
    {
        $this->indexingHelper = new IndexingHelper();
        $reflectionProperty = new \ReflectionProperty($this->indexingHelper, 'compatibilityCheck');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->indexingHelper, [
            'neos/neos' => ['9.0', '8.3', '8.2'],
            'neos/flow' => ['9.0', '8.3'],
        ]);
    }

    /**
     * @return array<string, array{0: string, 1: string[]}>
     */
    public static function requirementProvider(): array
    {
        return [
            'exact match' => ['^9.0', ['9.0']],
            'range match' => ['^8.3 || ^9.0', ['9.0', '8.3']],
            'no match' => ['^7.0', []],
            'invalid constraint' => ['self.version', []],
        ];
    }

    /**
     * @dataProvider requirementProvider
     * @param string[] $expectedMatch
     */
    public function testCompatibilityIsIdenticalToTheOneCalculatedFromTheVersionNodes(
        string $constraint,
        array $expectedMatch
    ): void {
        $versionNode = NodeBuilder::createNode([
            'version' => ['type' => 'string', 'value' => '1.0.0'],
            'stability' => ['type' => 'boolean', 'value' => true],
            'require' => ['type' => 'string', 'value' => (string)json_encode([
                'neos/neos' => $constraint,
                'neos/flow' => $constraint,
            ])],
        ], 'Neos.MarketPlace:ReleasedVersion');

        $expected = $this->indexingHelper->extractCompatibility([$versionNode], 'neos/neos');
        $calculated = $this->calculateCompatibility([$versionNode], 'neos/neos', [
            'neos/neos' => ['9.0', '8.3', '8.2'],
            'neos/flow' => ['9.0', '8.3'],
        ]);

        self::assertSame($expected, $calculated);
        self::assertSame($expectedMatch, $calculated);
    }

    public function testTheCompatibilityOfAVersionWithoutRequirementsIsEmpty(): void
    {
        $versionNode = NodeBuilder::createNode([
            'version' => ['type' => 'string', 'value' => '1.0.0'],
            'stability' => ['type' => 'boolean', 'value' => true],
            'require' => ['type' => 'string', 'value' => (string)json_encode(['php' => '^8.4'])],
        ], 'Neos.MarketPlace:ReleasedVersion');

        $expected = $this->indexingHelper->extractCompatibility([$versionNode], 'neos/neos');
        $calculated = $this->calculateCompatibility([$versionNode], 'neos/neos', [
            'neos/neos' => ['9.0', '8.3'],
            'neos/flow' => ['9.0', '8.3'],
        ]);

        self::assertSame($expected, $calculated);
        self::assertSame([], $calculated);
    }

    /**
     * The calculation as it is implemented in the package converter.
     *
     * @param Node[] $versionNodes
     * @param array<string, string[]> $compatibilityCheck
     * @return string[]
     */
    private function calculateCompatibility(
        array $versionNodes,
        string $packageName,
        array $compatibilityCheck
    ): array {
        if (!array_key_exists($packageName, $compatibilityCheck)) {
            return [];
        }

        $compatibleVersions = [];
        foreach ($versionNodes as $versionNode) {
            $requireJson = $versionNode->getProperty('require');
            if (!$requireJson) {
                continue;
            }
            try {
                $require = json_decode($requireJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Exception) {
                continue;
            }
            if (!is_array($require) || !array_key_exists($packageName, $require)) {
                continue;
            }
            foreach ($compatibilityCheck[$packageName] as $version) {
                try {
                    if (Semver::satisfies($version, $require[$packageName])) {
                        $compatibleVersions[] = $version;
                    }
                } catch (\Exception) {
                    continue;
                }
            }
        }

        return array_values(array_unique($compatibleVersions));
    }
}
