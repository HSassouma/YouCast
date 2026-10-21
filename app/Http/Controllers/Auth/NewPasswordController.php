<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the new password form for the account selected in the request.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->userFromSession($request);

        if ($user === null) {
            return $this->expired($request);
        }

        return view('auth.reset-password', [
            'email' => $user->email,
        ]);
    }

    /**
     * Store the new password for the account selected in the request.
     *
     * WARNING: this flow does not verify ownership of the email address. It is
     * intentionally limited to the simplified product flow and is not a
     * production-grade identity verification mechanism.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $this->userFromSession($request);

        if ($user === null) {
            return $this->expired($request);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        $request->session()->forget('password_reset.user_id');
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', __('passwords.reset'));
    }

    private function userFromSession(Request $request): ?User
    {
        $userId = $request->session()->get('password_reset.user_id');

        return is_numeric($userId) ? User::find((int) $userId) : null;
    }

    private function expired(Request $request): RedirectResponse
    {
        $request->session()->forget('password_reset.user_id');

        return redirect()
            ->route('password.request')
            ->with('reset_error', __('passwords.session'));
    }
}
