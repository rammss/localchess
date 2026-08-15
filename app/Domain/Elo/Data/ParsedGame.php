<?php

namespace App\Domain\Elo\Data;

/**
 * One round's worth of a player's game, straight from the TRF16 line —
 * before it's been matched to an actual opponent Player record.
 */
final readonly class ParsedGame
{
    public function __construct(
        public int $rivalStartingRank,
        public ?string $color,
        public string $rawResult,
    ) {}

    public function toArray(): array
    {
        return [
            'rivalStartingRank' => $this->rivalStartingRank,
            'color' => $this->color,
            'rawResult' => $this->rawResult,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['rivalStartingRank'], $data['color'], $data['rawResult']);
    }
}
