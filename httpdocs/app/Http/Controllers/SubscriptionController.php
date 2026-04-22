<?php
namespace App\Http\Controllers;
use App\Services\OrganizationUsageService;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function __construct(protected OrganizationUsageService $usageService)
    {
    }

    public function index() {
        $org = Auth::user()->organization;
        $plan = $org?->currentPlan();
        $sub  = $org?->subscription;
        $usage = $org ? $this->usageService->summary($org) : null;

        return view('dashboard.subscription.index', compact('org','plan','sub', 'usage'));
    }
}
