<?php

namespace App\Models;

use Database\Factories\PlayerRankingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The current ELO state of one player within one ranking, per game speed.
 * This is the pivot of Player<->Ranking, exposed as its own model because it
 * carries real business logic (see the per-speed aggregate helpers below).
 */
#[Table('player_ranking')]
#[Fillable([
    'ranking_id', 'player_id', 'is_default', 'standard_elo', 'rapid_elo', 'blitz_elo',
    'k_standard_elo', 'k_rapid_elo', 'k_blitz_elo', 'id_on_ranking',
])]
class PlayerRanking extends Model
{
    /** @use HasFactory<PlayerRankingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Ranking, $this>
     */
    public function ranking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return HasMany<PerformanceVariation, $this>
     */
    public function performanceVariations(): HasMany
    {
        return $this->hasMany(PerformanceVariation::class);
    }

    public function gamesPlayed(string $speed): int
    {
        return $this->performanceVariations()->where('type', $speed)->count();
    }

    public function rivalEloSum(string $speed): float
    {
        return (float) $this->performanceVariations()->where('type', $speed)->sum('rival_elo');
    }

    public function scoreSum(string $speed): float
    {
        return (float) $this->performanceVariations()->where('type', $speed)->sum('score');
    }

    public function latestPerformance(string $speed): ?float
    {
        return $this->performanceVariations()->where('type', $speed)->latest('id')->value('performance');
    }

    public function variationSum(string $speed): float
    {
        return (float) $this->performanceVariations()->where('type', $speed)->sum('variation');
    }

    /**
     * The ELO for a given speed, e.g. $playerRanking->eloFor('blitz').
     */
    public function eloFor(string $speed): float
    {
        return (float) $this->{"{$speed}_elo"};
    }

    /**
     * The K-factor for a given speed.
     */
    public function kFor(string $speed): int
    {
        return (int) $this->{"k_{$speed}_elo"};
    }
}
