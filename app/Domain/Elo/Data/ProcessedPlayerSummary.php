<?php

namespace App\Domain\Elo\Data;

use App\Models\Player;

/**
 * Everything a tournament run needs to know about one participant once all
 * their rounds have been processed, before it's written out as a
 * PlayerTournament row and folded into any Competition they're part of.
 */
final class ProcessedPlayerSummary
{
    public ?Player $player = null;

    public ?int $playerRankingId = null;

    public int $startingRank = 0;

    public string $playerName = '';

    public int $sourceRatingId = 0;

    public int $position = 0;

    public float $points = 0.0;

    public float $eloStart = 0.0;

    public float $eloFromFile = 0.0;

    public int $kFactor = 0;

    public float $totalVariation = 0.0;

    /** @var list<array{variation: float, rivalElo: float, score: float, gameId: int}> */
    public array $gameResults = [];

    public function eloEnd(): float
    {
        return $this->eloStart + $this->totalVariation;
    }
}
