<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConversationStatus;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\AuditLogger;
use App\Services\ConversationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The administration side of the private conversations.
 */
class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Conversation::class);

        $user = $request->user();

        $query = Conversation::query()
            ->with(['student', 'lastMessage'])
            ->withCount([
                'messages as unread_count' => fn (Builder $messages) => $messages
                    ->whereNull('read_at')
                    ->where('user_id', '!=', $user->id),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($status = $request->input('status')) {
            if (ConversationStatus::tryFrom($status)) {
                $query->where('status', $status);
            }
        }

        return view('admin.messages.index', [
            'conversations' => $query->paginate(15)->withQueryString(),
            'statuses' => ConversationStatus::cases(),
            'filters' => ['status' => $status],
        ]);
    }

    public function show(Request $request, Conversation $conversation, ConversationService $service): View
    {
        $this->authorize('view', $conversation);

        $conversation->load(['messages.author', 'student']);

        $service->markAsRead($conversation, $request->user());

        return view('admin.messages.show', [
            'conversation' => $conversation,
        ]);
    }

    /**
     * Close a conversation. The messages are kept: nothing is deleted.
     */
    public function close(Request $request, Conversation $conversation, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('close', $conversation);

        $conversation->status = ConversationStatus::Closed;
        $conversation->save();

        $audit->record($request->user(), 'conversation.closed', $conversation);

        return back()->with('status', 'Conversation clôturée.');
    }

    /**
     * Reopen a closed conversation so the learner can write again.
     */
    public function reopen(Request $request, Conversation $conversation, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('reopen', $conversation);

        $conversation->status = ConversationStatus::Open;
        $conversation->save();

        $audit->record($request->user(), 'conversation.reopened', $conversation);

        return back()->with('status', 'Conversation rouverte.');
    }
}
