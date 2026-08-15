<?php

namespace App\Domain\Elo;

use App\Domain\Elo\Data\ParsedPlayer;
use App\Domain\Elo\Data\PlayerMatch;
use App\Models\PlayerRanking;

/**
 * Matches a parsed TRF16 participant against an existing Player, using the
 * rating_id declared in the file (== PlayerRanking::id_on_ranking within
 * that specific Ranking). A name-similarity score is computed alongside the
 * match so the tournament-confirmation screen can flag a mismatch for a
 * human to double check before anything is saved — ported from 1.0's
 * UtilityService::allocateByRankingId().
 */
final class PlayerMatcher
{
    public function match(ParsedPlayer $parsedPlayer, int $rankingId): PlayerMatch
    {
        if ($parsedPlayer->ratingId === 0) {
            return new PlayerMatch(player: null, nameSimilarityPercent: null);
        }

        $playerRanking = PlayerRanking::with('player')
            ->where('ranking_id', $rankingId)
            ->where('id_on_ranking', $parsedPlayer->ratingId)
            ->first();

        if (! $playerRanking || ! $playerRanking->player) {
            return new PlayerMatch(player: null, nameSimilarityPercent: null);
        }

        $player = $playerRanking->player;

        $similarity = ($this->similarity($parsedPlayer->name, $player->name)
            + $this->similarity($parsedPlayer->lastName, $player->last_name ?? '')) / 2;

        return new PlayerMatch(player: $player, nameSimilarityPercent: round($similarity, 1));
    }

    private function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        similar_text(strtolower(trim($a)), strtolower(trim($b)), $percent);

        return $percent;
    }
}
