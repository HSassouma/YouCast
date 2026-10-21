<?php

namespace App\Http\Controllers;

use App\Enums\IdeaStatus;
use App\Http\Requests\StoreChallengeIdeaRequest;
use App\Models\ChallengeIdea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChallengeIdeaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ChallengeIdea::class);

        $ideas = $request->user()
            ->challengeIdeas()
            ->latest()
            ->paginate(10);

        return view('ideas.index', [
            'ideas' => $ideas,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ChallengeIdea::class);

        return view('ideas.create');
    }

    public function store(StoreChallengeIdeaRequest $request): RedirectResponse
    {
        $idea = new ChallengeIdea;
        $idea->fill($request->validated());

        // Ownership and moderation state are never mass-assigned.
        $idea->author_id = $request->user()->id;
        $idea->status = IdeaStatus::Pending;
        $idea->save();

        return redirect()
            ->route('ideas.index')
            ->with('status', 'Votre idée a été transmise au BDE.');
    }
}
