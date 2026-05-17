<?php
declare(strict_types=1);

namespace Neos\NeosConIo\Domain\Dto;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Psr\Http\Message\UriInterface;

final readonly class Speaker implements \JsonSerializable
{
    public function __construct(
        public NodeAggregateId $id,
        public string          $name,
        public string          $summary,
        public ?UriInterface   $avatarUrl,
        public RelatedTalks    $topics,
        public ?string         $company,
        public ?string         $position,
        public ?string         $twitter,
        public ?string         $github,
        public ?string         $mastodon,
    )
    {
    }

    /**
     * @return array{id: string, name: string, summary: string, avatar: string, facts: array{company: string, role: string, github: string, twitter: string, mastodon: string}, topics: RelatedTalks}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id->value,
            'name' => $this->name,
            'summary' => $this->summary,
            'avatar' => (string)$this->avatarUrl,
            'facts' => [
                'company' => (string)$this->company,
                'role' => (string)$this->position,
                'github' => (string)$this->github,
                'twitter' => (string)$this->twitter,
                'mastodon' => (string)$this->mastodon,
            ],
            'topics' => $this->topics,
        ];
    }
}
