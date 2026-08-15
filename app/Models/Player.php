<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A chess player. Global entity — not owned by a single federation, a player
 * competes across many federations' tournaments over their career.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $club_team_id
 * @property string $name
 * @property string|null $last_name
 * @property bool|null $gender
 * @property string|null $title
 * @property Carbon|null $birth_date
 */
#[Fillable([
    'user_id', 'club_team_id', 'name', 'last_name', 'gender', 'title',
    'arbiter_title', 'is_arbiter', 'is_organizer', 'country', 'club_name',
    'birth_date', 'image', 'details',
])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'gender' => 'boolean',
            'is_arbiter' => 'boolean',
            'is_organizer' => 'boolean',
            'birth_date' => 'date:Y-m-d',
        ];
    }

    /**
     * The self-service account this player has claimed, if any.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'club_team_id');
    }

    /**
     * @return BelongsToMany<Ranking, $this>
     */
    public function rankings(): BelongsToMany
    {
        return $this->belongsToMany(Ranking::class, 'player_ranking')
            ->withPivot([
                'is_default', 'standard_elo', 'rapid_elo', 'blitz_elo',
                'k_standard_elo', 'k_rapid_elo', 'k_blitz_elo', 'id_on_ranking',
            ])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Tournament, $this>
     */
    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'player_tournament')
            ->withPivot([
                'player_name', 'source_rating_id', 'k_factor', 'points', 'performance',
                'elo_variation', 'position', 'starting_rank', 'elo_from_file',
                'elo_start', 'elo_end', 'is_organizer', 'is_arbiter', 'is_director',
            ]);
    }

    /**
     * @return BelongsToMany<Competition, $this>
     */
    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class, 'competition_player')
            ->withPivot([
                'position', 'tournament_amount', 'join_event_stage', 'played_events',
                'discarded_tournaments', 'discarded_tournament_ids', 'points',
                'discarded_points', 'counted_tournament_ids', 'played_tournament_ids',
                'performances', 'elo_variations', 'scoreboard', 'performance', 'variation',
            ]);
    }

    /**
     * Tournaments where this player was assigned as an arbiter.
     *
     * @return BelongsToMany<Tournament, $this>
     */
    public function arbiteredTournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_arbiters')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PerformanceVariation, $this>
     */
    public function performanceVariations(): HasMany
    {
        return $this->hasMany(PerformanceVariation::class);
    }

    /**
     * @return HasMany<Game, $this>
     */
    public function gamesAsWhite(): HasMany
    {
        return $this->hasMany(Game::class, 'white_player_id');
    }

    /**
     * @return HasMany<Game, $this>
     */
    public function gamesAsBlack(): HasMany
    {
        return $this->hasMany(Game::class, 'black_player_id');
    }

    /**
     * Rounds this player sat out with a bye instead of playing.
     *
     * @return HasMany<Game, $this>
     */
    public function byeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'bye_player_id');
    }

    /**
     * Every game this player took part in, one way or another.
     *
     * @return Builder<Game>
     */
    public function games(): Builder
    {
        return Game::where('white_player_id', $this->id)
            ->orWhere('black_player_id', $this->id)
            ->orWhere('bye_player_id', $this->id);
    }
}
