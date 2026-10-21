<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the confirmed product workflow of the student activities page and
 * of the activity registration process.
 */
class ProductWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_log_in_and_reach_the_activities_page(): void
    {
        $student = User::factory()->learner()->create();

        $this->post(route('login'), [
            'email' => $student->email,
            'password' => 'password',
        ])->assertRedirect(route('activities.index', absolute: false));

        $this->actingAs($student)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Activités');
    }

    public function test_the_activities_page_shows_the_registration_status_of_the_student(): void
    {
        $student = User::factory()->learner()->create();
        $activity = Activity::factory()->published()->create(['title' => 'Open Sprint']);

        $this->actingAs($student)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertDontSee('Inscription validée');

        $this->actingAs($student)
            ->post(route('activities.registrations.store', $activity))
            ->assertRedirect();

        $this->actingAs($student)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Inscription validée');
    }

    public function test_a_registration_references_the_existing_authenticated_user(): void
    {
        $student = User::factory()->learner()->create();
        $activity = Activity::factory()->published()->create();

        $this->actingAs($student)
            ->post(route('activities.registrations.store', $activity))
            ->assertRedirect();

        $registration = Registration::query()
            ->where('activity_id', $activity->id)
            ->firstOrFail();

        $this->assertSame($student->id, $registration->user_id);
        $this->assertSame(RegistrationStatus::Registered, $registration->status);
    }

    public function test_registering_for_another_activity_does_not_create_another_user(): void
    {
        $student = User::factory()->learner()->create();
        $first = Activity::factory()->published()->create();
        $second = Activity::factory()->published()->create();

        $usersBefore = User::query()->count();

        $this->actingAs($student)->post(route('activities.registrations.store', $first))->assertRedirect();
        $this->actingAs($student)->post(route('activities.registrations.store', $second))->assertRedirect();

        $this->assertSame($usersBefore, User::query()->count(), 'Registering for an activity created a new user record.');

        $this->assertSame(
            [$student->id, $student->id],
            Registration::query()->whereIn('activity_id', [$first->id, $second->id])->pluck('user_id')->all(),
        );
    }

    public function test_a_closed_activity_rejects_registration(): void
    {
        $student = User::factory()->learner()->create();
        $activity = Activity::factory()->closed()->create();

        $this->actingAs($student)
            ->post(route('activities.registrations.store', $activity))
            ->assertForbidden();

        $this->assertDatabaseMissing('registrations', ['activity_id' => $activity->id]);
    }

    public function test_an_unpublished_activity_rejects_registration(): void
    {
        $student = User::factory()->learner()->create();
        $activity = Activity::factory()->create(['status' => ActivityStatus::Draft]);

        $this->actingAs($student)
            ->post(route('activities.registrations.store', $activity))
            ->assertForbidden();

        $this->assertDatabaseMissing('registrations', ['activity_id' => $activity->id]);
    }

    public function test_a_full_activity_rejects_registration(): void
    {
        $activity = Activity::factory()->published()->create(['capacity' => 1]);
        $first = User::factory()->learner()->create();
        $second = User::factory()->learner()->create();

        $this->actingAs($first)->post(route('activities.registrations.store', $activity))->assertRedirect();

        $this->actingAs($second)
            ->post(route('activities.registrations.store', $activity))
            ->assertSessionHasErrors('activity');

        $this->assertSame(1, Registration::query()->where('activity_id', $activity->id)->count());
    }

    public function test_a_student_cannot_view_another_students_registration_information(): void
    {
        $owner = User::factory()->learner()->create(['email' => 'owner@example.test']);
        $other = User::factory()->learner()->create(['email' => 'other@example.test']);
        $activity = Activity::factory()->published()->create();

        $this->actingAs($owner)->post(route('activities.registrations.store', $activity))->assertRedirect();

        // The participant list only exists in the protected administration area.
        $this->actingAs($other)
            ->get(route('admin.activities.show', $activity))
            ->assertForbidden();

        // No learner facing page exposes another learner's private data.
        $this->actingAs($other)
            ->get(route('activities.show', $activity))
            ->assertOk()
            ->assertDontSee($owner->email);

        $this->actingAs($other)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertDontSee($owner->email);
    }

    public function test_a_student_cannot_create_an_activity(): void
    {
        $student = User::factory()->learner()->create();

        $this->actingAs($student)
            ->post(route('admin.activities.store'), [
                'type' => 'event',
                'title' => 'Not allowed',
                'description' => 'A student must not create activities.',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('activities', ['title' => 'Not allowed']);
    }

    public function test_a_student_cannot_edit_an_activity(): void
    {
        $student = User::factory()->learner()->create();
        $activity = Activity::factory()->published()->create(['title' => 'Untouched']);

        $this->actingAs($student)
            ->put(route('admin.activities.update', $activity), [
                'type' => 'event',
                'title' => 'Hijacked',
                'description' => 'A student must not edit activities.',
            ])
            ->assertForbidden();

        $this->assertSame('Untouched', $activity->fresh()->title);
    }

    public function test_an_administrator_can_create_and_update_an_activity(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.activities.store'), [
                'type' => 'event',
                'title' => 'BDE Evening',
                'description' => 'A social evening organised by the BDE.',
                'capacity' => 30,
            ])
            ->assertRedirect();

        $activity = Activity::query()->where('title', 'BDE Evening')->firstOrFail();
        $this->assertSame(ActivityStatus::Draft, $activity->status);
        $this->assertSame($admin->id, $activity->created_by);

        $this->actingAs($admin)
            ->put(route('admin.activities.update', $activity), [
                'type' => 'event',
                'title' => 'BDE Evening (updated)',
                'description' => 'The same evening with a new name.',
                'capacity' => 40,
            ])
            ->assertRedirect();

        $activity->refresh();

        $this->assertSame('BDE Evening (updated)', $activity->title);
        $this->assertSame(40, $activity->capacity);
        $this->assertSame(ActivityStatus::Draft, $activity->status);
    }

    public function test_an_administrator_can_unpublish_a_published_activity_without_deleting_it(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->published()->create(['title' => 'Seasonal event']);

        $this->actingAs($admin)
            ->post(route('admin.activities.unpublish', $activity))
            ->assertRedirect();

        $activity->refresh();

        $this->assertSame(ActivityStatus::Draft, $activity->status);
        $this->assertNull($activity->published_at);
        $this->assertDatabaseHas('activities', ['id' => $activity->id]);
    }

    public function test_a_student_cannot_unpublish_an_activity(): void
    {
        $student = User::factory()->learner()->create();
        $activity = Activity::factory()->published()->create();

        $this->actingAs($student)
            ->post(route('admin.activities.unpublish', $activity))
            ->assertForbidden();

        $this->assertSame(ActivityStatus::Published, $activity->fresh()->status);
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

        $this->assertSame(
            UserRole::Learner,
            User::query()->where('email', 'escalation@example.test')->firstOrFail()->role,
        );
    }

    public function test_the_about_page_is_public_and_never_exposes_activities(): void
    {
        Activity::factory()->published()->create(['title' => 'Confidential Session']);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('À propos')
            ->assertDontSee('Confidential Session');
    }

    public function test_guests_stay_on_the_public_site_without_internal_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('À propos');
    }
}
