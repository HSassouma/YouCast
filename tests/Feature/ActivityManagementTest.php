<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use App\Services\RegistrationService;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_publish_an_activity(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.activities.store'), [
                'type' => ActivityType::Event->value,
                'title' => 'Campus Meetup',
                'description' => 'A social evening for YouCode learners.',
                'category' => 'Social',
                'location' => 'Main Hall',
                'capacity' => 20,
                'cancellation_allowed' => '1',
            ])
            ->assertRedirect();

        $activity = Activity::query()->where('title', 'Campus Meetup')->first();
        $this->assertNotNull($activity);
        $this->assertSame(ActivityStatus::Draft, $activity->status);
        $this->assertSame($admin->id, $activity->created_by);

        $this->actingAs($admin)
            ->post(route('admin.activities.publish', $activity))
            ->assertRedirect();

        $this->assertSame(ActivityStatus::Published, $activity->fresh()->status);
        $this->get(route('activities.show', $activity->fresh()))->assertOk()->assertSee('Campus Meetup');
    }

    public function test_admin_cannot_publish_invalid_team_sizes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.activities.store'), [
                'type' => ActivityType::TeamChallenge->value,
                'title' => 'Bad Team Challenge',
                'description' => 'Missing team sizes.',
            ])
            ->assertSessionHasErrors('min_team_size');
    }

    public function test_team_activity_capacity_must_fit_at_least_one_complete_team(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.activities.store'), [
                'type' => ActivityType::TeamChallenge->value,
                'title' => 'Too Small Capacity',
                'description' => 'Capacity cannot fit its minimum team.',
                'capacity' => 1,
                'min_team_size' => 2,
                'max_team_size' => 4,
            ])
            ->assertSessionHasErrors('capacity');
    }

    public function test_registration_deadline_later_on_the_activity_start_day_is_accepted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.activities.store'), [
                'type' => ActivityType::Event->value,
                'title' => 'Same Day Deadline',
                'description' => 'The deadline can be later on the start day.',
                'starts_at' => '2030-01-10T08:00',
                'ends_at' => '2030-01-10T12:00',
                'registration_opens_at' => '2030-01-01T09:00',
                'registration_deadline' => '2030-01-10T23:00',
            ])
            ->assertRedirect();

        $activity = Activity::query()->where('title', 'Same Day Deadline')->firstOrFail();
        $this->assertSame('2030-01-10', $activity->registration_deadline->toDateString());
    }

    public function test_activity_type_cannot_change_after_team_creation(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create();
        $activity = Activity::factory()->teamChallenge()->published()->create();

        app(TeamService::class)->createTeam($learner, $activity, 'Existing Crew');

        $this->actingAs($admin)
            ->put(route('admin.activities.update', $activity), [
                'type' => ActivityType::IndividualChallenge->value,
                'title' => $activity->title,
                'description' => $activity->description,
                'category' => $activity->category,
                'location' => $activity->location,
                'starts_at' => $activity->starts_at->format('Y-m-d\TH:i'),
                'ends_at' => $activity->ends_at->format('Y-m-d\TH:i'),
                'registration_opens_at' => $activity->registration_opens_at->format('Y-m-d\TH:i'),
                'registration_deadline' => $activity->registration_deadline->format('Y-m-d\TH:i'),
                'capacity' => $activity->capacity,
                'cancellation_allowed' => '1',
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame(ActivityType::TeamChallenge, $activity->fresh()->type);
    }

    public function test_guests_are_redirected_to_login_for_the_catalogue_and_details(): void
    {
        $activity = Activity::factory()->published()->create(['title' => 'Members Only']);

        // The catalogue and the details page are reserved for authenticated
        // members: a direct URL must never leak the programme to a visitor.
        $this->get(route('activities.index'))
            ->assertRedirect(route('login'));

        $this->get(route('activities.show', $activity))
            ->assertRedirect(route('login'))
            ->assertDontSee('Members Only');
    }

    public function test_catalogue_hides_drafts_and_archives(): void
    {
        Activity::factory()->published()->create(['title' => 'Visible Event']);
        Activity::factory()->create(['title' => 'Hidden Draft']);
        Activity::factory()->archived()->create(['title' => 'Old Archive']);

        $this->actingAs(User::factory()->learner()->create())
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Visible Event')
            ->assertDontSee('Hidden Draft')
            ->assertDontSee('Old Archive');
    }

    public function test_finished_activities_disappear_from_learner_pages_but_remain_available_to_admins(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $learner = User::factory()->learner()->create();
        $admin = User::factory()->admin()->create();
        $finished = Activity::factory()->published()->create([
            'title' => 'Finished Workshop',
            'ends_at' => now()->subSecond(),
        ]);
        $upcoming = Activity::factory()->published()->create([
            'title' => 'Upcoming Workshop',
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($learner)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertDontSee('Finished Workshop')
            ->assertSee('Upcoming Workshop');

        $this->actingAs($learner)
            ->get(route('activities.show', $finished))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.activities.show', $finished))
            ->assertOk()
            ->assertSee('Finished Workshop');

        $this->assertDatabaseHas('activities', ['id' => $finished->id, 'title' => 'Finished Workshop']);
        $this->travelBack();
    }

    public function test_activity_ending_at_current_time_is_considered_finished(): void
    {
        $activity = Activity::factory()->published()->create(['ends_at' => now()]);

        $this->assertTrue($activity->isFinished());
        $this->assertFalse($activity->registrationWindowOpen());
    }

    public function test_admin_activity_page_lists_registered_learner_names(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->learner()->create(['name' => 'Participant Name']);
        $activity = Activity::factory()->published()->create();
        app(RegistrationService::class)->registerIndividual($learner, $activity);

        $this->actingAs($admin)
            ->get(route('admin.activities.show', $activity))
            ->assertOk()
            ->assertSee('Participants (1)')
            ->assertSee('Participant Name');
    }

    public function test_search_filters_activities(): void
    {
        Activity::factory()->published()->create(['title' => 'Algo Sprint', 'category' => 'Competition']);
        Activity::factory()->published()->create(['title' => 'Design Night', 'category' => 'Workshop']);

        $this->actingAs(User::factory()->learner()->create())
            ->get(route('activities.index', ['q' => 'Algo']))
            ->assertSee('Algo Sprint')
            ->assertDontSee('Design Night');
    }
}
