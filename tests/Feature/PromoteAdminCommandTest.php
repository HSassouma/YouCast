<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromoteAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_promotes_an_existing_user(): void
    {
        $user = User::factory()->learner()->create(['email' => 'lead@youcode.local']);

        $this->artisan('youcast:promote-admin', ['email' => 'lead@youcode.local'])
            ->assertSuccessful();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }
}
