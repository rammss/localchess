<?php

namespace App\Policies;

use App\Enums\TeamRole;
use App\Models\Ranking;
use App\Models\Team;
use App\Models\User;

class RankingPolicy
{
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    public function view(User $user, Ranking $ranking): bool
    {
        return $user->belongsToTeam($ranking->owner);
    }

    public function create(User $user, Team $team): bool
    {
        return $this->canManage($user, $team);
    }

    public function update(User $user, Ranking $ranking): bool
    {
        return $this->canManage($user, $ranking->owner);
    }

    public function delete(User $user, Ranking $ranking): bool
    {
        return $this->canManage($user, $ranking->owner);
    }

    private function canManage(User $user, Team $team): bool
    {
        return ! $team->is_personal
            && ($user->teamRole($team)?->isAtLeast(TeamRole::Admin) ?? false);
    }
}
