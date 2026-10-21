<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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

    public function test_forgot_password_page_explains_the_private_reset_link_and_uses_csrf(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Mot de passe oublié')
            ->assertSee('Adresse e-mail')
            ->assertSee('Continuer')
            ->assertSee('Si un compte correspond à cette adresse')
            ->assertSee('action="'.route('password.check').'"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('name="_token"', false);
    }

    public function test_known_email_receives_a_time_limited_password_reset_notification(): void
    {
        Notification::fake();
        $user = User::factory()->learner()->create();

        $this->from(route('password.request'))
            ->post(route('password.check'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', __('passwords.sent'));

        Notification::assertSentTo($user, ResetPassword::class);
        $notification = Notification::sent($user, ResetPassword::class)->first();
        $this->assertNotEmpty($notification->token);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);

        $resetUrl = $notification->toMail($user)->actionUrl;
        $this->assertStringStartsWith(rtrim((string) config('app.url'), '/').'/reset-password?', $resetUrl);
    }

    public function test_unknown_email_gets_the_same_response_without_account_enumeration(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.check'), ['email' => 'unknown@example.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', __('passwords.sent'))
            ->assertSessionMissing('account_missing');

        Notification::assertNothingSent();
    }

    public function test_invalid_email_is_rejected(): void
    {
        $this->from(route('password.request'))
            ->post(route('password.check'), ['email' => 'not-an-email'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
    }

    public function test_password_reset_requires_the_email_token_and_can_only_be_used_once(): void
    {
        Notification::fake();
        $user = User::factory()->learner()->create();
        $originalRememberToken = $user->remember_token;

        $this->post(route('password.check'), ['email' => $user->email]);
        $notification = Notification::sent($user, ResetPassword::class)->first();
        $resetUrl = route('password.reset', ['token' => $notification->token, 'email' => $user->email]);

        $this->get($resetUrl)
            ->assertOk()
            ->assertSee('name="token"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertDontSee('Aucun e-mail ne sera envoyé.');

        $this->post(route('password.store'), [
            'email' => $user->email,
            'token' => $notification->token,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', __('passwords.reset'));

        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertNotSame('new-secure-password', $user->password);
        $this->assertNotSame($originalRememberToken, $user->remember_token);
        $this->assertDatabaseCount('password_reset_tokens', 0);

        $this->post(route('password.store'), [
            'email' => $user->email,
            'token' => $notification->token,
            'password' => 'another-secure-password',
            'password_confirmation' => 'another-secure-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'new-secure-password',
        ])->assertRedirect(route('activities.index', absolute: false));
    }

    public function test_expired_reset_token_cannot_change_the_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.check'), ['email' => $user->email]);
        $notification = Notification::sent($user, ResetPassword::class)->first();

        Carbon::setTestNow(now()->addMinutes(61));
        try {
            $this->post(route('password.store'), [
                'email' => $user->email,
                'token' => $notification->token,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])->assertSessionHasErrors('email');
        } finally {
            Carbon::setTestNow();
        }

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_reset_token_is_not_flashed_to_the_session_after_validation_failure(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.check'), ['email' => $user->email]);
        $notification = Notification::sent($user, ResetPassword::class)->first();

        $this->from(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
            ->post(route('password.store'), [
                'email' => $user->email,
                'token' => $notification->token,
                'password' => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('_old_input.token');
    }

    public function test_email_reset_requests_are_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.check'), ['email' => $user->email])->assertStatus(302);
        }

        $this->post(route('password.check'), ['email' => $user->email])->assertStatus(429);
    }

    public function test_reset_submissions_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.store'), [
                'email' => $user->email,
                'token' => 'invalid-token',
                'password' => 'new-secure-password',
                'password_confirmation' => 'different-password',
            ])->assertStatus(302);
        }

        $this->post(route('password.store'), [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'new-secure-password',
            'password_confirmation' => 'different-password',
        ])->assertStatus(429);
    }
}
