<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_routes(): void
    {
        $this->get(route('admin.activities.index'))->assertRedirect(route('login'));
    }

    public function test_learner_cannot_access_admin_routes(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)
            ->get(route('admin.activities.index'))
            ->assertForbidden();
    }

    public function test_learner_cannot_create_activity(): void
    {
        $learner = User::factory()->learner()->create();

        $this->actingAs($learner)
            ->post(route('admin.activities.store'), [
                'type' => 'event',
                'title' => 'Forbidden',
                'description' => 'Should not work',
            ])
            ->assertForbidden();
    }

    public function test_public_registration_cannot_assign_admin_role(): void
    {
        $this->post('/register', [
            'name' => 'Attacker',
            'email' => 'attacker@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $this->assertAuthenticated();
        $this->assertSame(UserRole::Learner, User::query()->where('email', 'attacker@example.com')->first()->role);
    }

    public function test_learner_cannot_view_draft_activity(): void
    {
        $learner = User::factory()->learner()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($learner)
            ->get(route('activities.show', $activity))
            ->assertForbidden();
    }

    public function test_admin_can_view_draft_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create(['created_by' => $admin->id]);

        $this->actingAs($admin)
            ->get(route('activities.show', $activity))
            ->assertOk();
    }
}
