<?php

namespace App\Models;

use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'team_id', 'competition_id', 'ranking_id', 'main_arbiter_id', 'organizer_id',
    'director_id', 'source_reference', 'name', 'place', 'city', 'state', 'country',
    'details', 'more_details', 'rounds_count', 'time_control', 'type',
    'organizer_name', 'pairing_software', 'main_arbiter_name', 'other_arbiters',
    'speed', 'player_average', 'amount_games', 'amount_players', 'amount_players_rank',
    'is_team_event', 'squads_count', 'processed', 'begins', 'ends', 'data', 'awards',
])]
class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_team_event' => 'boolean',
            'processed' => 'boolean',
            'begins' => 'date:Y-m-d',
            'ends' => 'date:Y-m-d',
            'data' => 'array',
            'awards' => 'array',
        ];
    }

    /**
     * The owning federation/club — the tenant boundary.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
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
    public function mainArbiter(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'main_arbiter_id');
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'organizer_id');
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function director(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'director_id');
    }

    /**
     * @return HasMany<Round, $this>
     */
    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    /**
     * @return HasMany<Game, $this>
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * @return HasMany<PlayerTournament, $this>
     */
    public function playerTournaments(): HasMany
    {
        return $this->hasMany(PlayerTournament::class);
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_tournament')
            ->withPivot([
                'player_name', 'source_rating_id', 'k_factor', 'points', 'performance',
                'elo_variation', 'position', 'starting_rank', 'elo_from_file',
                'elo_start', 'elo_end', 'is_organizer', 'is_arbiter', 'is_director',
            ]);
    }

    /**
     * @return BelongsToMany<Player, $this>
     */
    public function arbiters(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'tournament_arbiters')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Average starting ELO of the field, computed straight from the pivot.
     */
    public function playersAverage(): ?float
    {
        return $this->playerTournaments()->avg('elo_start');
    }
}
