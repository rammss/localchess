<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, Tournament $tournament): bool
    {
        return $user->belongsToTeam($tournament->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    public function update(User $user, Tournament $tournament): bool
    {
        return $this->canManage($user, $tournament->team);
    }

    /**
     * Parsing/processing a tournament (running the ELO engine over it) is
     * gated the same as any other write.
     */
    public function process(User $user, Tournament $tournament): bool
    {
        return $this->canManage($user, $tournament->team);
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        return $this->canManage($user, $tournament->team);
    }

    /**
     * A user may manage a federation's tournaments if they're an admin or
     * owner of a *real* team. A personal team (auto-created on registration
     * for every user) never grants this — see User::isStaff().
     */
    private function canManage(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && ($user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false);
    }
}
