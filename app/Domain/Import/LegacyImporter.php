<?php

namespace App\Domain\Import;

use App\Enums\TeamRole;
use App\Models\Competition;
use App\Models\CompetitionPlayer;
use App\Models\Game;
use App\Models\PerformanceVariation;
use App\Models\Player;
use App\Models\PlayerRanking;
use App\Models\PlayerTournament;
use App\Models\Ranking;
use App\Models\Round;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentArbiter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-time importer for a *copy* of the LocalChess 1.0 database (see Fase 6
 * of the rebuild plan). Reads from the `legacy` connection, writes to the
 * default connection, and remaps every foreign key through an in-memory
 * old-id -> new-id table since every 2.0 table starts from a fresh
 * auto-increment sequence.
 *
 * Rows that can't be attributed to a federation (1.0 allowed
 * tournaments/competitions/rankings with no federation link at all — one of
 * the bugs this rebuild fixes) are skipped and counted separately, since 2.0
 * requires that link. Blog/posts/tags are never read at all.
 */
class LegacyImporter
{
    private const LEGACY = 'legacy';

    /** @var array<int, int> */
    private array $teamIds = [];

    /** @var array<int, int> */
    private array $rankingIds = [];

    /** @var array<int, int> */
    private array $playerIds = [];

    /** @var array<int, int> */
    private array $competitionIds = [];

    /** @var array<int, int> */
    private array $tournamentIds = [];

    /** @var array<int, int> */
    private array $roundIds = [];

    /** @var array<int, int> */
    private array $playerRankingIds = [];

    /** @var array<int, int> */
    private array $gameIds = [];

    private ImportReport $report;

    public function import(string $ownerEmail): ImportReport
    {
        $owner = User::where('email', $ownerEmail)->firstOrFail();

        $this->report = new ImportReport;

        DB::transaction(function () use ($owner) {
            $this->importFederations($owner);
            $this->importRankings();
            $this->importPlayers();
            $this->importPlayerRankings();
            $this->importCompetitions();
            $this->importTournaments();
            $this->importRounds();
            $this->importGames();
            $this->importPlayerTournaments();
            $this->importCompetitionPlayers();
            $this->importPerformanceVariations();
            $this->importArbiters();
        });

        return $this->report;
    }

    private function legacy(string $table)
    {
        return DB::connection(self::LEGACY)->table($table);
    }

    /**
     * Federations import as Teams in two passes, since a child federation's
     * `federation_id` (its parent) may point to a federation created later
     * in id order.
     */
    private function importFederations(User $owner): void
    {
        $rows = $this->legacy('federations')->orderBy('id')->get();
        $this->report->source('federations', $rows->count());

        foreach ($rows as $row) {
            $team = Team::create([
                'name' => $row->name,
                'is_personal' => false,
                'acronym' => $row->acronym,
                'city' => $row->city,
                'state' => $row->state,
                'country' => $row->country,
                'address' => $row->adress,
                'website' => $row->website,
                'email' => $row->email,
                'phone' => $row->phone,
            ]);

            $team->memberships()->create(['user_id' => $owner->id, 'role' => TeamRole::Owner]);

            $this->teamIds[$row->id] = $team->id;
            $this->report->imported('teams');
        }

        foreach ($rows as $row) {
            if ($row->federation_id && isset($this->teamIds[$row->federation_id])) {
                Team::whereKey($this->teamIds[$row->id])->update([
                    'parent_team_id' => $this->teamIds[$row->federation_id],
                ]);
            }
        }
    }

    /**
     * A ranking's owner is the earliest federation that adopted it (by
     * `federation_ranking.id`) — 1.0 had no explicit owner concept. Every
     * linked federation, owner included, gets a `ranking_team` row, matching
     * how RankingController::store() links a newly-created ranking today.
     */
    private function importRankings(): void
    {
        $rows = $this->legacy('rankings')->orderBy('id')->get();
        $this->report->source('rankings', $rows->count());

        $links = $this->legacy('federation_ranking')->orderBy('id')->get()->groupBy('ranking_id');

        foreach ($rows as $row) {
            $rankingLinks = $links->get($row->id, collect());
            $ownerLink = $rankingLinks->first(fn ($link) => isset($this->teamIds[$link->federation_id]));

            if (! $ownerLink) {
                $this->report->skipped('rankings', "ranking #{$row->id} ({$row->name}) has no known federation");

                continue;
            }

            $ranking = Ranking::create([
                'owner_team_id' => $this->teamIds[$ownerLink->federation_id],
                'name' => $row->name,
                'acronym' => $row->acronym,
                'is_default' => (bool) $row->is_default,
                'live_ranking' => (bool) $row->live_ranking,
                'valuation_games_standard' => $row->valuation_games_standar ?? 5,
                'valuation_games_rapid' => $row->valuation_games_rapid ?? 5,
                'valuation_games_blitz' => $row->valuation_games_blitz ?? 5,
                'id_base' => $row->id_base,
                'ranking_base' => $row->ranking_base ?? 1400,
                'ranking_restart' => $row->ranking_restart ?? 1000,
                'ranking_max_entry' => $row->ranking_max_entry ?? 2200,
                'last_used_id' => $row->last_used_id ?? 0,
                'state' => (bool) $row->state,
                'k_base' => $row->k_base ?? 40,
                'k_blitz' => $row->k_blitz ?? 0.6,
                'k_rapid' => $row->k_rapid ?? 0.8,
                'k_age' => $row->k_age ?? 40,
                'k_games_amount' => $row->k_games_amount ?? 30,
                'k_downgrade' => $row->k_downgrade ?? 10,
                'k_rules' => $this->decodeJson($row->k_rules),
                'blitz_rules' => $this->decodeJson($row->blitz),
                'rapid_rules' => $this->decodeJson($row->rapid),
                'standard_rules' => $this->decodeJson($row->standar),
            ]);

            $this->rankingIds[$row->id] = $ranking->id;
            $this->report->imported('rankings');

            foreach ($rankingLinks as $link) {
                if (! isset($this->teamIds[$link->federation_id])) {
                    continue;
                }

                $ranking->teams()->syncWithoutDetaching([
                    $this->teamIds[$link->federation_id] => ['is_default' => (bool) $link->default],
                ]);
            }
        }
    }

    private function importPlayers(): void
    {
        $rows = $this->legacy('players')->orderBy('id')->get();
        $this->report->source('players', $rows->count());

        foreach ($rows as $row) {
            $player = Player::create([
                'club_team_id' => $row->club_id && isset($this->teamIds[$row->club_id]) ? $this->teamIds[$row->club_id] : null,
                'name' => $row->name ?? '',
                'last_name' => $row->last_name,
                'gender' => $row->gender === null ? null : (bool) $row->gender,
                'title' => $this->normalizeTitle($row->title),
                'arbiter_title' => $row->arbiter_title,
                'is_arbiter' => (bool) $row->arbiter,
                'is_organizer' => (bool) $row->organizer,
                'country' => $row->country,
                'club_name' => $row->club_name,
                'birth_date' => $row->birth_date,
                'image' => $row->image,
                'details' => $row->details,
            ]);

            $this->playerIds[$row->id] = $player->id;
            $this->report->imported('players');
        }
    }

    private function importPlayerRankings(): void
    {
        $rows = $this->legacy('player_ranking')->orderBy('id')->get();
        $this->report->source('player_ranking', $rows->count());

        foreach ($rows as $row) {
            if (! isset($this->rankingIds[$row->ranking_id]) || ! isset($this->playerIds[$row->player_id])) {
                $this->report->skipped('player_ranking', "row #{$row->id} references a skipped ranking or player");

                continue;
            }

            $playerRanking = PlayerRanking::create([
                'ranking_id' => $this->rankingIds[$row->ranking_id],
                'player_id' => $this->playerIds[$row->player_id],
                'is_default' => (bool) $row->default,
                'standard_elo' => $row->standar_elo ?? 0,
                'rapid_elo' => $row->rapid_elo ?? 0,
                'blitz_elo' => $row->blitz_elo ?? 0,
                'k_standard_elo' => $row->k_standar_elo ?? 0,
                'k_rapid_elo' => $row->k_rapid_elo ?? 0,
                'k_blitz_elo' => $row->k_blitz_elo ?? 0,
                'id_on_ranking' => $row->id_on_ranking,
            ]);

            $this->playerRankingIds[$row->id] = $playerRanking->id;
            $this->report->imported('player_ranking');
        }
    }

    private function importCompetitions(): void
    {
        $rows = $this->legacy('competitions')->orderBy('id')->get();
        $this->report->source('competitions', $rows->count());

        foreach ($rows as $row) {
            if (! $row->federation_id || ! isset($this->teamIds[$row->federation_id])) {
                $this->report->skipped('competitions', "competition #{$row->id} ({$row->name}) has no known federation");

                continue;
            }

            $competition = Competition::create([
                'team_id' => $this->teamIds[$row->federation_id],
                'ranking_id' => $row->ranking_id && isset($this->rankingIds[$row->ranking_id]) ? $this->rankingIds[$row->ranking_id] : null,
                'name' => $row->name,
                'acronym' => $row->acronym,
                'url' => $row->url ?? Str::slug($row->name).'-'.$row->id,
                'speed' => $this->normalizeSpeed($row->speed),
                'discarded_games' => $row->discarded_games ?? 0,
                'tournaments_amount' => $row->tournaments_amount ?? 0,
                'players_amount' => $row->players_amount ?? 0,
                'games_amount' => $row->games_amount ?? 0,
                'in_progress' => (bool) $row->in_progress,
                'event_stage' => $row->event_stage,
                'rules' => $this->decodeJson($row->rules),
                'begins' => $row->begins,
                'ends' => $row->ends,
            ]);

            $this->competitionIds[$row->id] = $competition->id;
            $this->report->imported('competitions');
        }
    }

    private function importTournaments(): void
    {
        $rows = $this->legacy('tournaments')->orderBy('id')->get();
        $this->report->source('tournaments', $rows->count());

        foreach ($rows as $row) {
            if (! $row->federation_id || ! isset($this->teamIds[$row->federation_id])) {
                $this->report->skipped('tournaments', "tournament #{$row->id} ({$row->name}) has no known federation");

                continue;
            }

            $tournament = Tournament::create([
                'team_id' => $this->teamIds[$row->federation_id],
                'competition_id' => $row->competition_id && isset($this->competitionIds[$row->competition_id]) ? $this->competitionIds[$row->competition_id] : null,
                'ranking_id' => $row->ranking_id && isset($this->rankingIds[$row->ranking_id]) ? $this->rankingIds[$row->ranking_id] : null,
                'main_arbiter_id' => $row->main_arbiter_id && isset($this->playerIds[$row->main_arbiter_id]) ? $this->playerIds[$row->main_arbiter_id] : null,
                'organizer_id' => $row->organizer_id && isset($this->playerIds[$row->organizer_id]) ? $this->playerIds[$row->organizer_id] : null,
                'director_id' => $row->director_id && isset($this->playerIds[$row->director_id]) ? $this->playerIds[$row->director_id] : null,
                'source_reference' => $row->tournament_sm_cr_id,
                'name' => $row->name ?? 'Torneo sin nombre',
                'place' => $row->place,
                'city' => $row->city,
                'state' => $row->state,
                'country' => $row->country,
                'details' => $row->details,
                'more_details' => $row->more_details,
                'rounds_count' => $row->rounds !== null ? (int) $row->rounds : null,
                'time_control' => $row->time_control,
                'type' => $row->type,
                'organizer_name' => $row->organizer,
                'pairing_software' => $row->paring_software,
                'main_arbiter_name' => $row->main_arbiter,
                'other_arbiters' => $row->other_arbiters,
                'speed' => $this->normalizeSpeed($row->speed),
                'player_average' => $row->player_average,
                'amount_games' => $row->amount_games,
                'amount_players' => $row->amount_players,
                'amount_players_rank' => $row->amount_players_rank,
                'is_team_event' => (bool) $row->teams,
                'squads_count' => $row->amount_teams,
                'processed' => (bool) $row->processed,
                'begins' => $row->begins,
                'ends' => $row->ends,
                'data' => $this->decodeJson($row->data),
                'awards' => $this->decodeJson($row->awards),
            ]);

            $this->tournamentIds[$row->id] = $tournament->id;
            $this->report->imported('tournaments');
        }
    }

    private function importRounds(): void
    {
        $rows = $this->legacy('rounds')->orderBy('id')->get();
        $this->report->source('rounds', $rows->count());

        foreach ($rows as $row) {
            if (! isset($this->tournamentIds[$row->tournament_id])) {
                $this->report->skipped('rounds', "round #{$row->id} belongs to a skipped tournament");

                continue;
            }

            $round = Round::create([
                'tournament_id' => $this->tournamentIds[$row->tournament_id],
                'number' => $row->number,
                'date' => $row->date,
                'time' => $row->time,
            ]);

            $this->roundIds[$row->id] = $round->id;
            $this->report->imported('rounds');
        }
    }

    /**
     * 1.0 stored one row per pairing with `white`/`black` player columns, a
     * combined `result` string like "1/0" (raw TRF16 codes, '/' separated),
     * and separate "nogame" rows (result === 'nogame') for byes — carrying
     * the sitting-out player in `player_id` and a human `details` string.
     * 2.0 normalizes both shapes into white_result/black_result decimals
     * plus a dedicated bye_player_id/bye_type, mirroring what
     * TournamentProcessor now writes for newly-processed tournaments.
     */
    private function importGames(): void
    {
        $rows = $this->legacy('games')->orderBy('id')->get();
        $this->report->source('games', $rows->count());

        foreach ($rows as $row) {
            if (! isset($this->tournamentIds[$row->tournament_id]) || ! isset($this->roundIds[$row->round_id])) {
                $this->report->skipped('games', "game #{$row->id} belongs to a skipped tournament or round");

                continue;
            }

            $isBye = $row->result === 'nogame';

            $attributes = [
                'tournament_id' => $this->tournamentIds[$row->tournament_id],
                'round_id' => $this->roundIds[$row->round_id],
                'ranking_id' => $row->ranking_id && isset($this->rankingIds[$row->ranking_id]) ? $this->rankingIds[$row->ranking_id] : null,
                'speed' => $this->normalizeSpeed($row->speed),
                'pgn' => $row->png,
                'played_at' => $row->datetime,
            ];

            if ($isBye) {
                $attributes += [
                    'bye_player_id' => $row->player_id && isset($this->playerIds[$row->player_id]) ? $this->playerIds[$row->player_id] : null,
                    'bye_type' => $this->normalizeByeType($row->details),
                    'no_game' => true,
                ];
            } else {
                [$whiteCode, $blackCode] = array_pad(explode('/', (string) $row->result, 2), 2, null);
                $whiteForfeit = in_array($whiteCode, ['+', '-'], true);
                $blackForfeit = in_array($blackCode, ['+', '-'], true);

                $attributes += [
                    'white_player_id' => $row->white && isset($this->playerIds[$row->white]) ? $this->playerIds[$row->white] : null,
                    'black_player_id' => $row->black && isset($this->playerIds[$row->black]) ? $this->playerIds[$row->black] : null,
                    'white_elo' => $row->white_elo,
                    'white_k' => $row->white_k,
                    'white_variation' => $row->white_variation,
                    'white_result' => $this->normalizeResultCode($whiteCode),
                    'black_elo' => $row->black_elo,
                    'black_k' => $row->black_k,
                    'black_variation' => $row->black_variation,
                    'black_result' => $this->normalizeResultCode($blackCode),
                    'forfeit' => $whiteForfeit || $blackForfeit,
                    'no_game' => false,
                ];
            }

            $game = Game::create($attributes);

            $this->gameIds[$row->id] = $game->id;
            $this->report->imported('games');
        }
    }

    private function importPlayerTournaments(): void
    {
        $rows = $this->legacy('player_tournament')->orderBy('id')->get();
        $this->report->source('player_tournament', $rows->count());

        foreach ($rows as $row) {
            if (! isset($this->tournamentIds[$row->tournament_id])) {
                $this->report->skipped('player_tournament', "row #{$row->id} belongs to a skipped tournament");

                continue;
            }

            PlayerTournament::create([
                'tournament_id' => $this->tournamentIds[$row->tournament_id],
                'player_id' => $row->player_id && isset($this->playerIds[$row->player_id]) ? $this->playerIds[$row->player_id] : null,
                'player_name' => $row->player_name,
                'source_rating_id' => $row->rating_id,
                'k_factor' => $row->k_factor,
                'points' => $row->points,
                'performance' => $row->performance,
                'elo_variation' => $row->elo_variation,
                'position' => $row->position,
                'starting_rank' => $row->rank_start,
                'elo_from_file' => $row->elo_file,
                'elo_start' => $row->elo_start,
                'elo_end' => $row->elo_end,
                'is_organizer' => (bool) $row->is_organizer,
                'is_arbiter' => (bool) $row->is_arbiter,
                'is_director' => (bool) $row->is_director,
            ]);

            $this->report->imported('player_tournament');
        }
    }

    private function importCompetitionPlayers(): void
    {
        $rows = $this->legacy('competition_player')->orderBy('id')->get();
        $this->report->source('competition_player', $rows->count());

        foreach ($rows as $row) {
            if (! isset($this->competitionIds[$row->competition_id]) || ! isset($this->playerIds[$row->player_id])) {
                $this->report->skipped('competition_player', "row #{$row->id} references a skipped competition or player");

                continue;
            }

            CompetitionPlayer::create([
                'competition_id' => $this->competitionIds[$row->competition_id],
                'player_id' => $this->playerIds[$row->player_id],
                'position' => $row->position,
                'tournament_amount' => $row->tournament_amount,
                'join_event_stage' => $row->join_event_stage,
                'played_events' => $row->played_events,
                'discarded_tournaments' => $row->discarded_tournaments,
                'discarded_tournament_ids' => $this->remapTournamentIdList($row->discarded_tournaments_id),
                'points' => $this->decodeJson($row->points),
                'discarded_points' => $this->decodeJson($row->discarded_points),
                'counted_tournament_ids' => $this->remapTournamentIdList($row->counted_tournaments_id),
                'played_tournament_ids' => $this->remapTournamentIdList($row->played_tournaments_id),
                'performances' => $this->decodeJson($row->competition_performance),
                'elo_variations' => $this->decodeJson($row->competition_elo_variation),
                'scoreboard' => $row->scoreboards,
                'performance' => $row->performance,
                'variation' => $row->variation,
            ]);

            $this->report->imported('competition_player');
        }
    }

    private function importPerformanceVariations(): void
    {
        $rows = $this->legacy('performance_variations')->orderBy('id')->get();
        $this->report->source('performance_variations', $rows->count());

        foreach ($rows as $row) {
            if (! isset($this->playerRankingIds[$row->player_ranking_id]) || ! isset($this->playerIds[$row->player_id])) {
                $this->report->skipped('performance_variations', "row #{$row->id} references a skipped player_ranking or player");

                continue;
            }

            PerformanceVariation::create([
                'player_ranking_id' => $this->playerRankingIds[$row->player_ranking_id],
                'player_id' => $this->playerIds[$row->player_id],
                'game_id' => $row->game_id && isset($this->gameIds[$row->game_id]) ? $this->gameIds[$row->game_id] : null,
                'type' => $this->normalizeSpeed($row->type),
                'rival_elo' => $row->valuation_rival_elo ?? 0,
                'score' => $row->valuation_score ?? 0,
                'performance' => $row->valuation_performance ?? 0,
                'variation' => $row->variation ?? 0,
                'elo_before' => $row->elo_before ?? 0,
                'elo_after' => $row->elo_after ?? 0,
            ]);

            $this->report->imported('performance_variations');
        }
    }

    private function importArbiters(): void
    {
        $rows = $this->legacy('arbiter_tournament')->orderBy('id')->get();
        $this->report->source('arbiter_tournament', $rows->count());

        foreach ($rows as $row) {
            if (! isset($this->tournamentIds[$row->tournament_id]) || ! $row->player_id || ! isset($this->playerIds[$row->player_id])) {
                $this->report->skipped('arbiter_tournament', "row #{$row->id} references a skipped tournament or player");

                continue;
            }

            TournamentArbiter::create([
                'tournament_id' => $this->tournamentIds[$row->tournament_id],
                'player_id' => $this->playerIds[$row->player_id],
                'role' => $this->normalizeArbiterRole($row->position),
            ]);

            $this->report->imported('tournament_arbiters');
        }
    }

    private function normalizeSpeed(?string $speed): string
    {
        $speed = strtolower(trim((string) $speed));

        // 1.0 stores this as the typo'd 'standar' (same typo as standar_elo).
        if ($speed === 'standar') {
            return 'standard';
        }

        return in_array($speed, ['standard', 'rapid', 'blitz'], true) ? $speed : 'blitz';
    }

    private function normalizeResultCode(?string $code): ?float
    {
        return match ($code) {
            '1', '+' => 1.0,
            '=' => 0.5,
            '0', '-' => 0.0,
            default => null,
        };
    }

    /**
     * 1.0's `position` was a free-text string (e.g. "Árbitro principal");
     * 2.0 restricts this to an enum, so anything that reads as "principal"/
     * "main"/"chief"/"head" becomes 'main', everything else 'assistant'.
     */
    private function normalizeArbiterRole(?string $position): string
    {
        $position = strtolower((string) $position);

        foreach (['principal', 'main', 'chief', 'head'] as $needle) {
            if (str_contains($position, $needle)) {
                return 'main';
            }
        }

        return 'assistant';
    }

    private function normalizeTitle(?string $title): ?string
    {
        $title = strtoupper(trim((string) $title));

        return in_array($title, ['CM', 'FM', 'IM', 'GM', 'WCM', 'WFM', 'WIM', 'WGM'], true) ? $title : null;
    }

    private function normalizeByeType(?string $details): ?string
    {
        return match ($details) {
            'Unpaired bye' => 'unpaired',
            'Half point bye' => 'half_point',
            'Full point bye' => 'full_point',
            'Zero point bye' => 'zero_point',
            default => null,
        };
    }

    private function decodeJson(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_array($value) ? $value : json_decode((string) $value, true);
    }

    /**
     * @return array<int, int>|null
     */
    private function remapTournamentIdList(mixed $raw): ?array
    {
        $ids = $this->decodeJson($raw);

        if ($ids === null) {
            return null;
        }

        return collect($ids)
            ->map(fn ($id) => $this->tournamentIds[$id] ?? null)
            ->filter()
            ->values()
            ->all();
    }
}
