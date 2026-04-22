<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\LaunchReadinessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LaunchReadinessController extends Controller
{
    public function __construct(protected LaunchReadinessService $readinessService)
    {
    }

    public function index(): View
    {
        $organization = Organization::with(['subscription.plan', 'agentDevices.cameras', 'cameras', 'policies'])
            ->findOrFail(Auth::user()->organization_id);

        $readiness = $this->readinessService->summary($organization);

        return view('dashboard.readiness.index', compact('organization', 'readiness'));
    }
}
