<?php
declare(strict_types=1);

namespace Neos\NeosConIo\Domain\Dto;

use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

final readonly class Talk implements \JsonSerializable
{
    public function __construct(
        public NodeAggregateId    $id,
        public string             $title,
        public string             $description,
        public string             $type,
        public \DateTimeInterface $date,
        public string             $stage,
        public ?Nodes             $speakers,
    )
    {
    }

    /**
     * @return array{id: string, title: string, description: string, type: string, start: string, stage: string, speakerIds: string[]}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id->value,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'date' => $this->date->format('c'),
            'start' => $this->date->format('G:i'),
            'stage' => $this->stage,
            'speakerIds' => $this->speakers?->toNodeAggregateIds()->toStringArray() ?? [],
        ];
    }
}
