<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Models\Activity;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('create', Activity::class);

        $query = Activity::query()
            ->with('creator')
            ->withCount('activeRegistrations')
            ->latest();

        if ($status = $request->input('status')) {
            if (ActivityStatus::tryFrom($status)) {
                $query->where('status', $status);
            }
        }

        if ($search = $this->bounded($request->input('q'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%');
            });
        }

        return view('admin.activities.index', [
            'activities' => $query->paginate(15)->withQueryString(),
            'statuses' => ActivityStatus::cases(),
            'filters' => [
                'q' => $search ?? '',
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Activity::class);

        return view('admin.activities.create', [
            'types' => ActivityType::cases(),
        ]);
    }

    public function store(StoreActivityRequest $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->normalized($request->validated());

        if ($data['type'] !== ActivityType::TeamChallenge) {
            $data['min_team_size'] = null;
            $data['max_team_size'] = null;
        }

        $activity = Activity::createDraft($data, $request->user());

        $audit->record($request->user(), 'activity.created', $activity);

        return redirect()
            ->route('admin.activities.show', $activity)
            ->with('status', 'Activité créée en brouillon.');
    }

    public function show(Activity $activity): View
    {
        $this->authorize('manageRegistrations', $activity);

        $activity->load([
            'creator',
            'sourceIdea.author',
            'activeRegistrations.user',
            'activeRegistrations.team',
            'teams.members.user',
        ])->loadCount('activeRegistrations');

        return view('admin.activities.show', [
            'activity' => $activity,
        ]);
    }

    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        return view('admin.activities.edit', [
            'activity' => $activity,
            'types' => ActivityType::cases(),
        ]);
    }

    public function update(UpdateActivityRequest $request, Activity $activity, AuditLogger $audit): RedirectResponse
    {
        $data = $this->normalized($request->validated());

        if ($data['type'] !== ActivityType::TeamChallenge) {
            $data['min_team_size'] = null;
            $data['max_team_size'] = null;
        }

        $activity->fill($data);

        if ($activity->isDirty('title')) {
            $activity->slug = Activity::uniqueSlug((string) $activity->title, $activity->id);
        }

        $activity->saveWithUniqueSlug();

        $audit->record($request->user(), 'activity.updated', $activity);

        return redirect()
            ->route('admin.activities.show', $activity)
            ->with('status', 'Activité mise à jour.');
    }

    public function publish(Request $request, Activity $activity, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('publish', $activity);

        $activity->status = ActivityStatus::Published;
        $activity->published_at = $activity->published_at ?? now();
        $activity->closed_at = null;
        $activity->save();

        $audit->record($request->user(), 'activity.published', $activity);

        return back()->with('status', 'Activité publiée.');
    }

    public function close(Request $request, Activity $activity, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('close', $activity);

        $activity->status = ActivityStatus::Closed;
        $activity->closed_at = now();
        $activity->save();

        $audit->record($request->user(), 'activity.closed', $activity);

        return back()->with('status', 'Activité clôturée.');
    }

    /**
     * Take a published activity back to draft.
     *
     * The activity disappears from the catalogue but its registrations and
     * history are kept: nothing is deleted.
     */
    public function unpublish(Request $request, Activity $activity, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('unpublish', $activity);

        $activity->status = ActivityStatus::Draft;
        $activity->published_at = null;
        $activity->closed_at = null;
        $activity->save();

        $audit->record($request->user(), 'activity.unpublished', $activity);

        return back()->with('status', 'Activité dépubliée et repassée en brouillon.');
    }

    public function cancel(Request $request, Activity $activity, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('cancel', $activity);

        $activity->status = ActivityStatus::Cancelled;
        $activity->save();

        $audit->record($request->user(), 'activity.cancelled', $activity);

        return back()->with('status', 'Activité annulée.');
    }

    public function archive(Request $request, Activity $activity, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('archive', $activity);

        $activity->status = ActivityStatus::Archived;
        $activity->save();

        $audit->record($request->user(), 'activity.archived', $activity);

        return back()->with('status', 'Activité archivée.');
    }

    /**
     * Normalise and bound a user supplied filter value.
     */
    private function bounded(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return mb_substr($value, 0, 100);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalized(array $data): array
    {
        $data['type'] = ActivityType::from($data['type']);
        $data['cancellation_allowed'] = (bool) ($data['cancellation_allowed'] ?? false);

        foreach (['starts_at', 'ends_at', 'registration_opens_at', 'registration_deadline'] as $field) {
            $data[$field] = $data[$field] ?? null;
        }

        return $data;
    }
}
