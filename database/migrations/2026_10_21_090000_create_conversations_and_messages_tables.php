<?php

use App\Enums\ConversationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Private conversations between a learner and the BDE administration.
 *
 * The conversation body is private: it is never rendered on a public page and
 * never sent outside YouCast.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            // The learner who opened the conversation. Restricted on delete so
            // the private history can never be silently destroyed.
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->string('subject', 120);
            $table->string('status', 32)->default(ConversationStatus::Open->value)->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Thread listing and unread counting.
            $table->index(['conversation_id', 'created_at']);
            $table->index(['conversation_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
