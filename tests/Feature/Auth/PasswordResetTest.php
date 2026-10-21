<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_links_to_the_forgot_password_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Mot de passe oublié ?')
            ->assertSee(route('password.request'), false);
    }

    public function test_forgot_password_page_contains_an_accessible_email_form(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Mot de passe oublié')
            ->assertSee('Adresse e-mail')
            ->assertSee('Continuer')
            ->assertSee('Aucun e-mail ne sera envoyé.')
            ->assertSee('action="'.route('password.check').'"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_known_email_opens_the_new_password_form_without_sending_email(): void
    {
        Mail::fake();

        $user = User::factory()->learner()->create();

        $this->post(route('password.check'), ['email' => $user->email])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHas('password_reset.user_id', $user->id);

        $this->get(route('password.reset'))
            ->assertOk()
            ->assertSee('Nouveau mot de passe')
            ->assertSee('Confirmation du mot de passe')
            ->assertSee($user->email)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('name="token"', false);

        $this->assertDatabaseCount('password_reset_tokens', 0);
        Mail::assertNothingSent();
    }

    public function test_password_reset_notifications_are_disabled(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $thrown = false;

        try {
            $user->sendPasswordResetNotification('fake-token');
        } catch (\LogicException) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
        Mail::assertNothingSent();
    }

    public function test_unknown_email_shows_a_styled_alert_and_a_register_link(): void
    {
        Mail::fake();

        $this->from(route('password.request'))
            ->post(route('password.check'), ['email' => 'unknown@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('account_missing', __('passwords.user'));

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('id="account-missing"', false)
            ->assertSee('role="alert"', false)
            ->assertSee('border-danger-200', false)
            ->assertSee('bg-danger-50', false)
            ->assertSee(__('passwords.user'))
            ->assertSee(route('register'), false);

        Mail::assertNothingSent();
    }

    public function test_invalid_email_is_rejected_without_sending_email(): void
    {
        Mail::fake();

        $this->from(route('password.request'))
            ->post(route('password.check'), ['email' => 'not-an-email'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    public function test_reset_form_requires_an_active_reset_request(): void
    {
        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('reset_error', __('passwords.session'));
    }

    public function test_password_can_be_reset_without_an_email_token(): void
    {
        Mail::fake();

        $user = User::factory()->learner()->create();
        $originalRememberToken = $user->remember_token;

        $this->post(route('password.check'), ['email' => $user->email])
            ->assertRedirect(route('password.reset'));

        $this->post(route('password.store'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('passwords.reset'));

        $user->refresh();

        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertNotSame('new-secure-password', $user->password);
        $this->assertNotSame($originalRememberToken, $user->remember_token);
        $this->assertDatabaseCount('password_reset_tokens', 0);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(__('passwords.reset'));

        // The new password works with the existing login flow.
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'new-secure-password',
        ])->assertRedirect(route('activities.index', absolute: false));

        $this->assertAuthenticatedAs($user);
        Mail::assertNothingSent();
    }

    public function test_reset_request_cannot_be_reused_after_a_successful_change(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.check'), ['email' => $user->email]);

        $this->post(route('password.store'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect(route('login'));

        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('reset_error', __('passwords.session'));
    }

    public function test_password_reset_requires_matching_confirmation(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.check'), ['email' => $user->email]);

        $this->post(route('password.store'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));

        // The request remains active after a validation error.
        $this->get(route('password.reset'))->assertOk();
    }

    public function test_password_reset_requires_a_valid_new_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.check'), ['email' => $user->email]);

        $this->post(route('password.store'), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_submission_without_an_active_request_changes_nothing(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.store'), [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('reset_error', __('passwords.session'));

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_email_checks_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.check'), ['email' => $user->email])
                ->assertStatus(302);
        }

        $this->post(route('password.check'), ['email' => $user->email])
            ->assertStatus(429);
    }

    public function test_reset_submissions_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSession(['password_reset.user_id' => $user->id])
                ->post(route('password.store'), [
                    'password' => 'new-secure-password',
                    'password_confirmation' => 'different-password',
                ])->assertStatus(302);
        }

        $this->withSession(['password_reset.user_id' => $user->id])
            ->post(route('password.store'), [
                'password' => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ])->assertStatus(429);
    }
}
