<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Models\Activity;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_learners_can_create_join_and_register_a_team(): void
    {
        $activity = Activity::factory()->teamChallenge()->published()->create([
            'capacity' => 10,
            'min_team_size' => 2,
            'max_team_size' => 3,
        ]);
        $leader = User::factory()->learner()->create();
        $member = User::factory()->learner()->create();

        $this->actingAs($leader)
            ->post(route('activities.teams.store', $activity), ['name' => 'Purple Fox'])
            ->assertRedirect();

        $team = Team::query()->where('name', 'Purple Fox')->first();
        $this->assertNotNull($team);

        $this->actingAs($member)
            ->post(route('activities.teams.join', $activity), ['join_code' => $team->join_code])
            ->assertRedirect();

        $this->actingAs($leader)
            ->post(route('activities.teams.register', [$activity, $team]))
            ->assertRedirect();

        $this->assertTrue($team->fresh()->is_registered);
        $this->assertDatabaseHas('registrations', [
            'user_id' => $leader->id,
            'activity_id' => $activity->id,
            'team_id' => $team->id,
            'status' => RegistrationStatus::Registered->value,
        ]);
        $this->assertDatabaseHas('registrations', [
            'user_id' => $member->id,
            'activity_id' => $activity->id,
            'status' => RegistrationStatus::Registered->value,
        ]);
    }

    public function test_cannot_join_full_team(): void
    {
        $activity = Activity::factory()->teamChallenge()->published()->create([
            'min_team_size' => 1,
            'max_team_size' => 1,
        ]);
        $leader = User::factory()->learner()->create();
        $outsider = User::factory()->learner()->create();

        $this->actingAs($leader)
            ->post(route('activities.teams.store', $activity), ['name' => 'Solo'])
            ->assertRedirect();

        $team = Team::query()->first();

        $this->actingAs($outsider)
            ->post(route('activities.teams.join', $activity), ['join_code' => $team->join_code])
            ->assertSessionHasErrors('join_code');
    }

    public function test_user_cannot_join_two_teams_for_same_activity(): void
    {
        $activity = Activity::factory()->teamChallenge()->published()->create([
            'min_team_size' => 1,
            'max_team_size' => 4,
        ]);
        $leaderA = User::factory()->learner()->create();
        $leaderB = User::factory()->learner()->create();

        $this->actingAs($leaderA)->post(route('activities.teams.store', $activity), ['name' => 'Alpha']);
        $this->actingAs($leaderB)->post(route('activities.teams.store', $activity), ['name' => 'Beta']);

        $beta = Team::query()->where('name', 'Beta')->first();

        $this->actingAs($leaderA)
            ->post(route('activities.teams.join', $activity), ['join_code' => $beta->join_code])
            ->assertSessionHasErrors('activity');
    }

    public function test_team_registration_respects_activity_capacity(): void
    {
        $activity = Activity::factory()->teamChallenge()->published()->create([
            'capacity' => 2,
            'min_team_size' => 2,
            'max_team_size' => 3,
        ]);
        $leader = User::factory()->learner()->create();
        $member = User::factory()->learner()->create();
        $extraLeader = User::factory()->learner()->create();
        $extraMember = User::factory()->learner()->create();

        $this->actingAs($leader)->post(route('activities.teams.store', $activity), ['name' => 'One']);
        $team = Team::query()->where('name', 'One')->first();
        $this->actingAs($member)->post(route('activities.teams.join', $activity), ['join_code' => $team->join_code]);
        $this->actingAs($leader)->post(route('activities.teams.register', [$activity, $team]))->assertRedirect();

        $this->actingAs($extraLeader)->post(route('activities.teams.store', $activity), ['name' => 'Two']);
        $teamTwo = Team::query()->where('name', 'Two')->first();
        $this->actingAs($extraMember)->post(route('activities.teams.join', $activity), ['join_code' => $teamTwo->join_code]);
        $this->actingAs($extraLeader)
            ->post(route('activities.teams.register', [$activity, $teamTwo]))
            ->assertSessionHasErrors('activity');
    }

    public function test_team_registration_rejects_a_team_that_exceeds_the_maximum_size(): void
    {
        $activity = Activity::factory()->teamChallenge()->published()->create([
            'capacity' => 5,
            'min_team_size' => 1,
            'max_team_size' => 1,
        ]);
        $leader = User::factory()->learner()->create();
        $team = app(\App\Services\TeamService::class)->createTeam($leader, $activity, 'Too Large');

        // Simulate stale or legacy data that bypassed the join rule.
        $extra = User::factory()->learner()->create();
        $membership = new TeamMember;
        $membership->team_id = $team->id;
        $membership->user_id = $extra->id;
        $membership->activity_id = $activity->id;
        $membership->role = 'member';
        $membership->save();

        $this->actingAs($leader)
            ->post(route('activities.teams.register', [$activity, $team]))
            ->assertSessionHasErrors('team');

        $this->assertFalse($team->fresh()->is_registered);
        $this->assertSame(0, $team->registrations()->count());
    }

    public function test_non_leader_cannot_register_team(): void
    {
        $activity = Activity::factory()->teamChallenge()->published()->create([
            'min_team_size' => 1,
            'max_team_size' => 4,
        ]);
        $leader = User::factory()->learner()->create();
        $member = User::factory()->learner()->create();

        $this->actingAs($leader)->post(route('activities.teams.store', $activity), ['name' => 'Crew']);
        $team = Team::query()->first();
        $this->actingAs($member)->post(route('activities.teams.join', $activity), ['join_code' => $team->join_code]);

        $this->actingAs($member)
            ->post(route('activities.teams.register', [$activity, $team]))
            ->assertForbidden();
    }
}
