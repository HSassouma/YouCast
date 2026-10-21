<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConversationRequest;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The learner facing side of the private conversations with the BDE.
 */
class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Conversation::class);

        $user = $request->user();

        $conversations = Conversation::query()
            ->forStudent($user->id)
            ->with('lastMessage')
            ->withCount([
                'messages as unread_count' => fn (Builder $query) => $query
                    ->whereNull('read_at')
                    ->where('user_id', '!=', $user->id),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('messages.index', [
            'conversations' => $conversations,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Conversation::class);

        return view('messages.create');
    }

    public function store(StoreConversationRequest $request, ConversationService $service): RedirectResponse
    {
        $conversation = $service->open(
            $request->user(),
            $request->validated('subject'),
            $request->validated('body'),
        );

        return redirect()
            ->route('messages.show', $conversation)
            ->with('status', 'Votre message a été envoyé au BDE.');
    }

    public function show(Request $request, Conversation $conversation, ConversationService $service): View
    {
        $this->authorize('view', $conversation);

        $conversation->load(['messages.author', 'student']);

        $service->markAsRead($conversation, $request->user());

        return view('messages.show', [
            'conversation' => $conversation,
        ]);
    }
}
