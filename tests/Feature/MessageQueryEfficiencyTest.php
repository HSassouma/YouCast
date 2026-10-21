<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Guards against an N+1 query in the message listings.
 *
 * The number of queries must not grow with the number of conversations.
 */
class MessageQueryEfficiencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_learner_message_list_does_not_issue_one_query_per_conversation(): void
    {
        $student = User::factory()->learner()->create();

        $this->createConversations($student, 1);
        $one = $this->countQueriesFor(route('messages.index'), $student);

        $this->createConversations($student, 4);
        $five = $this->countQueriesFor(route('messages.index'), $student);

        $this->assertLessThanOrEqual(
            $one + 1,
            $five,
            'The learner message list issues one query per conversation.',
        );
    }

    public function test_the_administration_inbox_does_not_issue_one_query_per_conversation(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->learner()->create();

        $this->createConversations($student, 1);
        $one = $this->countQueriesFor(route('admin.messages.index'), $admin);

        $this->createConversations($student, 4);
        $five = $this->countQueriesFor(route('admin.messages.index'), $admin);

        $this->assertLessThanOrEqual(
            $one + 1,
            $five,
            'The administration inbox issues one query per conversation.',
        );
    }

    private function createConversations(User $student, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $conversation = Conversation::factory()->create(['student_id' => $student->id]);

            Message::factory()->count(2)->create([
                'conversation_id' => $conversation->id,
                'user_id' => $student->id,
            ]);
        }
    }

    private function countQueriesFor(string $route, User $user): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)->get($route)->assertOk();

        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $queries;
    }
}
