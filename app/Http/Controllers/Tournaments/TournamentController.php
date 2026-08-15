<?php

namespace App\Http\Controllers\Tournaments;

use App\Domain\Elo\CompetitionAggregator;
use App\Domain\Elo\Data\ParsedTournament;
use App\Domain\Elo\PlayerMatcher;
use App\Domain\Elo\PlayerRosterResolver;
use App\Domain\Elo\TournamentProcessor;
use App\Domain\Elo\Trf16Parser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tournaments\ProcessTournamentRequest;
use App\Http\Requests\Tournaments\UploadTournamentRequest;
use App\Models\Competition;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('viewAny', [Tournament::class, $current_team]);

        $search = $request->string('search')->trim()->value() ?: null;

        $tournaments = $current_team->tournaments()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('processed')
            ->orderByDesc('ends')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('tournaments/Index', [
            'tournaments' => $tournaments,
            'search' => $search,
        ]);
    }

    public function create(Team $current_team): Response
    {
        Gate::authorize('create', [Tournament::class, $current_team]);

        return Inertia::render('tournaments/Upload', [
            'rankings' => $current_team->ownedRankings()->where('state', true)->orderByDesc('is_default')->get(['id', 'name', 'acronym']),
        ]);
    }

    public function store(UploadTournamentRequest $request, Team $current_team, Trf16Parser $parser): RedirectResponse
    {
        $parsed = $parser->parse($request->file('file')->getRealPath());

        $tournament = $current_team->tournaments()->create([
            'ranking_id' => $request->integer('ranking_id'),
            'name' => $parsed->name ?? 'Torneo sin nombre',
            'place' => $parsed->place,
            'country' => $parsed->country,
            'type' => $parsed->type,
            'main_arbiter_name' => $parsed->mainArbiter,
            'other_arbiters' => $parsed->otherArbiters,
            'time_control' => $parsed->timeControl,
            'begins' => $parsed->begins,
            'ends' => $parsed->ends,
            'rounds_count' => $parsed->roundsCount(),
            'amount_players' => $parsed->amountPlayers,
            'amount_players_rank' => $parsed->amountPlayersRank,
            'is_team_event' => $parsed->hasTeams,
            'squads_count' => $parsed->amountTeams,
            'player_average' => $parsed->playerAverageRating,
            'data' => $parsed->toArray(),
            'processed' => false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Torneo cargado. Confirmá los datos antes de procesarlo.']);

        return to_route('tournaments.confirm', ['current_team' => $current_team->slug, 'tournament' => $tournament->id]);
    }

    public function confirm(Team $current_team, Tournament $tournament, PlayerMatcher $matcher): Response
    {
        Gate::authorize('process', $tournament);

        abort_if($tournament->processed, 409, 'Este torneo ya fue procesado.');

        $parsed = ParsedTournament::fromArray($tournament->data);
        $ranking = $tournament->ranking;

        $matches = collect($parsed->players)->map(function ($parsedPlayer) use ($matcher, $ranking) {
            $match = $matcher->match($parsedPlayer, $ranking->id);

            return [
                'startingRank' => $parsedPlayer->startingRank,
                'name' => $parsedPlayer->name,
                'lastName' => $parsedPlayer->lastName,
                'rating' => $parsedPlayer->rating,
                'ratingId' => $parsedPlayer->ratingId,
                'matchedPlayerId' => $match->player?->id,
                'matchedPlayerName' => $match->player ? trim("{$match->player->last_name}, {$match->player->name}") : null,
                'similarity' => $match->nameSimilarityPercent,
            ];
        })->values();

        return Inertia::render('tournaments/Confirm', [
            'tournament' => $tournament,
            'matches' => $matches,
            'competitions' => $current_team->competitions()->orderByDesc('in_progress')->get(['id', 'name', 'acronym']),
        ]);
    }

    public function process(ProcessTournamentRequest $request, Team $current_team, Tournament $tournament, Trf16Parser $parser): RedirectResponse
    {
        abort_if($tournament->processed, 409, 'Este torneo ya fue procesado.');

        $parsed = ParsedTournament::fromArray($tournament->data);
        $ranking = $tournament->ranking;

        $resolver = new PlayerRosterResolver(new PlayerMatcher);
        $playersByStartingRank = $resolver->resolve($parsed, $ranking, $request->array('sync_starting_ranks'));

        app(TournamentProcessor::class)->process($tournament, $parsed, $ranking, $request->string('speed')->value(), $playersByStartingRank);

        if ($competitionId = $request->integer('competition_id')) {
            $competition = Competition::findOrFail($competitionId);
            app(CompetitionAggregator::class)->addTournament($tournament->fresh(), $competition);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Torneo procesado exitosamente.']);

        return to_route('tournaments.show', ['current_team' => $current_team->slug, 'tournament' => $tournament->id]);
    }

    public function show(Team $current_team, Tournament $tournament): Response|RedirectResponse
    {
        Gate::authorize('view', $tournament);

        if (! $tournament->processed) {
            return to_route('tournaments.confirm', ['current_team' => $current_team->slug, 'tournament' => $tournament->id]);
        }

        $standings = $tournament->playerTournaments()
            ->with('player')
            ->orderBy('position')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('tournaments/Show', [
            'tournament' => $tournament->load('competition', 'ranking'),
            'standings' => $standings,
        ]);
    }

    public function destroy(Team $current_team, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('delete', $tournament);

        abort_if($tournament->processed, 409, 'No se puede eliminar un torneo ya procesado.');

        $tournament->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Torneo eliminado.']);

        return to_route('tournaments.index', ['current_team' => $current_team->slug]);
    }
}
