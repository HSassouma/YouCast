<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function store(Request $request, Activity $activity, RegistrationService $service): RedirectResponse
    {
        $this->authorize('register', $activity);

        $service->registerIndividual($request->user(), $activity);

        return back()->with('status', 'Votre inscription est confirmée.');
    }

    public function destroy(Request $request, Activity $activity, RegistrationService $service): RedirectResponse
    {
        $this->authorize('cancelRegistration', $activity);

        $service->cancel($request->user(), $activity);

        return back()->with('status', 'Votre inscription a été annulée.');
    }
}
