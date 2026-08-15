<?php

namespace App\Models;

use Database\Factories\PlayerTournamentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A player's result summary for one specific tournament — the Player<->Tournament
 * pivot, exposed as its own model since it carries the ELO before/after snapshot.
 */
#[Table('player_tournament')]
#[Fillable([
    'tournament_id', 'player_id', 'player_name', 'source_rating_id', 'k_factor',
    'points', 'performance', 'elo_variation', 'position', 'starting_rank',
    'elo_from_file', 'elo_start', 'elo_end', 'is_organizer', 'is_arbiter', 'is_director',
])]
class PlayerTournament extends Model
{
    /** @use HasFactory<PlayerTournamentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_organizer' => 'boolean',
            'is_arbiter' => 'boolean',
            'is_director' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public static function averageStartingEloFor(int $tournamentId): ?float
    {
        return static::where('tournament_id', $tournamentId)->avg('elo_start');
    }
}
