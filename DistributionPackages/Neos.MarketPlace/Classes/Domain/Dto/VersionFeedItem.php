<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Domain\Dto;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

final readonly class VersionFeedItem implements \JsonSerializable
{
    public function __construct(
        public Node    $packageNode,
        public string  $title,
        public ?string $description,
        public string  $link,
        public string  $linkToRelease,
        public string  $version,
        public \DateTimeInterface  $lastActivity,
        public bool    $stability,
        public ?string $stabilityLevel,
        public int     $downloads,
        public int     $stars,
    )
    {
    }

    /**
     * @return array{title: string, description: string|null, link: string, linkToRelease: string, version: string, lastActivity: string, stable: bool, stabilityLevel: string, downloads: int, stars: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'linkToRelease' => $this->linkToRelease,
            'version' => $this->version,
            'lastActivity' => $this->lastActivity->format('c'),
            'stable' => $this->stability,
            'stabilityLevel' => $this->stabilityLevel ?? '',
            'downloads' => $this->downloads,
            'stars' => $this->stars,
        ];
    }
}
