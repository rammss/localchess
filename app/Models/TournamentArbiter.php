<?php

namespace App\Models;

use Database\Factories\TournamentArbiterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A per-tournament arbiter assignment. Not a standing team-wide role — see
 * the plan's note on why "árbitro" is modeled here rather than as a TeamRole.
 */
#[Fillable(['tournament_id', 'player_id', 'role'])]
class TournamentArbiter extends Model
{
    /** @use HasFactory<TournamentArbiterFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
