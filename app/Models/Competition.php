<?php

namespace App\Models;

use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A circuit of tournaments with cumulative, discardable scoring.
 */
#[Fillable([
    'team_id', 'ranking_id', 'name', 'acronym', 'url', 'speed', 'discarded_games',
    'tournaments_amount', 'players_amount', 'games_amount', 'in_progress',
    'event_stage', 'rules', 'begins', 'ends',
])]
class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'in_progress' => 'boolean',
            'rules' => 'array',
            'begins' => 'date:Y-m-d',
            'ends' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Ranking, $this>
     */
    public function ranking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class);
    }

    /**
     * @return HasMany<Tournament, $this>
     */
    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    /**
     * @return HasMany<CompetitionPlayer, $this>
     */
    public function competitionPlayers(): HasMany
    {
        return $this->hasMany(CompetitionPlayer::class);
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'competition_player')
            ->withPivot([
                'position', 'tournament_amount', 'join_event_stage', 'played_events',
                'discarded_tournaments', 'discarded_tournament_ids', 'points',
                'discarded_points', 'counted_tournament_ids', 'played_tournament_ids',
                'performances', 'elo_variations', 'scoreboard', 'performance', 'variation',
            ]);
    }
}
