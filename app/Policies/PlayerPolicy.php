<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\User;

/**
 * Players are a global entity — not owned by a single federation, since a
 * player competes across many federations over their career. Any staff
 * member of a real (non-personal) federation may manage the shared player
 * directory, same as tournament directors already could in the legacy app.
 */
class PlayerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Player $player): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Player $player): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Player $player): bool
    {
        return $user->isStaff();
    }
}
