<?php

namespace App\Domain\Elo;

use App\Domain\Elo\Data\ParsedPlayer;
use App\Domain\Elo\Data\ParsedTournament;
use App\Domain\Elo\Data\ProcessedPlayerSummary;
use App\Models\Game;
use App\Models\PerformanceVariation;
use App\Models\Player;
use App\Models\PlayerRanking;
use App\Models\PlayerTournament;
use App\Models\Ranking;
use App\Models\Round;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Turns a parsed TRF16 tournament into Rounds, Games, ELO variations and a
 * per-player summary — the heart of the engine. Ported from LocalChess 1.0's
 * RankingService (createRoundsAndGamesGenerator, gamesAndRanking,
 * savePlayersTorunamentResume), restructured into one pass per round instead
 * of 1.0's array-mutation reconciliation, and fixing the dead "full point
 * bye" branch that 1.0's bye-type detection never actually reached.
 *
 * Ratings are frozen at their pre-tournament value for every game's
 * expected-score calculation (a "periodic", not live, rating system — see
 * Ranking::live_ranking) and applied in one lump sum at the end; the
 * elo_before/elo_after recorded per game still walks forward game by game
 * so a player's history reads as a smooth in-tournament progression.
 */
final class TournamentProcessor
{
    public function __construct(private readonly EloCalculator $calculator) {}

    /**
     * @param  array<int, Player>  $playersByStartingRank  resolved via PlayerMatcher/PlayerRosterResolver beforehand
     */
    public function process(
        Tournament $tournament,
        ParsedTournament $parsed,
        Ranking $ranking,
        string $speed,
        array $playersByStartingRank,
    ): void {
        DB::transaction(function () use ($tournament, $parsed, $ranking, $speed, $playersByStartingRank) {
            $rounds = $this->createRounds($tournament, $parsed);
            $parsedByStartingRank = collect($parsed->players)->keyBy('startingRank');

            $summaries = $this->initializeSummaries($parsed, $playersByStartingRank, $ranking, $speed);

            $gamesCreated = $this->createGamesAndAccumulateResults(
                $tournament, $ranking, $speed, $rounds, $parsedByStartingRank, $playersByStartingRank, $summaries,
            );

            $this->applyResultsToRankings($ranking, $speed, $summaries);
            $this->savePlayerTournamentRows($tournament, $speed, $summaries);
            $this->computeCategoryAwards($tournament, $ranking, $speed);

            $tournament->update([
                'processed' => true,
                'speed' => $speed,
                'ranking_id' => $ranking->id,
                'rounds_count' => count($rounds),
                'amount_games' => $gamesCreated,
                'amount_players' => count($parsed->players),
                'amount_players_rank' => $parsed->amountPlayersRank,
                'player_average' => $parsed->playerAverageRating,
            ]);
        });
    }

    /**
     * @return list<Round>
     */
    private function createRounds(Tournament $tournament, ParsedTournament $parsed): array
    {
        return collect($parsed->roundDates)
            ->map(fn (string $date, int $index) => Round::create([
                'tournament_id' => $tournament->id,
                'number' => $index + 1,
                'date' => $this->parseRoundDate($date),
            ]))
            ->all();
    }

    private function parseRoundDate(string $raw): ?string
    {
        try {
            return Carbon::createFromFormat('Y/m/d', $raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, Player>  $playersByStartingRank
     * @return array<int, ProcessedPlayerSummary> keyed by starting rank
     */
    private function initializeSummaries(
        ParsedTournament $parsed,
        array $playersByStartingRank,
        Ranking $ranking,
        string $speed,
    ): array {
        $summaries = [];

        foreach ($parsed->players as $parsedPlayer) {
            $summary = new ProcessedPlayerSummary;
            $summary->startingRank = $parsedPlayer->startingRank;
            $summary->playerName = trim("{$parsedPlayer->lastName}, {$parsedPlayer->name}");
            $summary->sourceRatingId = $parsedPlayer->ratingId;
            $summary->position = $parsedPlayer->rank;
            $summary->points = $parsedPlayer->points;
            $summary->eloFromFile = (float) $parsedPlayer->rating;

            $player = $playersByStartingRank[$parsedPlayer->startingRank] ?? null;
            $summary->player = $player;

            if ($player) {
                $playerRanking = PlayerRanking::where('ranking_id', $ranking->id)
                    ->where('player_id', $player->id)
                    ->first();

                if ($playerRanking) {
                    $summary->playerRankingId = $playerRanking->id;
                    $summary->eloStart = $playerRanking->eloFor($speed);
                    $summary->kFactor = $playerRanking->kFor($speed);
                }
            }

            $summaries[$parsedPlayer->startingRank] = $summary;
        }

        return $summaries;
    }

    /**
     * @param  list<Round>  $rounds
     * @param  Collection<int, ParsedPlayer>  $parsedByStartingRank
     * @param  array<int, Player>  $playersByStartingRank
     * @param  array<int, ProcessedPlayerSummary>  $summaries
     */
    private function createGamesAndAccumulateResults(
        Tournament $tournament,
        Ranking $ranking,
        string $speed,
        array $rounds,
        Collection $parsedByStartingRank,
        array $playersByStartingRank,
        array $summaries,
    ): int {
        $gamesCreated = 0;

        foreach ($rounds as $roundIndex => $round) {
            foreach ($parsedByStartingRank as $startingRank => $parsedPlayer) {
                $game = $parsedPlayer->games[$roundIndex] ?? null;

                if ($game === null) {
                    continue;
                }

                if ($game->color === null) {
                    Game::create([
                        'tournament_id' => $tournament->id,
                        'round_id' => $round->id,
                        'ranking_id' => $ranking->id,
                        'bye_player_id' => $playersByStartingRank[$startingRank]->id ?? null,
                        'no_game' => true,
                        'bye_type' => $this->byeType($game->rawResult),
                        'speed' => $speed,
                    ]);
                    $gamesCreated++;

                    continue;
                }

                // Every real pairing is reported from both sides; only act
                // on the white side to create exactly one Game row per pairing.
                if ($game->color !== 'white') {
                    continue;
                }

                $rivalRank = $game->rivalStartingRank;
                $rivalParsed = $parsedByStartingRank->get($rivalRank);
                $rivalGame = $rivalParsed?->games[$roundIndex] ?? null;

                [$whiteResult, $whiteForfeit] = $this->normalizeResult($game->rawResult);
                [$blackResult, $blackForfeit] = $this->normalizeResult($rivalGame?->rawResult ?? $game->rawResult);
                $forfeit = $whiteForfeit || $blackForfeit;

                $whiteSummary = $summaries[$startingRank];
                $blackSummary = $summaries[$rivalRank] ?? null;

                $whiteElo = (int) ($whiteSummary->playerRankingId ? $whiteSummary->eloStart : $whiteSummary->eloFromFile);
                $whiteK = $whiteSummary->playerRankingId ? $whiteSummary->kFactor : 0;
                $blackElo = (int) ($blackSummary?->playerRankingId ? $blackSummary->eloStart : ($blackSummary?->eloFromFile ?? 0));
                $blackK = $blackSummary?->playerRankingId ? $blackSummary->kFactor : 0;

                $whiteVariation = 0.0;
                $blackVariation = 0.0;

                if (! $forfeit && $whiteElo > 0 && $blackElo > 0) {
                    $variation = $this->calculator->variation($whiteElo, $whiteK, $blackElo, $blackK, $whiteResult, $blackResult);
                    $whiteVariation = $variation['white'];
                    $blackVariation = $variation['black'];
                }

                $createdGame = Game::create([
                    'tournament_id' => $tournament->id,
                    'round_id' => $round->id,
                    'ranking_id' => $ranking->id,
                    'white_player_id' => $playersByStartingRank[$startingRank]->id ?? null,
                    'black_player_id' => $playersByStartingRank[$rivalRank]->id ?? null,
                    'white_elo' => $whiteElo ?: null,
                    'white_k' => $whiteK ?: null,
                    'white_variation' => $forfeit ? null : $whiteVariation,
                    'white_result' => $whiteResult,
                    'black_elo' => $blackElo ?: null,
                    'black_k' => $blackK ?: null,
                    'black_variation' => $forfeit ? null : $blackVariation,
                    'black_result' => $blackResult,
                    'forfeit' => $forfeit,
                    'speed' => $speed,
                ]);
                $gamesCreated++;

                if (! $forfeit) {
                    if ($whiteSummary->playerRankingId) {
                        $whiteSummary->gameResults[] = [
                            'variation' => $whiteVariation, 'rivalElo' => (float) $blackElo,
                            'score' => $whiteResult, 'gameId' => $createdGame->id,
                        ];
                        $whiteSummary->totalVariation += $whiteVariation;
                    }
                    if ($blackSummary?->playerRankingId) {
                        $blackSummary->gameResults[] = [
                            'variation' => $blackVariation, 'rivalElo' => (float) $whiteElo,
                            'score' => $blackResult, 'gameId' => $createdGame->id,
                        ];
                        $blackSummary->totalVariation += $blackVariation;
                    }
                }
            }
        }

        return $gamesCreated;
    }

    /**
     * @return array{0: float, 1: bool} [numericResult, isForfeit]
     */
    private function normalizeResult(string $raw): array
    {
        return match ($raw) {
            '1' => [1.0, false],
            '=' => [0.5, false],
            '0' => [0.0, false],
            '+' => [1.0, true],
            '-' => [0.0, true],
            default => [0.0, false],
        };
    }

    private function byeType(string $raw): ?string
    {
        return match (true) {
            in_array($raw, ['U', 'u'], true) => 'unpaired',
            in_array($raw, ['H', 'h'], true) => 'half_point',
            in_array($raw, ['F', 'f'], true) => 'full_point',
            default => 'zero_point',
        };
    }

    /**
     * @param  array<int, ProcessedPlayerSummary>  $summaries
     */
    private function applyResultsToRankings(Ranking $ranking, string $speed, array $summaries): void
    {
        $column = "valuation_games_{$speed}";

        foreach ($summaries as $summary) {
            if (! $summary->playerRankingId) {
                continue;
            }

            $playerRanking = PlayerRanking::find($summary->playerRankingId);

            if (! $playerRanking) {
                continue;
            }

            $cumulativeGames = $playerRanking->gamesPlayed($speed);
            $cumulativeScore = $playerRanking->scoreSum($speed);
            $cumulativeRivalElo = $playerRanking->rivalEloSum($speed);
            $runningElo = $summary->eloStart;
            $enteringRanking = false;

            foreach ($summary->gameResults as $result) {
                $cumulativeGames++;
                $cumulativeScore += $result['score'];
                $cumulativeRivalElo += $result['rivalElo'];
                $enteringRanking = $cumulativeGames >= $ranking->{$column} && $playerRanking->eloFor($speed) == 0;

                $lifetimePerformance = $this->calculator->performance(
                    $cumulativeGames, $cumulativeScore, $cumulativeRivalElo, $enteringRanking,
                );

                PerformanceVariation::create([
                    'player_ranking_id' => $playerRanking->id,
                    'player_id' => $playerRanking->player_id,
                    'game_id' => $result['gameId'],
                    'type' => $speed,
                    'rival_elo' => $result['rivalElo'],
                    'score' => $result['score'],
                    'performance' => $lifetimePerformance,
                    'variation' => $result['variation'],
                    'elo_before' => $runningElo,
                    'elo_after' => $runningElo + $result['variation'],
                ]);

                $runningElo += $result['variation'];
            }

            if ($enteringRanking) {
                $this->enterRanking($playerRanking, $ranking, $speed, $this->calculator->performance(
                    $cumulativeGames, $cumulativeScore, $cumulativeRivalElo, true,
                ));
            } else {
                $playerRanking->{"{$speed}_elo"} = $summary->eloStart + $summary->totalVariation;
                $this->applyCategoryKFactor($playerRanking, $ranking, $speed);
                $this->resetIfBelowThreshold($playerRanking, $ranking, $speed);
            }

            $playerRanking->save();

            // Tournament-only performance (not the lifetime one above).
            $summary->points = $summary->points; // unchanged, comes straight from the file
        }
    }

    private function enterRanking(PlayerRanking $playerRanking, Ranking $ranking, string $speed, float $performance): void
    {
        $playerRanking->{"k_{$speed}_elo"} = $ranking->k_base;
        $playerRanking->{"{$speed}_elo"} = $performance;

        $this->applyCategoryKFactor($playerRanking, $ranking, $speed);
        $this->applyUnderageKFactor($playerRanking, $ranking, $speed);

        if ($performance > $ranking->ranking_base && $performance <= $ranking->ranking_max_entry) {
            $playerRanking->{"{$speed}_elo"} = $performance;
        } elseif ($performance > $ranking->ranking_max_entry) {
            $playerRanking->{"{$speed}_elo"} = $ranking->ranking_max_entry;
        } elseif ($performance < $ranking->ranking_base) {
            PerformanceVariation::where('player_ranking_id', $playerRanking->id)->where('type', $speed)->delete();
            $playerRanking->{"{$speed}_elo"} = 0;
            $playerRanking->{"k_{$speed}_elo"} = 0;
        }
    }

    private function applyCategoryKFactor(PlayerRanking $playerRanking, Ranking $ranking, string $speed): void
    {
        $rules = $ranking->k_rules ?? [];

        if (empty($rules['rank_category'])) {
            return;
        }

        $bands = $ranking->{"{$speed}_rules"} ?? [];
        $elo = $playerRanking->eloFor($speed);

        foreach ($bands as $band) {
            [$min, $max, $k] = $band;
            if ($elo >= $min && $elo <= $max) {
                $playerRanking->{"k_{$speed}_elo"} = $k;
                break;
            }
        }
    }

    private function applyUnderageKFactor(PlayerRanking $playerRanking, Ranking $ranking, string $speed): void
    {
        $rules = $ranking->k_rules ?? [];

        if (empty($rules['rank_underage']) || ! $playerRanking->player?->birth_date) {
            return;
        }

        $age = $playerRanking->player->birth_date->diffInYears(now());

        if ($age < 18) {
            $playerRanking->{"k_{$speed}_elo"} = $ranking->k_base;
        }
    }

    private function resetIfBelowThreshold(PlayerRanking $playerRanking, Ranking $ranking, string $speed): void
    {
        if ($playerRanking->eloFor($speed) >= $ranking->ranking_restart) {
            return;
        }

        PerformanceVariation::where('player_ranking_id', $playerRanking->id)->where('type', $speed)->delete();
        $playerRanking->{"{$speed}_elo"} = 0;
        $playerRanking->{"k_{$speed}_elo"} = 0;
    }

    /**
     * @param  array<int, ProcessedPlayerSummary>  $summaries
     */
    private function savePlayerTournamentRows(Tournament $tournament, string $speed, array $summaries): void
    {
        foreach ($summaries as $summary) {
            $gamesPlayed = count($summary->gameResults);
            $pointsScored = array_sum(array_column($summary->gameResults, 'score'));
            $rivalEloSum = array_sum(array_column($summary->gameResults, 'rivalElo'));

            $tournamentPerformance = $this->calculator->performance($gamesPlayed, $pointsScored, $rivalEloSum, false);

            $playerRanking = $summary->playerRankingId ? PlayerRanking::find($summary->playerRankingId) : null;

            PlayerTournament::create([
                'tournament_id' => $tournament->id,
                'player_id' => $summary->player?->id,
                'player_name' => $summary->playerName,
                'source_rating_id' => $summary->sourceRatingId,
                'k_factor' => $summary->kFactor,
                'points' => $summary->points,
                'performance' => $tournamentPerformance,
                'elo_variation' => $summary->totalVariation,
                'position' => $summary->position,
                'starting_rank' => $summary->startingRank,
                'elo_from_file' => $summary->eloFromFile,
                'elo_start' => $summary->eloStart,
                'elo_end' => $playerRanking?->eloFor($speed) ?? $summary->eloEnd(),
            ]);
        }
    }

    /**
     * Podium (1st/2nd/3rd) plus best-of-category awards, stored as
     * {label: player_id} JSON on the tournament — ported from 1.0's
     * RankingService::categoryAwards().
     */
    private function computeCategoryAwards(Tournament $tournament, Ranking $ranking, string $speed): void
    {
        $rules = $ranking->k_rules ?? [];

        if (empty($rules['rank_category'])) {
            return;
        }

        $bands = $ranking->{"{$speed}_rules"} ?? [];
        $rows = PlayerTournament::where('tournament_id', $tournament->id)->orderBy('position')->get();
        $awards = [];
        $categoryLabels = ['Mejor cuarta o No Rank' => 40, 'Mejor tercera' => 30, 'Mejor segunda' => 20, 'Mejor primera' => 10];

        foreach ($rows as $row) {
            if ($row->position === 1) {
                $awards['Primero'] = $row->player_id;
            } elseif ($row->position === 2) {
                $awards['Segundo'] = $row->player_id;
            } elseif ($row->position === 3) {
                $awards['Tercero'] = $row->player_id;
            }

            if ($row->position > 3) {
                foreach ($bands as $band) {
                    [$min, $max, $bandK] = $band;
                    if ($row->elo_from_file < $min || $row->elo_from_file > $max) {
                        continue;
                    }

                    $label = array_search($bandK, $categoryLabels, true);

                    if ($label && ! isset($awards[$label])) {
                        $awards[$label] = $row->player_id;
                    }
                }
            }

            if (count(array_intersect_key($categoryLabels, $awards)) === count($categoryLabels)) {
                break;
            }
        }

        $tournament->update(['awards' => $awards]);
    }
}
