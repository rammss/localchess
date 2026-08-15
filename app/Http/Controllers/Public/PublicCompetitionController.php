<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class PublicCompetitionController extends Controller
{
    public function index(Team $team): Response
    {
        $competitions = $team->competitions()
            ->orderByDesc('in_progress')
            ->orderByDesc('begins')
            ->paginate(15);

        return Inertia::render('public/competitions/Index', [
            'team' => $team,
            'competitions' => $competitions,
        ]);
    }

    public function show(Team $team, Competition $competition): Response
    {
        abort_unless($competition->team_id === $team->id, 404);

        $standings = $competition->competitionPlayers()
            ->with('player:id,name,last_name,title')
            ->orderBy('position')
            ->paginate(20)
            ->withQueryString();

        $tournaments = $competition->tournaments()
            ->where('processed', true)
            ->orderBy('ends')
            ->get(['id', 'name', 'ends']);

        return Inertia::render('public/competitions/Show', [
            'team' => $team,
            'competition' => $competition->load('ranking:id,name,acronym'),
            'standings' => $standings,
            'tournaments' => $tournaments,
        ]);
    }
}
