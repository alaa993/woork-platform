<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CameraHealthController extends Controller
{
    public function index(): View
    {
        $organizationId = Auth::user()->organization_id;

        $cameras = Camera::where('organization_id', $organizationId)
            ->with([
                'room',
                'agentDevice',
                'heartbeats' => fn ($query) => $query->latest('checked_at')->limit(5),
            ])
            ->orderByRaw("CASE WHEN stream_status = 'online' THEN 0 WHEN stream_status = 'warning' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->get();

        $cameras->each(fn (Camera $camera) => $camera->setAttribute('runtime_diagnostics', $this->runtimeDiagnostics($camera)));

        $stats = [
            'total' => $cameras->count(),
            'online' => $cameras->where('stream_status', 'online')->count(),
            'warning' => $cameras->where('stream_status', 'warning')->count(),
            'offline' => $cameras->filter(fn ($camera) => in_array($camera->stream_status, ['offline', 'misconfigured', 'pending', null], true))->count(),
        ];

        return view('dashboard.camera-health.index', compact('cameras', 'stats'));
    }

    public function show(Camera $camera): View
    {
        abort_unless($camera->organization_id === Auth::user()->organization_id, 404);

        $camera->load([
            'room',
            'agentDevice',
            'heartbeats' => fn ($query) => $query->latest('checked_at')->limit(20),
        ]);
        $camera->setAttribute('runtime_diagnostics', $this->runtimeDiagnostics($camera));

        $recentEvents = Event::where('camera_id', $camera->id)
            ->with(['employee:id,name', 'room:id,name'])
            ->latest('started_at')
            ->limit(30)
            ->get();

        $eventBreakdown = $recentEvents
            ->groupBy('type')
            ->map(fn ($rows) => $rows->count())
            ->sortDesc()
            ->all();

        return view('dashboard.camera-health.show', compact('camera', 'recentEvents', 'eventBreakdown'));
    }

    protected function runtimeDiagnostics(Camera $camera): array
    {
        $heartbeat = $camera->heartbeats->first();
        $observations = $heartbeat?->observations ?? [];
        $analysisConfig = $camera->analysis_config ?? [];

        return [
            'analyzer' => $heartbeat?->analyzer ?: $analysisConfig['analyzer'] ?? null,
            'detector' => $observations['detector'] ?? $analysisConfig['detector'] ?? null,
            'detector_bundle' => $analysisConfig['detector_bundle'] ?? null,
            'presence_state' => $observations['presence_state'] ?? null,
            'person_count' => $observations['person_count'] ?? null,
            'phone_count' => $observations['phone_count'] ?? null,
            'phone_supported' => $observations['phone_supported'] ?? null,
            'active_track_ids' => $observations['active_track_ids'] ?? [],
        ];
    }
}
