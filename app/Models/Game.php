<?php

namespace App\Models;

use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tournament_id', 'round_id', 'ranking_id', 'white_player_id', 'black_player_id',
    'bye_player_id', 'white_elo', 'white_k', 'white_variation', 'white_result',
    'black_elo', 'black_k', 'black_variation', 'black_result', 'forfeit', 'no_game',
    'bye_type', 'speed', 'pgn', 'played_at',
])]
class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'forfeit' => 'boolean',
            'no_game' => 'boolean',
            'played_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tournament, $this>
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * @return BelongsTo<Round, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
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
    public function white(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'white_player_id');
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function black(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'black_player_id');
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function bye(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'bye_player_id');
    }

    /**
     * @return HasMany<PerformanceVariation, $this>
     */
    public function performanceVariations(): HasMany
    {
        return $this->hasMany(PerformanceVariation::class);
    }
}
