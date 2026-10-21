<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\ChallengeIdea;
use App\Models\Conversation;
use App\Models\Team;
use App\Policies\ActivityPolicy;
use App\Policies\ChallengeIdeaPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\TeamPolicy;
use App\Support\UnreadMessages;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // The framework pagination markup (grey/indigo, rounded-md) does not
        // follow the design tokens; the application ships its own views.
        Paginator::defaultView('pagination::youcast');
        Paginator::defaultSimpleView('pagination::youcast-simple');

        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            $path = route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ], absolute: false);

            return rtrim((string) config('app.url'), '/').$path;
        });

        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(ChallengeIdea::class, ChallengeIdeaPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);

        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('password-confirm', function (Request $request) {
            return Limit::perMinute(5)->by('user:'.$request->user()->id.'|'.$request->ip());
        });

        RateLimiter::for('password-change', function (Request $request) {
            return Limit::perMinute(5)->by('user:'.$request->user()->id.'|'.$request->ip());
        });

        RateLimiter::for('admin-actions', function (Request $request) {
            return Limit::perMinute(60)->by('admin:'.$request->user()->id.'|'.$request->ip());
        });

        RateLimiter::for('ideas', function (Request $request) {
            return Limit::perHour(5)->by($this->throttleKey($request));
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(20)->by($this->throttleKey($request));
        });

        RateLimiter::for('teams', function (Request $request) {
            return Limit::perMinute(20)->by($this->throttleKey($request));
        });

        // Opening a private conversation with the BDE.
        RateLimiter::for('message-create', function (Request $request) {
            return Limit::perHour(5)->by($this->throttleKey($request));
        });

        // Answering inside a conversation.
        RateLimiter::for('message-reply', function (Request $request) {
            return Limit::perHour(60)->by($this->throttleKey($request));
        });

        // Record repeated authentication failures. Only the source address is
        // logged so that no personal data ends up in the log files.
        Event::listen(Lockout::class, function (Lockout $event): void {
            Log::warning('Authentication lockout triggered.', [
                'ip' => $event->request->ip(),
            ]);
        });

        // The navigation shows the number of unread messages. One aggregated
        // query, and only for authenticated visitors.
        View::composer('layouts.navigation', function ($view): void {
            $user = Auth::user();

            if (! $user) {
                $view->with('unreadMessages', 0);

                return;
            }

            // A page that already knows the count (the dashboard) does not
            // need a second query.
            if (array_key_exists('unreadMessages', $view->getData())) {
                return;
            }

            $view->with('unreadMessages', UnreadMessages::countFor($user));
        });
    }

    /**
     * Build a throttle key that identifies the caller by account when known.
     */
    private function throttleKey(Request $request): string
    {
        $userId = $request->user()?->id;

        return $userId ? 'user:'.$userId : 'ip:'.$request->ip();
    }
}
