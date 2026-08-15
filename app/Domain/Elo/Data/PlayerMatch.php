<?php

namespace App\Domain\Elo\Data;

use App\Models\Player;

/**
 * The result of trying to match a TRF16 line to an existing Player, by the
 * rating_id (id_on_ranking) declared in the file.
 */
final readonly class PlayerMatch
{
    public function __construct(
        public ?Player $player,
        public ?float $nameSimilarityPercent,
    ) {}

    public function isMatched(): bool
    {
        return $this->player !== null;
    }
}
