<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Check whether the given address belongs to an account.
     *
     * WARNING: this simplified flow deliberately follows the product
     * requirement and does not prove that the visitor owns the email address.
     * It must not be presented as a production-grade account recovery
     * mechanism without an additional identity check.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null) {
            return back()
                ->withInput($request->only('email'))
                ->with('account_missing', __('passwords.user'));
        }

        $request->session()->put('password_reset.user_id', $user->getKey());
        $request->session()->regenerate();

        return redirect()->route('password.reset');
    }
}
