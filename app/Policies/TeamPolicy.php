<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function create(User $user, Activity $activity): bool
    {
        return $user->isLearner() && $activity->usesTeams() && $activity->registrationWindowOpen();
    }

    public function join(User $user, Activity $activity): bool
    {
        return $this->create($user, $activity);
    }

    public function view(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->hasMember($user);
    }

    public function leave(User $user, Team $team): bool
    {
        return $team->hasMember($user) && ! $team->isLeader($user) && ! $team->is_registered;
    }

    public function disband(User $user, Team $team): bool
    {
        return $team->isLeader($user) && ! $team->is_registered;
    }

    public function register(User $user, Team $team): bool
    {
        return $team->isLeader($user) && ! $team->is_registered;
    }

    public function unregister(User $user, Team $team): bool
    {
        return $team->isLeader($user) && $team->is_registered;
    }
}
