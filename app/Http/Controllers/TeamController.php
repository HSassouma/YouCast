<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinTeamRequest;
use App\Http\Requests\StoreTeamRequest;
use App\Models\Activity;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function store(StoreTeamRequest $request, Activity $activity, TeamService $service): RedirectResponse
    {
        $team = $service->createTeam($request->user(), $activity, $request->validated('name'));

        return back()->with('status', 'Équipe créée. Partagez le code '.$team->join_code.' avec vos coéquipiers.');
    }

    public function join(JoinTeamRequest $request, Activity $activity, TeamService $service): RedirectResponse
    {
        $team = $service->joinTeam($request->user(), $activity, $request->validated('join_code'));

        return back()->with('status', "Vous avez rejoint l'équipe ".$team->name.'.');
    }

    public function leave(Request $request, Activity $activity, Team $team, TeamService $service): RedirectResponse
    {
        abort_unless($team->activity_id === $activity->id, 404);

        $this->authorize('leave', $team);

        $service->leaveTeam($request->user(), $team);

        return back()->with('status', "Vous avez quitté l'équipe.");
    }

    public function destroy(Request $request, Activity $activity, Team $team, TeamService $service): RedirectResponse
    {
        abort_unless($team->activity_id === $activity->id, 404);

        $this->authorize('disband', $team);

        $service->disbandTeam($request->user(), $team);

        return back()->with('status', "L'équipe a été dissoute.");
    }

    public function register(Request $request, Activity $activity, Team $team, TeamService $service): RedirectResponse
    {
        abort_unless($team->activity_id === $activity->id, 404);

        $this->authorize('register', $team);

        $service->registerTeam($request->user(), $team);

        return back()->with('status', 'Votre équipe est désormais inscrite.');
    }

    public function unregister(Request $request, Activity $activity, Team $team, TeamService $service): RedirectResponse
    {
        abort_unless($team->activity_id === $activity->id, 404);

        $this->authorize('unregister', $team);

        $service->unregisterTeam($request->user(), $team);

        return back()->with('status', "L'inscription de votre équipe a été annulée.");
    }
}
