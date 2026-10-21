<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\IdeaStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\ChallengeIdea;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Registration;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Enums\TeamMemberRole;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for the issues found during the backend security audit.
 *
 * Every test in this file corresponds to a confirmed finding documented in
 * BACKEND_SECURITY_AUDIT.md.
 */
class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    // Password recovery security properties are covered in PasswordResetTest.

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        User::factory()->learner()->create(['email' => 'target@example.test']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'target@example.test',
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login'), [
            'email' => 'target@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_session(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)->get(route('dashboard'))->assertOk();

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization / IDOR
    |--------------------------------------------------------------------------
    */

    public function test_learner_cannot_cancel_another_learners_registration(): void
    {
        $owner = User::factory()->learner()->create();
        $attacker = User::factory()->learner()->create();
        $activity = Activity::factory()->published()->create();

        $this->actingAs($owner)
            ->post(route('activities.registrations.store', $activity))
            ->assertRedirect();

        $this->actingAs($attacker)
            ->delete(route('activities.registrations.destroy', $activity))
            ->assertSessionHasErrors('activity');

        $this->assertDatabaseHas('registrations', [
            'user_id' => $owner->id,
            'activity_id' => $activity->id,
            'status' => RegistrationStatus::Registered->value,
        ]);
    }

    public function test_learner_cannot_disband_a_team_they_do_not_belong_to(): void
    {
        $activity = Activity::factory()->teamChallenge()->published()->create();
        $leader = User::factory()->learner()->create();
        $outsider = User::factory()->learner()->create();

        $this->actingAs($leader)
            ->post(route('activities.teams.store', $activity), ['name' => 'Alpha'])
            ->assertRedirect();

        $team = Team::query()->where('name', 'Alpha')->firstOrFail();

        $this->actingAs($outsider)
            ->delete(route('activities.teams.destroy', [$activity, $team]))
            ->assertForbidden();

        $this->assertDatabaseHas('teams', ['id' => $team->id]);
    }

    public function test_a_team_from_another_activity_is_not_accepted(): void
    {
        $activityA = Activity::factory()->teamChallenge()->published()->create();
        $activityB = Activity::factory()->teamChallenge()->published()->create();
        $leader = User::factory()->learner()->create();

        $this->actingAs($leader)
            ->post(route('activities.teams.store', $activityA), ['name' => 'Alpha'])
            ->assertRedirect();

        $team = Team::query()->where('name', 'Alpha')->firstOrFail();

        $this->actingAs($leader)
            ->delete(route('activities.teams.destroy', [$activityB, $team]))
            ->assertNotFound();
    }

    public function test_learner_cannot_reach_the_admin_moderation_pages(): void
    {
        $learner = User::factory()->learner()->create();
        $idea = ChallengeIdea::factory()->create();

        $this->actingAs($learner)->get(route('admin.ideas.index'))->assertForbidden();
        $this->actingAs($learner)->get(route('admin.ideas.show', $idea))->assertForbidden();
    }

    public function test_an_idea_cannot_be_reviewed_or_converted_twice(): void
    {
        $admin = User::factory()->admin()->create();
        $idea = ChallengeIdea::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.ideas.review', $idea), ['decision' => IdeaStatus::Accepted->value])
            ->assertRedirect();

        // A second moderation round is rejected: the transition is invalid.
        $this->actingAs($admin)
            ->post(route('admin.ideas.review', $idea), ['decision' => IdeaStatus::Rejected->value])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.ideas.convert', $idea), ['type' => 'event'])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.ideas.convert', $idea), ['type' => 'event'])
            ->assertForbidden();

        $this->assertSame(1, Activity::query()->where('source_idea_id', $idea->id)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Mass assignment
    |--------------------------------------------------------------------------
    */

    public function test_activity_ownership_and_status_cannot_be_forced_by_the_request(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();

        $this->actingAs($admin)
            ->post(route('admin.activities.store'), [
                'type' => 'event',
                'title' => 'Forced state',
                'description' => 'Trying to publish directly from the browser.',
                'status' => ActivityStatus::Published->value,
                'created_by' => $learner->id,
                'published_at' => now()->toDateTimeString(),
            ])
            ->assertRedirect();

        $activity = Activity::query()->where('title', 'Forced state')->firstOrFail();

        $this->assertSame(ActivityStatus::Draft, $activity->status);
        $this->assertSame($admin->id, $activity->created_by);
        $this->assertNull($activity->published_at);
    }

    public function test_idea_moderation_state_cannot_be_forced_by_the_learner(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)
            ->post(route('ideas.store'), [
                'title' => 'Forced acceptance',
                'description' => 'A description long enough to satisfy the validation rules.',
                'status' => IdeaStatus::Accepted->value,
                'reviewer_id' => $learner->id,
                'review_notes' => 'Approved by me.',
            ])
            ->assertRedirect(route('ideas.index'));

        $idea = ChallengeIdea::query()->where('title', 'Forced acceptance')->firstOrFail();

        $this->assertSame(IdeaStatus::Pending, $idea->status);
        $this->assertNull($idea->reviewer_id);
        $this->assertNull($idea->review_notes);
        $this->assertNull($idea->reviewed_at);
    }

    public function test_privileged_model_attributes_are_not_mass_assignable(): void
    {
        $activity = Activity::factory()->create(['status' => ActivityStatus::Draft]);

        $activity->fill([
            'status' => ActivityStatus::Published,
            'created_by' => 999,
            'published_at' => now(),
        ]);

        $this->assertSame(ActivityStatus::Draft, $activity->status);
        $this->assertNotSame(999, $activity->created_by);
        $this->assertNull($activity->published_at);

        $idea = ChallengeIdea::factory()->create();

        $idea->fill([
            'status' => IdeaStatus::Accepted,
            'reviewer_id' => 999,
        ]);

        $this->assertSame(IdeaStatus::Pending, $idea->status);
        $this->assertNull($idea->reviewer_id);

        // Tables that are entirely server owned reject mass assignment outright.
        $registration = Registration::factory()->create();

        try {
            $registration->fill(['status' => RegistrationStatus::Cancelled]);
            $this->fail('Mass assignment of a registration status should be rejected.');
        } catch (MassAssignmentException) {
            $this->assertSame(RegistrationStatus::Registered, $registration->status);
        }
    }

    public function test_public_registration_never_creates_an_administrator(): void
    {
        $this->post(route('register'), [
            'name' => 'Escalation',
            'email' => 'escalation@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Admin->value,
        ]);

        $this->assertDatabaseHas('users', ['email' => 'escalation@example.test']);

        $user = User::query()->where('email', 'escalation@example.test')->firstOrFail();

        $this->assertSame(UserRole::Learner, $user->role);
    }

    /*
    |--------------------------------------------------------------------------
    | Account deletion / data integrity
    |--------------------------------------------------------------------------
    */

    public function test_account_with_historical_records_can_be_deleted_without_losing_bde_history(): void
    {
        $owner = User::factory()->admin()->create(['email' => 'owner@example.test']);
        $idea = ChallengeIdea::factory()->create(['author_id' => $owner->id]);
        $activity = Activity::factory()->create(['created_by' => $owner->id]);
        $teamActivity = Activity::factory()->teamChallenge()->create(['created_by' => $owner->id]);
        $team = Team::factory()->create([
            'activity_id' => $teamActivity->id,
            'leader_id' => $owner->id,
        ]);
        $member = User::factory()->learner()->create();
        $membership = TeamMember::forceCreate([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'activity_id' => $teamActivity->id,
            'role' => TeamMemberRole::Member,
        ]);
        $conversation = Conversation::factory()->create(['student_id' => $owner->id]);
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $owner->id,
        ]);

        $moderator = User::factory()->admin()->create();

        $this->actingAs($owner)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('challenge_ideas', ['id' => $idea->id, 'author_id' => null]);
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'created_by' => null]);
        $this->assertDatabaseHas('activities', ['id' => $teamActivity->id, 'created_by' => null]);
        $this->assertDatabaseHas('conversations', ['id' => $conversation->id, 'student_id' => null]);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'user_id' => null]);
        $this->assertDatabaseHas('teams', ['id' => $team->id, 'leader_id' => $member->id]);
        $this->assertDatabaseHas('team_members', [
            'id' => $membership->id,
            'role' => TeamMemberRole::Leader->value,
        ]);
        $this->assertGuest();

        $this->actingAs($moderator)
            ->get(route('admin.ideas.show', $idea))
            ->assertOk()
            ->assertSee('Compte supprimé');

        $this->actingAs($moderator)
            ->get(route('admin.messages.show', $conversation))
            ->assertOk()
            ->assertSee('Compte supprimé');
    }

    public function test_account_without_owned_records_can_still_be_deleted(): void
    {
        $learner = User::factory()->learner()->create(['email' => 'clean@example.test']);

        $this->actingAs($learner)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertDatabaseMissing('users', ['id' => $learner->id]);
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | Registration integrity
    |--------------------------------------------------------------------------
    */

    public function test_failed_team_registration_leaves_no_partial_registrations(): void
    {
        // The team has three members but the activity only has two seats, so the
        // whole registration must fail and roll back.
        $activity = Activity::factory()->teamChallenge()->published()->create([
            'capacity' => 2,
            'min_team_size' => 2,
            'max_team_size' => 3,
        ]);
        $leader = User::factory()->learner()->create();
        $member = User::factory()->learner()->create();
        $third = User::factory()->learner()->create();

        $this->actingAs($leader)->post(route('activities.teams.store', $activity), ['name' => 'Partial']);
        $team = Team::query()->where('name', 'Partial')->firstOrFail();

        $this->actingAs($member)
            ->post(route('activities.teams.join', $activity), ['join_code' => $team->join_code])
            ->assertRedirect();

        $this->actingAs($third)
            ->post(route('activities.teams.join', $activity), ['join_code' => $team->join_code])
            ->assertRedirect();

        $this->actingAs($leader)
            ->post(route('activities.teams.register', [$activity, $team]))
            ->assertSessionHasErrors('activity');

        $this->assertSame(0, Registration::query()
            ->where('activity_id', $activity->id)
            ->where('status', RegistrationStatus::Registered->value)
            ->count());

        $this->assertFalse($team->fresh()->is_registered);
    }

    public function test_registration_is_rejected_for_an_unpublished_activity(): void
    {
        $learner = User::factory()->learner()->create();
        $activity = Activity::factory()->create(['status' => ActivityStatus::Draft]);

        $this->actingAs($learner)
            ->post(route('activities.registrations.store', $activity))
            ->assertForbidden();

        $this->assertDatabaseMissing('registrations', ['activity_id' => $activity->id]);
    }

    public function test_registration_endpoint_is_rate_limited(): void
    {
        $learner = User::factory()->learner()->create();
        $activity = Activity::factory()->published()->create();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($learner)->post(route('activities.registrations.store', $activity));
        }

        $this->actingAs($learner)
            ->post(route('activities.registrations.store', $activity))
            ->assertStatus(429);
    }

    /*
    |--------------------------------------------------------------------------
    | Idea moderation
    |--------------------------------------------------------------------------
    */

    public function test_idea_submission_is_rate_limited(): void
    {
        $learner = User::factory()->learner()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($learner)->post(route('ideas.store'), [
                'title' => 'Idea '.$i,
                'description' => 'A description long enough to satisfy the validation rules.',
            ]);
        }

        $this->actingAs($learner)
            ->post(route('ideas.store'), [
                'title' => 'Idea 6',
                'description' => 'A description long enough to satisfy the validation rules.',
            ])
            ->assertStatus(429);
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    public function test_registration_deadline_before_the_activity_start_day_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.activities.store'), [
                'type' => 'event',
                'title' => 'Deadline before event day',
                'description' => 'Registration deadline cannot precede the event day.',
                'starts_at' => '2030-01-10T09:00',
                'registration_opens_at' => '2030-01-01T09:00',
                'registration_deadline' => '2030-01-09T23:00',
            ])
            ->assertSessionHasErrors('registration_deadline');

        $this->assertDatabaseMissing('activities', ['title' => 'Deadline before event day']);
    }

    public function test_activity_fields_are_length_limited(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.activities.store'), [
                'type' => 'event',
                'title' => str_repeat('a', 181),
                'description' => 'Valid description.',
            ])
            ->assertSessionHasErrors('title');
    }

    /*
    |--------------------------------------------------------------------------
    | Output encoding
    |--------------------------------------------------------------------------
    */

    public function test_user_supplied_content_is_escaped_in_the_rendered_page(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = '<script>alert("xss")</script>';

        Activity::factory()->published()->create([
            'created_by' => $admin->id,
            'title' => $payload,
            'description' => $payload,
        ]);

        $activity = Activity::query()->where('title', $payload)->firstOrFail();

        $this->actingAs(User::factory()->learner()->create())
            ->get(route('activities.show', $activity))
            ->assertOk()
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
