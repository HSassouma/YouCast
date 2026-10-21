<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_security_headers_are_present(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
    }

    public function test_login_rejects_an_external_intended_redirect(): void
    {
        $user = User::factory()->learner()->create();

        $this->withSession(['url.intended' => 'https://attacker.example/phishing'])
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect(route('activities.index', absolute: false));
    }

    public function test_login_keeps_a_same_site_intended_redirect(): void
    {
        $user = User::factory()->learner()->create();
        $target = route('about');

        $this->withSession(['url.intended' => $target])
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect($target);
    }

    public function test_password_changes_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user)
                ->put(route('password.update'), [
                    'current_password' => 'incorrect-password',
                    'password' => 'new-secure-password',
                    'password_confirmation' => 'new-secure-password',
                ])
                ->assertStatus(302);
        }

        $this->actingAs($user)
            ->put(route('password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertStatus(429);
    }

    public function test_administration_actions_are_rate_limited(): void
    {
        $admin = User::factory()->admin()->create();

        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->actingAs($admin)
                ->get(route('admin.activities.index'))
                ->assertOk();
        }

        $this->actingAs($admin)
            ->get(route('admin.activities.index'))
            ->assertStatus(429);
    }

    public function test_password_confirmation_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($user)
                ->post(route('password.confirm'), ['password' => 'incorrect-password'])
                ->assertStatus(302);
        }

        $this->actingAs($user)
            ->post(route('password.confirm'), ['password' => 'incorrect-password'])
            ->assertStatus(429);
    }
}
