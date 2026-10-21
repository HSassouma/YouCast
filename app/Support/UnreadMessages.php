<?php

namespace App\Support;

use App\Models\Message;
use App\Models\User;

/**
 * Counts the messages the given account has not read yet.
 *
 * A single aggregated query, shared by the navigation badge and the
 * administrator dashboard so the number is never computed twice on a page.
 */
final class UnreadMessages
{
    public static function countFor(User $user): int
    {
        return Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->whereNull('messages.read_at')
            ->where('messages.user_id', '!=', $user->id)
            ->when(! $user->isAdmin(), fn ($query) => $query->where('conversations.student_id', $user->id))
            ->count();
    }
}
