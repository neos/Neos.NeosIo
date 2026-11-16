<?php
declare(strict_types=1);

namespace Neos\NeosConIo\Domain\Dto;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Psr\Http\Message\UriInterface;

final readonly class RelatedTalk implements \JsonSerializable
{
    public function __construct(
        public NodeAggregateId $id,
        public string $title,
        public string $eventName,
        public ?UriInterface $url,
        public bool $hasVideo,
    ) {
    }

    /**
     * @return array{id: string, title: string, event: string, url: string, hasVideo: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id->value,
            'title' => $this->title,
            'event' => $this->eventName,
            'url' => (string)$this->url,
            'hasVideo' => $this->hasVideo,
        ];
    }
}
