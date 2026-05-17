<?php
declare(strict_types=1);

namespace Neos\NeosConIo\Domain\Dto;

final readonly class Speakers implements \JsonSerializable
{
    /**
     * @param array<string, Speaker> $values
     */
    private function __construct(
        public array $values
    )
    {
    }

    /**
     * @param array<string, Speaker> $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return Speaker[]|\stdClass
     */
    public function jsonSerialize(): array|\stdClass
    {
        return $this->values ?: new \stdClass();
    }
}
