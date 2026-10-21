<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Activity;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_can_register_and_cancel_individual_activity(): void
    {
        $learner = User::factory()->learner()->create();
        $activity = Activity::factory()->individual()->published()->create(['capacity' => 10]);

        $this->actingAs($learner)
            ->post(route('activities.registrations.store', $activity))
            ->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'user_id' => $learner->id,
            'activity_id' => $activity->id,
            'status' => RegistrationStatus::Registered->value,
        ]);

        $this->actingAs($learner)
            ->delete(route('activities.registrations.destroy', $activity))
            ->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'user_id' => $learner->id,
            'activity_id' => $activity->id,
            'status' => RegistrationStatus::Cancelled->value,
        ]);
    }

    public function test_duplicate_registration_is_rejected(): void
    {
        $learner = User::factory()->learner()->create();
        $activity = Activity::factory()->published()->create();

        $this->actingAs($learner)->post(route('activities.registrations.store', $activity));
        $this->actingAs($learner)
            ->post(route('activities.registrations.store', $activity))
            ->assertSessionHasErrors('activity');

        $this->assertSame(1, Registration::query()->where('user_id', $learner->id)->where('activity_id', $activity->id)->count());
    }

    public function test_capacity_limit_is_enforced(): void
    {
        $activity = Activity::factory()->published()->create(['capacity' => 1]);
        $first = User::factory()->learner()->create();
        $second = User::factory()->learner()->create();

        $this->actingAs($first)->post(route('activities.registrations.store', $activity))->assertRedirect();
        $this->actingAs($second)
            ->post(route('activities.registrations.store', $activity))
            ->assertSessionHasErrors('activity');
    }

    public function test_cannot_register_after_deadline(): void
    {
        $learner = User::factory()->learner()->create();
        $activity = Activity::factory()->published()->create([
            'registration_deadline' => now()->subHour(),
        ]);

        $this->actingAs($learner)
            ->post(route('activities.registrations.store', $activity))
            ->assertSessionHasErrors('activity');
    }

    public function test_cannot_register_for_team_activity_as_individual(): void
    {
        $learner = User::factory()->learner()->create();
        $activity = Activity::factory()->teamChallenge()->published()->create();

        $this->actingAs($learner)
            ->post(route('activities.registrations.store', $activity))
            ->assertSessionHasErrors('activity');
    }

    public function test_guest_cannot_register(): void
    {
        $activity = Activity::factory()->published()->create();

        $this->post(route('activities.registrations.store', $activity))
            ->assertRedirect(route('login'));
    }
}
