<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityType;
use App\Enums\IdeaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConvertChallengeIdeaRequest;
use App\Http\Requests\ReviewChallengeIdeaRequest;
use App\Models\Activity;
use App\Models\ChallengeIdea;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChallengeIdeaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('moderate', ChallengeIdea::class);

        $query = ChallengeIdea::query()->with(['author', 'reviewer', 'activity'])->latest();

        if ($status = $request->input('status')) {
            if (IdeaStatus::tryFrom($status)) {
                $query->where('status', $status);
            }
        }

        return view('admin.ideas.index', [
            'ideas' => $query->paginate(15)->withQueryString(),
            'statuses' => IdeaStatus::cases(),
            'filters' => ['status' => $status],
        ]);
    }

    public function show(Request $request, ChallengeIdea $idea): View
    {
        $this->authorize('moderate', ChallengeIdea::class);
        $this->authorize('view', $idea);

        $idea->load(['author', 'reviewer', 'activity']);

        return view('admin.ideas.show', [
            'idea' => $idea,
            'types' => ActivityType::cases(),
        ]);
    }

    public function review(ReviewChallengeIdeaRequest $request, ChallengeIdea $idea, AuditLogger $audit): RedirectResponse
    {
        $decision = IdeaStatus::from($request->validated('decision'));

        // Moderation state is never mass-assigned.
        $idea->status = $decision;
        $idea->reviewer_id = $request->user()->id;
        $idea->review_notes = $request->validated('review_notes');
        $idea->reviewed_at = now();
        $idea->save();

        $audit->record($request->user(), 'idea.'.$decision->value, $idea);

        return back()->with('status', 'Idée marquée comme '.$decision->label().'.');
    }

    public function convert(ConvertChallengeIdeaRequest $request, ChallengeIdea $idea, AuditLogger $audit): RedirectResponse
    {
        $type = ActivityType::from($request->validated('type'));

        $activity = Activity::createDraft([
            'type' => $type,
            'title' => $idea->title,
            'description' => $idea->description,
            'category' => $idea->category,
            'cancellation_allowed' => true,
            'min_team_size' => $type === ActivityType::TeamChallenge ? 2 : null,
            'max_team_size' => $type === ActivityType::TeamChallenge ? 4 : null,
        ], $request->user(), $idea);

        $audit->record($request->user(), 'idea.converted', $idea, [
            'activity_id' => $activity->id,
        ]);

        return redirect()
            ->route('admin.activities.edit', $activity)
            ->with('status', "Idée convertie en brouillon d'activité. Complétez les détails avant de publier.");
    }
}
