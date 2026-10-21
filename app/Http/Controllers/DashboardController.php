<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Enums\IdeaStatus;
use App\Enums\RegistrationStatus;
use App\Models\Activity;
use App\Models\ChallengeIdea;
use App\Models\Registration;
use App\Models\Team;
use App\Support\UnreadMessages;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('dashboard', [
                'isAdmin' => true,
                'stats' => [
                    'published' => Activity::query()->where('status', ActivityStatus::Published)->count(),
                    'drafts' => Activity::query()->where('status', ActivityStatus::Draft)->count(),
                    'pending_ideas' => ChallengeIdea::query()->where('status', IdeaStatus::Pending)->count(),
                    'registrations' => Registration::query()->where('status', RegistrationStatus::Registered)->count(),
                    'teams' => Team::query()->count(),
                ],
                'unreadMessages' => UnreadMessages::countFor($user),
                'recentActivities' => Activity::query()->latest()->limit(5)->get(),
                'pendingIdeas' => ChallengeIdea::query()
                    ->with('author')
                    ->where('status', IdeaStatus::Pending)
                    ->latest()
                    ->limit(5)
                    ->get(),
            ]);
        }

        $registrations = $user->registrations()
            ->with('activity')
            ->where('status', RegistrationStatus::Registered)
            ->latest()
            ->get();

        $ideas = $user->challengeIdeas()->latest()->limit(5)->get();

        return view('dashboard', [
            'isAdmin' => false,
            'registrations' => $registrations,
            'ideas' => $ideas,
            'openActivities' => Activity::query()
                ->where('status', ActivityStatus::Published)
                ->orderBy('starts_at')
                ->limit(4)
                ->get(),
        ]);
    }
}
