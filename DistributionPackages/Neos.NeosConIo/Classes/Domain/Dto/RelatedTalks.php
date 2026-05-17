<?php
declare(strict_types=1);

namespace Neos\NeosConIo\Domain\Dto;

final readonly class RelatedTalks implements \JsonSerializable
{
    /**
     * @param array<string, RelatedTalk> $values
     */
    private function __construct(
        public array $values
    ) {
    }

    /**
     * @param array<string, RelatedTalk> $talks
     */
    public static function fromArray(array $talks): self
    {
        return new self($talks);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return RelatedTalk[]|\stdClass
     */
    public function jsonSerialize(): array|\stdClass
    {
        return $this->values ?: new \stdClass();
    }
}
