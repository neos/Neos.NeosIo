<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Domain\Dto;

/**
 * @implements \IteratorAggregate<int, VersionFeedItem>
 */
final readonly class VersionFeedResult implements \IteratorAggregate, \JsonSerializable
{
    /** @var list<VersionFeedItem> */
    public array $items;

    public function __construct(VersionFeedItem ...$items)
    {
        $this->items = array_values($items);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * @return array{results: VersionFeedItem[]}
     */
    public function jsonSerialize(): array
    {
        return [
            'results' => $this->items,
        ];
    }
}
