<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlertsController extends Controller
{
    public function index(Request $request): View
    {
        $query = Alert::where('organization_id', Auth::user()->organization_id)
            ->with(['camera:id,name', 'agentDevice:id,name', 'employee:id,name', 'room:id,name']);

        if ($source = $request->string('source')->toString()) {
            $query->where('source', $source);
        }

        if ($level = $request->string('level')->toString()) {
            $query->where('level', $level);
        }

        if ($state = $request->string('state')->toString()) {
            $query->when(
                $state === 'active',
                fn ($builder) => $builder->where('is_active', true),
                fn ($builder) => $builder->where('is_active', false)
            );
        }

        if ($kind = $request->string('kind')->toString()) {
            $query->where('kind', $kind);
        }

        $alerts = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('dashboard.alerts.index', [
            'alerts' => $alerts,
            'filters' => [
                'source' => $source,
                'level' => $level,
                'state' => $state,
                'kind' => $kind,
            ],
            'kindOptions' => Alert::where('organization_id', Auth::user()->organization_id)
                ->distinct()
                ->orderBy('kind')
                ->pluck('kind')
                ->filter()
                ->values(),
        ]);
    }

    public function resolve(Alert $alert): RedirectResponse
    {
        abort_unless($alert->organization_id === Auth::user()->organization_id, 404);

        $alert->update([
            'is_active' => false,
            'resolved_at' => Carbon::now(),
        ]);

        return back()->with('ok', __('dashboard.alert_marked_resolved'));
    }
}
