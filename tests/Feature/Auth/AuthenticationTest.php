<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('Créer un compte')
            ->assertSee(route('register'), false)
            ->assertSee('Mot de passe oublié ?')
            ->assertSee(route('password.request'), false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->learner()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        // A learner lands on the activities page.
        $response->assertRedirect(route('activities.index', absolute: false));
    }

    public function test_administrators_land_on_the_administration_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.activities.index', absolute: false));
    }

    public function test_unknown_email_and_wrong_password_return_the_same_generic_error(): void
    {
        $user = User::factory()->create();
        $expectedErrors = ['login' => 'Email ou mot de passe incorrect.'];

        $this->from(route('login'))
            ->post('/login', [
                'email' => 'unknown@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors($expectedErrors);

        $this->assertGuest();

        $this->from(route('login'))
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors($expectedErrors);

        $this->assertGuest();
    }

    public function test_login_error_is_displayed_in_an_accessible_alert(): void
    {
        $user = User::factory()->create();

        $this->from(route('login'))
            ->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors(['login' => 'Email ou mot de passe incorrect.']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="login-error"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('bg-danger-50', false)
            ->assertSee('Email ou mot de passe incorrect.')
            ->assertDontSee('Ces identifiants ne correspondent pas', false);
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->from(route('login'))
            ->post('/login', [])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
