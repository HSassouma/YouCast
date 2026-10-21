<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Activity $activity): bool
    {
        if ($activity->status->isPubliclyVisible() && ! $activity->isFinished()) {
            return true;
        }

        return $user?->isAdmin() ?? false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->isAdmin() && ! $activity->isArchived();
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->isAdmin() && $activity->isDraft();
    }

    public function publish(User $user, Activity $activity): bool
    {
        return $user->isAdmin() && ($activity->isDraft() || $activity->isClosed());
    }

    /**
     * Take a published activity back to draft.
     *
     * Unpublishing hides the activity from the catalogue without deleting it:
     * its registrations and history are preserved.
     */
    public function unpublish(User $user, Activity $activity): bool
    {
        return $user->isAdmin() && ($activity->isPublished() || $activity->isClosed());
    }

    public function close(User $user, Activity $activity): bool
    {
        return $user->isAdmin() && $activity->isPublished();
    }

    public function cancel(User $user, Activity $activity): bool
    {
        return $user->isAdmin() && ($activity->isDraft() || $activity->isPublished());
    }

    public function archive(User $user, Activity $activity): bool
    {
        return $user->isAdmin() && ($activity->isClosed() || $activity->isCancelled());
    }

    public function register(User $user, Activity $activity): bool
    {
        return $user->isLearner() && $activity->isPublished();
    }

    /**
     * A learner may cancel their own registration while the activity is
     * published. The registration service additionally verifies that the
     * registration belongs to the current user.
     */
    public function cancelRegistration(User $user, Activity $activity): bool
    {
        return $user->isLearner() && $activity->isPublished();
    }

    public function manageRegistrations(User $user, Activity $activity): bool
    {
        return $user->isAdmin();
    }
}
