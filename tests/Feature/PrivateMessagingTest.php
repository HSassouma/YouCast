<?php

namespace Tests\Feature;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests for the private conversations between learners and the BDE.
 *
 * A conversation must stay readable and writable only by its participants.
 */
class PrivateMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_learner_can_open_a_conversation_with_the_bde(): void
    {
        $student = User::factory()->learner()->create();

        $this->actingAs($student)
            ->post(route('messages.store'), [
                'subject' => 'Question about the hackathon',
                'body' => 'Can my team register after the deadline?',
            ])
            ->assertRedirect();

        $conversation = Conversation::query()->firstOrFail();

        $this->assertSame($student->id, $conversation->student_id);
        $this->assertSame(ConversationStatus::Open, $conversation->status);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $student->id,
            'body' => 'Can my team register after the deadline?',
        ]);
    }

    public function test_an_administrator_cannot_open_a_conversation_as_a_learner(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('messages.store'), [
                'subject' => 'Administrator',
                'body' => 'Administrators answer, they do not open conversations.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('conversations', 0);
    }

    public function test_a_learner_cannot_read_another_learners_conversation(): void
    {
        $owner = User::factory()->learner()->create();
        $other = User::factory()->learner()->create();
        $conversation = Conversation::factory()->create(['student_id' => $owner->id]);

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $owner->id,
            'body' => 'A private question about my registration.',
        ]);

        $this->actingAs($other)
            ->get(route('messages.show', $conversation))
            ->assertForbidden();

        $this->actingAs($other)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertDontSee('A private question about my registration.')
            ->assertDontSee($owner->email);
    }

    public function test_a_learner_cannot_reply_to_another_learners_conversation(): void
    {
        $owner = User::factory()->learner()->create();
        $other = User::factory()->learner()->create();
        $conversation = Conversation::factory()->create(['student_id' => $owner->id]);

        $this->actingAs($other)
            ->post(route('messages.replies.store', $conversation), ['body' => 'Intruding message'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_a_guest_cannot_read_a_conversation(): void
    {
        $conversation = Conversation::factory()->create();

        $this->get(route('messages.show', $conversation))->assertRedirect(route('login'));
        $this->get(route('admin.messages.show', $conversation))->assertRedirect(route('login'));
    }

    public function test_an_administrator_can_read_and_answer_a_conversation(): void
    {
        Http::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->learner()->create();
        $conversation = Conversation::factory()->create(['student_id' => $student->id]);

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $student->id,
            'body' => 'Could you explain the team rules?',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.messages.show', $conversation))
            ->assertOk()
            ->assertSee('Could you explain the team rules?');

        $this->actingAs($admin)
            ->post(route('messages.replies.store', $conversation), ['body' => 'Of course, here is how it works.'])
            ->assertRedirect(route('admin.messages.show', $conversation));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $admin->id,
            'body' => 'Of course, here is how it works.',
        ]);

        Http::assertNothingSent();
    }

    public function test_a_learner_can_answer_the_administrator(): void
    {
        Http::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->learner()->create();
        $conversation = Conversation::factory()->create(['student_id' => $student->id]);

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $admin->id,
            'body' => 'Did you receive your answer?',
        ]);

        $this->actingAs($student)
            ->post(route('messages.replies.store', $conversation), ['body' => 'Yes, thank you!'])
            ->assertRedirect(route('messages.show', $conversation));

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $student->id,
            'body' => 'Yes, thank you!',
        ]);

        Http::assertNothingSent();
    }

    public function test_a_closed_conversation_rejects_new_answers(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->learner()->create();
        $conversation = Conversation::factory()->closed()->create(['student_id' => $student->id]);

        $this->actingAs($student)
            ->post(route('messages.replies.store', $conversation), ['body' => 'One more question'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('messages.replies.store', $conversation), ['body' => 'Still closed'])
            ->assertForbidden();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_an_administrator_can_close_and_reopen_a_conversation(): void
    {
        $admin = User::factory()->admin()->create();
        $conversation = Conversation::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.messages.close', $conversation))
            ->assertRedirect();

        $this->assertSame(ConversationStatus::Closed, $conversation->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.messages.reopen', $conversation))
            ->assertRedirect();

        $this->assertSame(ConversationStatus::Open, $conversation->fresh()->status);
    }

    public function test_a_learner_cannot_close_a_conversation(): void
    {
        $student = User::factory()->learner()->create();
        $conversation = Conversation::factory()->create(['student_id' => $student->id]);

        $this->actingAs($student)
            ->post(route('admin.messages.close', $conversation))
            ->assertForbidden();

        $this->assertSame(ConversationStatus::Open, $conversation->fresh()->status);
    }

    public function test_a_learner_cannot_reach_the_administration_inbox(): void
    {
        $student = User::factory()->learner()->create();

        $this->actingAs($student)->get(route('admin.messages.index'))->assertForbidden();
    }

    public function test_opening_a_conversation_sends_no_external_request(): void
    {
        Http::fake();

        $student = User::factory()->learner()->create();

        $this->actingAs($student)
            ->post(route('messages.store'), [
                'subject' => 'Private',
                'body' => 'This content must never leave the server.',
            ])
            ->assertRedirect();

        Http::assertNothingSent();
    }

    public function test_message_contents_never_appear_on_public_pages(): void
    {
        $student = User::factory()->learner()->create();
        $conversation = Conversation::factory()->create(['student_id' => $student->id]);

        Message::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $student->id,
            'body' => 'Strictly confidential hint text.',
        ]);

        $this->get(route('home'))->assertOk()->assertDontSee('Strictly confidential hint text.');
        // The catalogue itself is now members-only: a guest is redirected to
        // the login screen and never receives any page that could leak it.
        $this->get(route('activities.index'))->assertRedirect(route('login'));
    }

    public function test_opening_a_conversation_is_rate_limited(): void
    {
        $student = User::factory()->learner()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($student)->post(route('messages.store'), [
                'subject' => 'Subject '.$i,
                'body' => 'Body of message '.$i,
            ]);
        }

        $this->actingAs($student)
            ->post(route('messages.store'), [
                'subject' => 'One too many',
                'body' => 'Body of message',
            ])
            ->assertStatus(429);
    }

    public function test_a_conversation_body_is_validated(): void
    {
        $student = User::factory()->learner()->create();

        $this->actingAs($student)
            ->post(route('messages.store'), ['subject' => '', 'body' => ''])
            ->assertSessionHasErrors(['subject', 'body']);

        $this->assertDatabaseCount('conversations', 0);
    }
}
