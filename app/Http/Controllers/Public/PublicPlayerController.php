<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PerformanceVariation;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PublicPlayerController extends Controller
{
    public function index(Request $request, Team $team): Response
    {
        $rankingIds = $team->ownedRankings()->pluck('id');
        $search = $request->string('search')->trim()->value() ?: null;

        $players = Player::query()
            ->whereHas('rankings', fn ($query) => $query->whereIn('rankings.id', $rankingIds))
            ->when($search, fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"))
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('public/players/Index', [
            'team' => $team,
            'players' => $players,
            'search' => $search,
        ]);
    }

    public function show(Team $team, Player $player): Response
    {
        $rankingIds = $team->ownedRankings()->pluck('id');

        $rankings = $player->rankings()->whereIn('rankings.id', $rankingIds)->get();

        abort_if($rankings->isEmpty(), 404);

        $tournaments = $player->tournaments()
            ->where('tournaments.team_id', $team->id)
            ->where('tournaments.processed', true)
            ->orderByDesc('ends')
            ->get();

        return Inertia::render('public/players/Show', [
            'team' => $team,
            'player' => $player,
            'rankings' => $rankings,
            'tournaments' => $tournaments,
            'eloHistory' => Inertia::defer(fn () => $this->eloHistory($player, $rankingIds)),
        ]);
    }

    private function eloHistory(Player $player, Collection $rankingIds): array
    {
        return PerformanceVariation::where('player_id', $player->id)
            ->whereHas('playerRanking', fn ($query) => $query->whereIn('ranking_id', $rankingIds))
            ->with('game.round.tournament:id,name,ends')
            ->orderBy('id')
            ->get()
            ->map(fn (PerformanceVariation $variation) => [
                'date' => $variation->game?->round?->tournament?->ends?->format('Y-m-d') ?? $variation->created_at->format('Y-m-d'),
                'elo' => (float) $variation->elo_after,
                'type' => $variation->type,
                'tournament' => $variation->game?->round?->tournament?->name,
            ])
            ->values()
            ->all();
    }
}
