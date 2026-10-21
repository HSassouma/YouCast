<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\RegistrationStatus;
use App\Models\Activity;
use App\Models\Registration;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $query = Activity::query()
            ->publiclyVisible()
            ->withCount(['activeRegistrations'])
            ->orderByRaw('case when status = ? then 0 else 1 end', [ActivityStatus::Published->value])
            ->orderBy('starts_at');

        // Filter values are bounded so that a crafted query string cannot turn
        // the catalogue into an expensive scan.
        if ($search = $this->bounded($request->input('q'), 100)) {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%')
                    ->orWhere('location', 'like', '%'.$search.'%');
            });
        }

        if ($type = $request->input('type')) {
            if (ActivityType::tryFrom($type)) {
                $query->where('type', $type);
            }
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($request->input('open') === '1') {
            $query->where('status', ActivityStatus::Published)
                ->where(function ($builder) {
                    $builder->whereNull('registration_deadline')
                        ->orWhere('registration_deadline', '>=', now());
                });
        }

        $categories = Activity::query()
            ->publiclyVisible()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        // The registration state of the visitor is resolved with a single
        // query so the catalogue never triggers an N+1 lookup.
        $registeredActivityIds = $request->user()
            ? Registration::query()
                ->where('user_id', $request->user()->id)
                ->where('status', RegistrationStatus::Registered)
                ->pluck('activity_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        return view('activities.index', [
            'activities' => $query->paginate(9)->withQueryString(),
            'filters' => [
                'q' => $search ?? '',
                'type' => $type,
                'category' => $category,
                'open' => $request->input('open'),
            ],
            'categories' => $categories,
            'types' => ActivityType::cases(),
            'registeredActivityIds' => $registeredActivityIds,
        ]);
    }

    public function show(Request $request, Activity $activity): View
    {
        $this->authorize('view', $activity);

        // Only the counters and the related idea are needed here. The visitor's
        // own team is loaded separately below so that the whole team roster of
        // every team in the activity is never loaded.
        $activity->load(['creator', 'sourceIdea'])
            ->loadCount('activeRegistrations');

        $user = $request->user();
        $registration = null;
        $membership = null;

        if ($user) {
            $registration = Registration::query()
                ->where('user_id', $user->id)
                ->where('activity_id', $activity->id)
                ->where('status', RegistrationStatus::Registered)
                ->first();

            $membership = TeamMember::query()
                ->with('team.members.user')
                ->where('user_id', $user->id)
                ->where('activity_id', $activity->id)
                ->first();
        }

        return view('activities.show', [
            'activity' => $activity,
            'registration' => $registration,
            'membership' => $membership,
        ]);
    }

    /**
     * Normalise and bound a user supplied filter value.
     */
    private function bounded(mixed $value, int $maxLength): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return mb_substr($value, 0, $maxLength);
    }
}
