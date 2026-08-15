<?php

namespace App\Http\Controllers;

use App\Models\PerformanceVariation;
use App\Models\PlayerRanking;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Team $current_team): Response
    {
        $email = strtolower($request->user()->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('Dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'stats' => $current_team->is_personal ? null : $this->stats($current_team),
        ]);
    }

    private function stats(Team $team): array
    {
        $rankingIds = $team->ownedRankings()->pluck('id');

        $now = now();

        return [
            'rankingsCount' => $rankingIds->count(),
            'playersCount' => PlayerRanking::whereIn('ranking_id', $rankingIds)->distinct('player_id')->count('player_id'),
            'tournamentsThisMonth' => $team->tournaments()
                ->whereBetween('begins', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->count(),
            'upcomingTournaments' => $team->tournaments()
                ->where('begins', '>=', $now->toDateString())
                ->orderBy('begins')
                ->limit(5)
                ->get(['id', 'name', 'place', 'begins']),
            'topGainers' => PerformanceVariation::query()
                ->whereHas('playerRanking', fn ($query) => $query->whereIn('ranking_id', $rankingIds))
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->where('variation', '>', 0)
                ->with('player:id,name,last_name')
                ->orderByDesc('variation')
                ->limit(5)
                ->get(['id', 'player_id', 'variation', 'created_at']),
            'topLosers' => PerformanceVariation::query()
                ->whereHas('playerRanking', fn ($query) => $query->whereIn('ranking_id', $rankingIds))
                ->where('created_at', '>=', $now->copy()->subDays(30))
                ->where('variation', '<', 0)
                ->with('player:id,name,last_name')
                ->orderBy('variation')
                ->limit(5)
                ->get(['id', 'player_id', 'variation', 'created_at']),
        ];
    }
}
