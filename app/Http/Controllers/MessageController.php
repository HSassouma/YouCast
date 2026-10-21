<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\RedirectResponse;

/**
 * Answers written inside an existing conversation.
 *
 * The same endpoint serves learners and administrators: the policy decides
 * whether the author may reply and the redirect returns the author to the
 * area they came from.
 */
class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, Conversation $conversation, ConversationService $service): RedirectResponse
    {
        $service->reply(
            $request->user(),
            $conversation,
            $request->validated('body'),
        );

        $target = $request->user()->isAdmin()
            ? route('admin.messages.show', $conversation)
            : route('messages.show', $conversation);

        return redirect($target)->with('status', 'Votre réponse a été envoyée.');
    }
}
