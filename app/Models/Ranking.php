<?php

namespace App\Models;

use Database\Factories\RankingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An independent ELO rating system, owned by one federation, optionally
 * recognized/adopted by others via the ranking_team pivot.
 */
#[Fillable([
    'owner_team_id', 'name', 'acronym', 'is_default', 'live_ranking',
    'valuation_games_standard', 'valuation_games_rapid', 'valuation_games_blitz',
    'id_base', 'ranking_base', 'ranking_restart', 'ranking_max_entry', 'last_used_id',
    'state', 'k_base', 'k_blitz', 'k_rapid', 'k_age', 'k_games_amount', 'k_downgrade',
    'k_rules', 'blitz_rules', 'rapid_rules', 'standard_rules',
])]
class Ranking extends Model
{
    /** @use HasFactory<RankingFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'live_ranking' => 'boolean',
            'state' => 'boolean',
            'k_rules' => 'array',
            'blitz_rules' => 'array',
            'rapid_rules' => 'array',
            'standard_rules' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'owner_team_id');
    }

    /**
     * Every federation that recognizes this ranking (owner included, if it
     * has also added itself to the pivot).
     *
     * @return BelongsToMany<Team, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'ranking_team')
            ->withPivot(['is_default'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_ranking')
            ->withPivot([
                'is_default', 'standard_elo', 'rapid_elo', 'blitz_elo',
                'k_standard_elo', 'k_rapid_elo', 'k_blitz_elo', 'id_on_ranking',
            ])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PlayerRanking, $this>
     */
    public function playerRankings(): HasMany
    {
        return $this->hasMany(PlayerRanking::class);
    }

    /**
     * @return HasMany<Competition, $this>
     */
    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class);
    }

    /**
     * @return HasMany<Tournament, $this>
     */
    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    /**
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }
}
