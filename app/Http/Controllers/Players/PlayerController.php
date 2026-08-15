<?php

namespace App\Http\Controllers\Players;

use App\Exports\PlayersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Players\SavePlayerRequest;
use App\Imports\PlayerImport;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlayerController extends Controller
{
    public function index(Request $request, Team $current_team): Response
    {
        Gate::authorize('viewAny', Player::class);

        $search = $request->string('search')->trim()->value() ?: null;

        $players = Player::query()
            ->when($search, fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")))
            ->orderBy('last_name')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('players/Index', [
            'players' => $players,
            'search' => $search,
        ]);
    }

    public function create(Team $current_team): Response
    {
        Gate::authorize('create', Player::class);

        return Inertia::render('players/Create');
    }

    public function store(SavePlayerRequest $request, Team $current_team): RedirectResponse
    {
        $player = Player::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jugador creado.']);

        return to_route('players.edit', ['current_team' => $current_team->slug, 'player' => $player->id]);
    }

    public function edit(Team $current_team, Player $player): Response
    {
        Gate::authorize('update', $player);

        return Inertia::render('players/Edit', [
            'player' => $player,
        ]);
    }

    public function update(SavePlayerRequest $request, Team $current_team, Player $player): RedirectResponse
    {
        $player->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jugador actualizado.']);

        return to_route('players.edit', ['current_team' => $current_team->slug, 'player' => $player->id]);
    }

    public function destroy(Team $current_team, Player $player): RedirectResponse
    {
        Gate::authorize('delete', $player);

        $player->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jugador eliminado.']);

        return to_route('players.index', ['current_team' => $current_team->slug]);
    }

    public function importForm(Team $current_team): Response
    {
        Gate::authorize('create', Player::class);

        return Inertia::render('players/Import', [
            'rankings' => $current_team->ownedRankings()->orderByDesc('is_default')->get(['id', 'name', 'acronym']),
        ]);
    }

    public function import(Request $request, Team $current_team): RedirectResponse
    {
        Gate::authorize('create', Player::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
            'ranking_id' => ['required', 'integer', 'exists:rankings,id'],
        ]);

        Excel::import(new PlayerImport((int) $request->integer('ranking_id')), $request->file('file'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jugadores importados.']);

        return to_route('players.index', ['current_team' => $current_team->slug]);
    }

    public function exportForm(Team $current_team): Response
    {
        Gate::authorize('viewAny', Player::class);

        return Inertia::render('players/Export', [
            'rankings' => $current_team->ownedRankings()->orderByDesc('is_default')->get(['id', 'name', 'acronym']),
        ]);
    }

    public function export(Request $request, Team $current_team): BinaryFileResponse
    {
        Gate::authorize('viewAny', Player::class);

        $request->validate([
            'ranking_id' => ['required', 'integer', 'exists:rankings,id'],
            'speed' => ['required', 'in:standard,rapid,blitz'],
        ]);

        return Excel::download(
            new PlayersExport((int) $request->integer('ranking_id'), $request->string('speed')->value()),
            'jugadores.xlsx',
        );
    }
}
