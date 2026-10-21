<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\IdeaStatus;
use App\Models\Activity;
use App\Models\ChallengeIdea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChallengeIdeaTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_can_submit_an_idea(): void
    {
        Http::fake();
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)
            ->post(route('ideas.store'), [
                'title' => 'Campus energy challenge',
                'description' => 'Build a tool that helps the campus reduce energy waste over a semester.',
                'category' => 'Web',
            ])
            ->assertRedirect(route('ideas.index'));

        $this->assertDatabaseHas('challenge_ideas', [
            'author_id' => $learner->id,
            'title' => 'Campus energy challenge',
            'status' => IdeaStatus::Pending->value,
        ]);

        Http::assertNothingSent();
    }

    public function test_admin_can_accept_and_convert_an_idea(): void
    {
        $admin = User::factory()->admin()->create();
        $idea = ChallengeIdea::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.ideas.review', $idea), [
                'decision' => IdeaStatus::Accepted->value,
                'review_notes' => 'Let us run this next month.',
            ])
            ->assertRedirect();

        $this->assertSame(IdeaStatus::Accepted, $idea->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.ideas.convert', $idea), [
                'type' => ActivityType::IndividualChallenge->value,
            ])
            ->assertRedirect();

        $activity = Activity::query()->where('source_idea_id', $idea->id)->first();
        $this->assertNotNull($activity);
        $this->assertSame(ActivityStatus::Draft, $activity->status);
        $this->assertSame($idea->title, $activity->title);
        $this->assertSame($idea->id, $activity->fresh()->source_idea_id);
    }

    public function test_learner_cannot_review_ideas(): void
    {
        $learner = User::factory()->learner()->create();
        $idea = ChallengeIdea::factory()->create();

        $this->actingAs($learner)
            ->post(route('admin.ideas.review', $idea), [
                'decision' => IdeaStatus::Accepted->value,
            ])
            ->assertForbidden();
    }
}
