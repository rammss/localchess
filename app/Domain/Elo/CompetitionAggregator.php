<?php

namespace App\Domain\Elo;

use App\Models\Competition;
use App\Models\CompetitionPlayer;
use App\Models\PlayerTournament;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;

/**
 * Folds a just-processed tournament's results into the standing of the
 * competition (circuit) it belongs to, applying the "count the best N,
 * discard the worst" rule. Ported from LocalChess 1.0's
 * CompetitionService::addTournament(), fixing the stale-variable bug found
 * in the 1.0 audit (finding 7.1‑D): the loop over players who *didn't* play
 * this tournament reused `$player` left over from the previous `foreach`
 * instead of the current row's own player, corrupting their discard
 * calculation.
 */
final class CompetitionAggregator
{
    public function addTournament(Tournament $tournament, Competition $competition): void
    {
        DB::transaction(function () use ($tournament, $competition) {
            $competition->increment('event_stage');
            $competition->refresh();
            $tournament->update(['competition_id' => $competition->id]);

            $tournamentIds = $competition->tournaments()->pluck('id')->all();
            $playedRows = PlayerTournament::where('tournament_id', $tournament->id)->get();
            $playedPlayerIds = $playedRows->pluck('player_id')->filter()->all();

            foreach ($playedRows as $row) {
                if (! $row->player_id) {
                    continue;
                }

                $this->updateStanding($competition, $row->player_id, $tournamentIds, [
                    'points' => (float) $row->points,
                    'performance' => (float) $row->performance,
                    'elo_variation' => (float) $row->elo_variation,
                ]);
            }

            // Players already in the competition who sat this tournament out
            // still need their tournament_amount/discard math refreshed.
            $absentees = CompetitionPlayer::where('competition_id', $competition->id)
                ->when(! empty($playedPlayerIds), fn ($query) => $query->whereNotIn('player_id', $playedPlayerIds))
                ->get();

            foreach ($absentees as $standing) {
                $this->refreshDiscardsAndAmount($standing, $competition, $tournamentIds);
                $standing->scoreboard = $standing->totalPoints();
                $standing->save();
            }

            $this->recomputePositions($competition);
        });
    }

    /**
     * @param  array{points: float, performance: float, elo_variation: float}  $result
     */
    private function updateStanding(Competition $competition, int $playerId, array $tournamentIds, array $result): void
    {
        $standing = CompetitionPlayer::firstOrNew([
            'competition_id' => $competition->id,
            'player_id' => $playerId,
        ]);

        if (! $standing->exists) {
            $standing->join_event_stage = $competition->event_stage;
            $standing->played_events = 0;
            $standing->points = [];
            $standing->performances = [];
            $standing->elo_variations = [];
            $standing->discarded_tournament_ids = [];
            $standing->discarded_points = [];
        }

        $standing->points = [...($standing->points ?? []), $result['points']];
        $standing->performances = [...($standing->performances ?? []), $result['performance']];
        $standing->elo_variations = [...($standing->elo_variations ?? []), $result['elo_variation']];
        $standing->played_events = ($standing->played_events ?? 0) + 1;

        $this->refreshDiscardsAndAmount($standing, $competition, $tournamentIds);

        $standing->scoreboard = $standing->totalPoints();
        $standing->save();
    }

    /**
     * Recomputes tournament_amount and, when the competition discards
     * results, which of the player's played tournaments count and which
     * get dropped — 1.0's CompetitionService::processDiscarded(), applied
     * to the *correct* player's own data this time.
     */
    private function refreshDiscardsAndAmount(CompetitionPlayer $standing, Competition $competition, array $tournamentIds): void
    {
        $standing->tournament_amount = $competition->event_stage - $standing->join_event_stage + 1;

        $playedTournaments = PlayerTournament::whereIn('tournament_id', $tournamentIds)
            ->where('player_id', $standing->player_id)
            ->orderBy('points')
            ->get();

        $playedTournamentIds = $playedTournaments->pluck('tournament_id')->all();
        $standing->played_tournament_ids = $playedTournamentIds;
        $standing->played_events = count($playedTournamentIds);
        $standing->counted_tournament_ids = $playedTournamentIds;
        $standing->discarded_tournament_ids = [];
        $standing->discarded_points = [];
        $standing->discarded_tournaments = 0;

        if ($competition->discarded_games <= 0) {
            return;
        }

        $standing->discarded_tournaments = (int) floor($standing->tournament_amount / $competition->discarded_games);

        if ($standing->discarded_tournaments <= 0) {
            return;
        }

        $absences = $standing->tournament_amount - $standing->played_events;
        $toDiscardFromPlayed = $standing->discarded_tournaments - $absences;

        if ($toDiscardFromPlayed <= 0) {
            return;
        }

        // Lowest-scoring played tournaments (already sorted ascending by points) get dropped first.
        $discardedIds = array_slice($playedTournamentIds, 0, $toDiscardFromPlayed);
        $standing->discarded_tournament_ids = $discardedIds;
        $standing->counted_tournament_ids = array_values(array_diff($playedTournamentIds, $discardedIds));

        $standing->discarded_points = $playedTournaments
            ->whereIn('tournament_id', $discardedIds)
            ->pluck('points')
            ->map(fn ($points) => (float) $points)
            ->all();
    }

    private function recomputePositions(Competition $competition): void
    {
        $standings = CompetitionPlayer::where('competition_id', $competition->id)
            ->get()
            ->sortByDesc(fn (CompetitionPlayer $standing) => [(float) $standing->scoreboard, $standing->averagePerformance()])
            ->values();

        foreach ($standings as $index => $standing) {
            $standing->position = $index + 1;
            $standing->variation = $standing->totalVariation();
            $standing->performance = $standing->averagePerformance();
            $standing->save();
        }
    }
}
