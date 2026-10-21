<?php

namespace App\Services;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Business rules for the private conversations between learners and the BDE.
 */
class ConversationService
{
    /**
     * Open a new conversation and write its first message.
     */
    public function open(User $student, string $subject, string $body): Conversation
    {
        return DB::transaction(function () use ($student, $subject, $body): Conversation {
            $conversation = new Conversation;
            $conversation->subject = $subject;
            $conversation->student_id = $student->id;
            $conversation->status = ConversationStatus::Open;
            $conversation->last_message_at = now();
            $conversation->save();

            $this->appendMessage($conversation, $student, $body);

            return $conversation;
        });
    }

    /**
     * Append an answer to an open conversation.
     */
    public function reply(User $author, Conversation $conversation, string $body): Message
    {
        return DB::transaction(function () use ($author, $conversation, $body): Message {
            $message = $this->appendMessage($conversation, $author, $body);

            $conversation->last_message_at = $message->created_at ?? now();
            $conversation->save();

            return $message;
        });
    }

    /**
     * Mark every message the reader did not write as read.
     */
    public function markAsRead(Conversation $conversation, User $reader): void
    {
        $conversation->messages()
            ->whereNull('read_at')
            ->where('user_id', '!=', $reader->id)
            ->update(['read_at' => now()]);
    }

    private function appendMessage(Conversation $conversation, User $author, string $body): Message
    {
        $message = new Message;
        $message->conversation_id = $conversation->id;
        $message->user_id = $author->id;
        $message->body = $body;
        $message->save();

        return $message;
    }
}
