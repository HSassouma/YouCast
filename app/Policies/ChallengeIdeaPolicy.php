<?php

namespace App\Policies;

use App\Models\ChallengeIdea;
use App\Models\User;

class ChallengeIdeaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Access to the moderation console (reviewing and converting ideas).
     */
    public function moderate(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ChallengeIdea $idea): bool
    {
        return $user->isAdmin() || $idea->author_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isLearner() || $user->isAdmin();
    }

    public function review(User $user, ChallengeIdea $idea): bool
    {
        return $user->isAdmin() && $idea->isPending();
    }

    public function convert(User $user, ChallengeIdea $idea): bool
    {
        return $user->isAdmin() && $idea->isAccepted() && $idea->activity === null;
    }
}
