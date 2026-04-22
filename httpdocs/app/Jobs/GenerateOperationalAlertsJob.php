<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\Camera;
use App\Models\Policy;
use App\Services\AlertDispatcher;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateOperationalAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AlertDispatcher $dispatcher): void
    {
        $cameras = Camera::with([
            'agentDevice',
            'heartbeats' => fn ($query) => $query->latest('checked_at')->limit(1),
        ])->get();

        $policies = Policy::whereIn('organization_id', $cameras->pluck('organization_id')->unique())
            ->get()
            ->keyBy('organization_id');

        foreach ($cameras as $camera) {
            $heartbeat = $camera->heartbeats->first();
            $policy = $policies[$camera->organization_id] ?? null;
            $thresholds = $this->thresholdsFor($policy);
            $offlineAfterMinutes = (int) ($thresholds['camera_offline_after_minutes'] ?? 5);
            $warningAfterMinutes = (int) ($thresholds['camera_warning_after_minutes'] ?? 3);

            $this->checkOffline($camera, $heartbeat, $dispatcher, $offlineAfterMinutes);
            $this->checkWarning($camera, $heartbeat, $dispatcher, $warningAfterMinutes);
            $this->checkDetectorFallback($camera, $heartbeat, $dispatcher, (bool) ($thresholds['detector_fallback'] ?? true));
            $this->checkPhoneSupport($camera, $heartbeat, $dispatcher, (bool) ($thresholds['phone_detection_unavailable'] ?? true));
        }
    }

    protected function thresholdsFor(?Policy $policy): array
    {
        $defaults = config('woork.thresholds', []);
        if (! $policy) {
            return $defaults;
        }
        return array_merge($defaults, $policy->thresholds ?? []);
    }

    protected function checkOffline(Camera $camera, mixed $heartbeat, AlertDispatcher $dispatcher, int $offlineAfterMinutes): void
    {
        $isOffline = in_array($camera->stream_status, ['offline', 'misconfigured', 'pending'], true)
            || ! $camera->last_seen_at
            || $camera->last_seen_at->lt(now()->subMinutes($offlineAfterMinutes));

        if ($isOffline) {
            $dispatcher->dispatch([
                'organization_id' => $camera->organization_id,
                'camera_id' => $camera->id,
                'agent_device_id' => $camera->agent_device_id,
                'employee_id' => null,
                'room_id' => $camera->room_id,
                'kind' => 'camera_offline',
                'level' => 'critical',
                'source' => 'operations',
                'message' => "Camera {$camera->name} is offline or stale.",
                'rules' => [
                    'stream_status' => $camera->stream_status,
                    'last_seen_at' => optional($camera->last_seen_at)?->toIso8601String(),
                    'offline_after_minutes' => $offlineAfterMinutes,
                ],
            ], ['in_app']);
            return;
        }

        $this->resolveAlert($camera, 'camera_offline');
    }

    protected function checkWarning(Camera $camera, mixed $heartbeat, AlertDispatcher $dispatcher, int $warningAfterMinutes): void
    {
        $isWarning = $camera->stream_status === 'warning'
            || ($heartbeat && $heartbeat->health_message && $heartbeat->checked_at?->gt(now()->subMinutes($warningAfterMinutes)));

        if ($isWarning) {
            $dispatcher->dispatch([
                'organization_id' => $camera->organization_id,
                'camera_id' => $camera->id,
                'agent_device_id' => $camera->agent_device_id,
                'employee_id' => null,
                'room_id' => $camera->room_id,
                'kind' => 'camera_warning',
                'level' => 'warning',
                'source' => 'operations',
                'message' => "Camera {$camera->name} reported a warning state.",
                'rules' => [
                    'stream_status' => $camera->stream_status,
                    'health_message' => $heartbeat?->health_message,
                ],
            ], ['in_app']);
            return;
        }

        $this->resolveAlert($camera, 'camera_warning');
    }

    protected function checkDetectorFallback(Camera $camera, mixed $heartbeat, AlertDispatcher $dispatcher, bool $enabled): void
    {
        if (! $enabled) {
            $this->resolveAlert($camera, 'detector_fallback');
            return;
        }

        $observations = $heartbeat?->observations ?? [];
        $fallbackRequired = (bool) ($observations['fallback_required'] ?? false);

        if ($fallbackRequired) {
            $dispatcher->dispatch([
                'organization_id' => $camera->organization_id,
                'camera_id' => $camera->id,
                'agent_device_id' => $camera->agent_device_id,
                'employee_id' => null,
                'room_id' => $camera->room_id,
                'kind' => 'detector_fallback',
                'level' => 'warning',
                'source' => 'operations',
                'message' => "Camera {$camera->name} is running on fallback analyzer mode.",
                'rules' => [
                    'analyzer' => $heartbeat?->analyzer,
                    'observations' => $observations,
                ],
            ], ['in_app']);
            return;
        }

        $this->resolveAlert($camera, 'detector_fallback');
    }

    protected function checkPhoneSupport(Camera $camera, mixed $heartbeat, AlertDispatcher $dispatcher, bool $enabled): void
    {
        if (! $enabled) {
            $this->resolveAlert($camera, 'phone_detection_unavailable');
            return;
        }

        $analysisConfig = $camera->analysis_config ?? [];
        $observations = $heartbeat?->observations ?? [];
        $phoneSupported = $observations['phone_supported'] ?? null;

        if (($analysisConfig['phone_event_type'] ?? null) && $phoneSupported === false) {
            $dispatcher->dispatch([
                'organization_id' => $camera->organization_id,
                'camera_id' => $camera->id,
                'agent_device_id' => $camera->agent_device_id,
                'employee_id' => null,
                'room_id' => $camera->room_id,
                'kind' => 'phone_detection_unavailable',
                'level' => 'info',
                'source' => 'operations',
                'message' => "Camera {$camera->name} does not currently support phone detection.",
                'rules' => [
                    'detector' => $observations['detector'] ?? $analysisConfig['detector'] ?? null,
                    'detector_bundle' => $analysisConfig['detector_bundle'] ?? null,
                ],
            ], ['in_app']);
            return;
        }

        $this->resolveAlert($camera, 'phone_detection_unavailable');
    }

    protected function resolveAlert(Camera $camera, string $kind): void
    {
        Alert::where('organization_id', $camera->organization_id)
            ->where('camera_id', $camera->id)
            ->where('kind', $kind)
            ->where('source', 'operations')
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'resolved_at' => Carbon::now(),
            ]);
    }
}
