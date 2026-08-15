<?php

namespace App\Models;

use Database\Factories\PerformanceVariationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per ELO variation a player experiences from a single game. This is
 * the audit trail / source of truth for "evolution of ELO over time" charts.
 */
#[Fillable([
    'player_ranking_id', 'player_id', 'game_id', 'type', 'rival_elo', 'score',
    'performance', 'variation', 'elo_before', 'elo_after',
])]
class PerformanceVariation extends Model
{
    /** @use HasFactory<PerformanceVariationFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<PlayerRanking, $this>
     */
    public function playerRanking(): BelongsTo
    {
        return $this->belongsTo(PlayerRanking::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }
}
