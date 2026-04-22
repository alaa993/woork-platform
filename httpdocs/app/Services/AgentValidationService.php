<?php

namespace App\Services;

use App\Models\AgentDevice;

class AgentValidationService
{
    public function summary(AgentDevice $agentDevice): array
    {
        $agentDevice->loadMissing(['cameras.room', 'heartbeats', 'cameraHeartbeats.camera']);

        $deviceSeen = (bool) ($agentDevice->last_seen_at || $agentDevice->heartbeats->isNotEmpty());
        $hasCameras = $agentDevice->cameras->isNotEmpty();
        $onlineCameras = $agentDevice->cameras->where('stream_status', 'online')->count();
        $warningCameras = $agentDevice->cameras->where('stream_status', 'warning')->count();
        $offlineCameras = $agentDevice->cameras->filter(fn ($camera) => in_array($camera->stream_status, ['offline', 'misconfigured', 'pending', null], true))->count();

        $steps = [
            [
                'label' => __('dashboard.validation_step_install_agent'),
                'completed' => $deviceSeen,
                'detail' => $deviceSeen
                    ? __('dashboard.validation_seen_detail')
                    : __('dashboard.validation_pending_detail'),
            ],
            [
                'label' => __('dashboard.validation_step_run_doctor'),
                'completed' => $deviceSeen,
                'detail' => __('dashboard.validation_doctor_detail'),
            ],
            [
                'label' => __('dashboard.validation_step_run_benchmark'),
                'completed' => $deviceSeen,
                'detail' => __('dashboard.validation_benchmark_detail'),
            ],
            [
                'label' => __('dashboard.validation_step_assign_camera'),
                'completed' => $hasCameras,
                'detail' => $hasCameras
                    ? __('dashboard.validation_cameras_detail', ['count' => $agentDevice->cameras->count()])
                    : __('dashboard.validation_no_camera_detail'),
            ],
            [
                'label' => __('dashboard.validation_step_first_heartbeat'),
                'completed' => $onlineCameras > 0 || $warningCameras > 0,
                'detail' => __('dashboard.validation_heartbeat_detail'),
            ],
            [
                'label' => __('dashboard.validation_step_first_online'),
                'completed' => $onlineCameras > 0,
                'detail' => __('dashboard.validation_online_detail', ['count' => $onlineCameras]),
            ],
        ];

        $completed = collect($steps)->where('completed', true)->count();

        return [
            'steps' => $steps,
            'progress_percent' => (int) round(($completed / max(1, count($steps))) * 100),
            'completed_steps' => $completed,
            'total_steps' => count($steps),
            'counters' => [
                'online' => $onlineCameras,
                'warning' => $warningCameras,
                'offline' => $offlineCameras,
                'total' => $agentDevice->cameras->count(),
            ],
            'commands' => [
                'doctor' => 'woork-agent doctor --config config.json',
                'benchmark' => 'woork-agent benchmark --config config.json --seconds 8',
                'pair' => sprintf('woork-agent pair --config config.json --pairing-token %s', $agentDevice->pairing_token),
                'run' => 'woork-agent run --config config.json',
            ],
        ];
    }
}
