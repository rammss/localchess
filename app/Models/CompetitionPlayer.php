<?php

namespace App\Models;

use Database\Factories\CompetitionPlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A player's accumulated standing within a competition (circuit of
 * tournaments), including which results get discarded under the
 * competition's "count the best N" rule.
 */
#[Table('competition_player')]
#[Fillable([
    'competition_id', 'player_id', 'position', 'tournament_amount', 'join_event_stage',
    'played_events', 'discarded_tournaments', 'discarded_tournament_ids', 'points',
    'discarded_points', 'counted_tournament_ids', 'played_tournament_ids',
    'performances', 'elo_variations', 'scoreboard', 'performance', 'variation',
])]
class CompetitionPlayer extends Model
{
    /** @use HasFactory<CompetitionPlayerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'discarded_tournament_ids' => 'array',
            'points' => 'array',
            'discarded_points' => 'array',
            'counted_tournament_ids' => 'array',
            'played_tournament_ids' => 'array',
            'performances' => 'array',
            'elo_variations' => 'array',
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
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Total accumulated ELO variation across the whole competition.
     */
    public function totalVariation(): float
    {
        return array_sum($this->elo_variations ?? []);
    }

    /**
     * Average performance across the whole competition.
     */
    public function averagePerformance(): float
    {
        $performances = $this->performances ?? [];

        return count($performances) > 0 ? array_sum($performances) / count($performances) : 0.0;
    }

    /**
     * The scoreboard total: points scored minus the points from discarded
     * (worst) results.
     */
    public function totalPoints(): float
    {
        return array_sum($this->points ?? []) - array_sum($this->discarded_points ?? []);
    }
}
