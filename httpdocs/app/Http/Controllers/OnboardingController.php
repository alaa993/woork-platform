<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\OrganizationOnboardingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(protected OrganizationOnboardingService $onboardingService)
    {
    }

    public function index(): View
    {
        $organization = Organization::with(['agentDevices', 'cameras'])->findOrFail(Auth::user()->organization_id);
        $onboarding = $this->onboardingService->summary($organization);

        return view('dashboard.onboarding.index', compact('organization', 'onboarding'));
    }
}
