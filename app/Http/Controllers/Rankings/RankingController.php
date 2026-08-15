<?php

namespace App\Http\Controllers\Rankings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rankings\SaveRankingRequest;
use App\Models\Ranking;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('viewAny', [Ranking::class, $current_team]);

        $search = $request->string('search')->trim()->value() ?: null;

        $rankings = $current_team->ownedRankings()
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('acronym', 'like', "%{$search}%")))
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('rankings/Index', [
            'rankings' => $rankings,
            'search' => $search,
        ]);
    }

    public function create(Team $current_team): Response
    {
        Gate::authorize('create', [Ranking::class, $current_team]);

        return Inertia::render('rankings/Create');
    }

    public function store(SaveRankingRequest $request, Team $current_team): RedirectResponse
    {
        $ranking = $current_team->ownedRankings()->create([
            ...$request->validated(),
            'last_used_id' => 0,
            'k_rules' => ['rank_category' => false, 'rank_underage' => false],
        ]);

        $current_team->rankings()->syncWithoutDetaching([$ranking->id => ['is_default' => $ranking->is_default]]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ranking creado.']);

        return to_route('rankings.edit', ['current_team' => $current_team->slug, 'ranking' => $ranking->id]);
    }

    public function edit(Team $current_team, Ranking $ranking): Response
    {
        Gate::authorize('update', $ranking);

        return Inertia::render('rankings/Edit', [
            'ranking' => $ranking,
        ]);
    }

    public function update(SaveRankingRequest $request, Team $current_team, Ranking $ranking): RedirectResponse
    {
        $ranking->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ranking actualizado.']);

        return to_route('rankings.edit', ['current_team' => $current_team->slug, 'ranking' => $ranking->id]);
    }

    public function destroy(Team $current_team, Ranking $ranking): RedirectResponse
    {
        Gate::authorize('delete', $ranking);

        $ranking->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ranking eliminado.']);

        return to_route('rankings.index', ['current_team' => $current_team->slug]);
    }
}
