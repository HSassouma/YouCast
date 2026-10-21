<?php

namespace Tests\Feature;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
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
