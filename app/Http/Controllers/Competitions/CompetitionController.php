<?php

namespace App\Http\Controllers\Competitions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competitions\SaveCompetitionRequest;
use App\Models\Competition;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionController extends Controller
{
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('viewAny', [Competition::class, $current_team]);

        $search = $request->string('search')->trim()->value() ?: null;

        $competitions = $current_team->competitions()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('begins')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('competitions/Index', [
            'competitions' => $competitions,
            'search' => $search,
        ]);
    }

    public function create(Team $current_team): Response
    {
        Gate::authorize('create', [Competition::class, $current_team]);

        return Inertia::render('competitions/Create', [
            'rankings' => $current_team->ownedRankings()->orderByDesc('is_default')->get(['id', 'name', 'acronym']),
        ]);
    }

    public function store(SaveCompetitionRequest $request, Team $current_team): RedirectResponse
    {
        $competition = $current_team->competitions()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Competencia creada.']);

        return to_route('competitions.show', ['current_team' => $current_team->slug, 'competition' => $competition->id]);
    }

    public function show(Team $current_team, Competition $competition): Response
    {
        Gate::authorize('view', $competition);

        $standings = $competition->competitionPlayers()
            ->with('player')
            ->orderBy('position')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('competitions/Show', [
            'competition' => $competition->load('ranking'),
            'standings' => $standings,
        ]);
    }

    public function edit(Team $current_team, Competition $competition): Response
    {
        Gate::authorize('update', $competition);

        return Inertia::render('competitions/Edit', [
            'competition' => $competition,
            'rankings' => $current_team->ownedRankings()->orderByDesc('is_default')->get(['id', 'name', 'acronym']),
        ]);
    }

    public function update(SaveCompetitionRequest $request, Team $current_team, Competition $competition): RedirectResponse
    {
        $competition->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Competencia actualizada.']);

        return to_route('competitions.edit', ['current_team' => $current_team->slug, 'competition' => $competition->id]);
    }

    public function destroy(Team $current_team, Competition $competition): RedirectResponse
    {
        Gate::authorize('delete', $competition);

        $competition->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Competencia eliminada.']);

        return to_route('competitions.index', ['current_team' => $current_team->slug]);
    }
}
