<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Only the participants of a conversation may read it.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }

    /**
     * Learners open a conversation with the BDE.
     */
    public function create(User $user): bool
    {
        return $user->isLearner();
    }

    /**
     * A participant may answer while the conversation is open.
     */
    public function reply(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user) && $conversation->isOpen();
    }

    public function close(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin() && $conversation->isOpen();
    }

    public function reopen(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin() && ! $conversation->isOpen();
    }
}
