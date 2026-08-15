<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Ranking;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicRankingController extends Controller
{
    public function index(Team $team): Response
    {
        $rankings = $team->ownedRankings()
            ->where('state', true)
            ->orderByDesc('is_default')
            ->get(['id', 'name', 'acronym', 'is_default']);

        return Inertia::render('public/rankings/Index', [
            'team' => $team,
            'rankings' => $rankings,
        ]);
    }

    public function show(Request $request, Team $team, Ranking $ranking): Response
    {
        abort_unless($ranking->owner_team_id === $team->id && $ranking->state, 404);

        $speed = $request->string('speed')->value() ?: 'standard';
        abort_unless(in_array($speed, ['standard', 'rapid', 'blitz'], true), 404);

        $search = $request->string('search')->trim()->value() ?: null;

        $players = $ranking->playerRankings()
            ->with('player:id,name,last_name,title,country')
            ->when($search, fn ($query) => $query->whereHas('player', fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")))
            ->orderByDesc("{$speed}_elo")
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('public/rankings/Show', [
            'team' => $team,
            'ranking' => $ranking,
            'players' => $players,
            'speed' => $speed,
            'search' => $search,
        ]);
    }
}
