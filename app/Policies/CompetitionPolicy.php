<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Competition;
use App\Models\Team;
use App\Models\User;

class CompetitionPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, Competition $competition): bool
    {
        return $user->belongsToTeam($competition->team);
    }

    public function create(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    public function update(User $user, Competition $competition): bool
    {
        return $this->canManage($user, $competition->team);
    }

    public function delete(User $user, Competition $competition): bool
    {
        return $this->canManage($user, $competition->team);
    }

    private function canManage(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && ($user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false);
    }
}
