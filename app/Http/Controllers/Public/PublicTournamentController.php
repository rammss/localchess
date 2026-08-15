<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Game;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;
use Inertia\Inertia;
use Inertia\Response;

class PublicTournamentController extends Controller
{
    public function index(Team $team): Response
    {
        $tournaments = $team->tournaments()
            ->where('processed', true)
            ->orderByDesc('ends')
            ->paginate(15);

        return Inertia::render('public/tournaments/Index', [
            'team' => $team,
            'tournaments' => $tournaments,
        ]);
    }

    public function show(Team $team, Tournament $tournament): Response
    {
        return $this->renderShow($team, $tournament, null);
    }

    /**
     * Same standings view as show(), with a competition breadcrumb. Kept as
     * a distinct action (rather than an optional param on show()) because
     * Laravel resolves implicit route-model bindings by matching route
     * parameters to method parameters in URI order, not by name — a single
     * method can't safely serve both '{tournament}' and
     * '{competition}/{tournament}' URI shapes when the type-hints differ in
     * position. The view itself stays unified via renderShow().
     */
    public function showForCompetition(Team $team, Competition $competition, Tournament $tournament): Response
    {
        return $this->renderShow($team, $tournament, $competition);
    }

    private function renderShow(Team $team, Tournament $tournament, ?Competition $competition): Response
    {
        abort_unless($tournament->team_id === $team->id && $tournament->processed, 404);

        if ($competition) {
            abort_unless($tournament->competition_id === $competition->id, 404);
        }

        $standings = $tournament->playerTournaments()
            ->with('player:id,name,last_name,title')
            ->orderBy('position')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('public/tournaments/Show', [
            'team' => $team,
            'tournament' => $tournament->load('ranking:id,name,acronym'),
            'competition' => $competition,
            'standings' => $standings,
        ]);
    }

    public function player(Team $team, Tournament $tournament, Player $player): Response
    {
        return $this->renderPlayer($team, $tournament, $player, null);
    }

    /**
     * Same player-games view as player(), with a competition breadcrumb —
     * see the note on showForCompetition() for why this is a separate action.
     */
    public function playerForCompetition(Team $team, Competition $competition, Tournament $tournament, Player $player): Response
    {
        return $this->renderPlayer($team, $tournament, $player, $competition);
    }

    private function renderPlayer(Team $team, Tournament $tournament, Player $player, ?Competition $competition): Response
    {
        abort_unless($tournament->team_id === $team->id && $tournament->processed, 404);

        if ($competition) {
            abort_unless($tournament->competition_id === $competition->id, 404);
        }

        $summary = $tournament->playerTournaments()->where('player_id', $player->id)->first();

        abort_if($summary === null, 404);

        $games = Game::where('tournament_id', $tournament->id)
            ->where(fn ($query) => $query
                ->where('white_player_id', $player->id)
                ->orWhere('black_player_id', $player->id)
                ->orWhere('bye_player_id', $player->id))
            ->with(['round:id,number', 'white:id,name,last_name', 'black:id,name,last_name'])
            ->get()
            ->map(function (Game $game) use ($player) {
                $isWhite = $game->white_player_id === $player->id;
                $isBye = $game->bye_player_id === $player->id;
                $score = $isWhite ? $game->white_result : $game->black_result;

                return [
                    'id' => $game->id,
                    'round' => $game->round?->number,
                    'color' => $isBye ? null : ($isWhite ? 'white' : 'black'),
                    'opponent' => $isBye ? null : ($isWhite ? $game->black : $game->white),
                    'isBye' => $isBye,
                    'score' => $isBye ? null : ($score === null ? null : (float) $score),
                    'variation' => $isBye ? null : (float) ($isWhite ? $game->white_variation : $game->black_variation),
                    'forfeit' => $game->forfeit,
                ];
            })
            ->sortBy('round')
            ->values();

        return Inertia::render('public/tournaments/Player', [
            'team' => $team,
            'tournament' => $tournament->load('ranking:id,name,acronym'),
            'competition' => $competition,
            'player' => $player,
            'summary' => $summary,
            'games' => $games,
        ]);
    }
}
