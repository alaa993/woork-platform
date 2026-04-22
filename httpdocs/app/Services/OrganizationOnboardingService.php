<?php

namespace App\Services;

use App\Models\Camera;
use App\Models\DailySummary;
use App\Models\Event;
use App\Models\Organization;

class OrganizationOnboardingService
{
    public function summary(Organization $organization): array
    {
        $organization->loadMissing(['agentDevices', 'cameras']);

        $hasAgentDevice = $organization->agentDevices->isNotEmpty();
        $hasPairedDevice = $organization->agentDevices->contains(function ($device) {
            return $device->status === 'online'
                || $device->last_seen_at
                || ! empty($device->api_token_hash);
        });
        $hasCamera = $organization->cameras->isNotEmpty();
        $hasAssignedCamera = $organization->cameras->contains(fn ($camera) => ! empty($camera->agent_device_id));
        $hasOnlineCamera = $organization->cameras->contains(function ($camera) {
            return $camera->stream_status === 'online' && $camera->last_seen_at;
        });
        $hasEvents = Event::where('organization_id', $organization->id)->exists()
            || DailySummary::where('organization_id', $organization->id)->exists();

        $steps = [
            [
                'key' => 'create_device',
                'label' => __('dashboard.onboarding_step_create_device'),
                'completed' => $hasAgentDevice,
                'route' => route('agent-devices.create'),
                'action' => __('dashboard.onboarding_action_create_device'),
            ],
            [
                'key' => 'pair_device',
                'label' => __('dashboard.onboarding_step_pair_device'),
                'completed' => $hasPairedDevice,
                'route' => $hasAgentDevice
                    ? route('agent-devices.install', $organization->agentDevices->sortByDesc('id')->first())
                    : route('agent-devices.create'),
                'action' => __('dashboard.onboarding_action_pair_device'),
            ],
            [
                'key' => 'add_camera',
                'label' => __('dashboard.onboarding_step_add_camera'),
                'completed' => $hasCamera,
                'route' => route('cameras.create'),
                'action' => __('dashboard.onboarding_action_add_camera'),
            ],
            [
                'key' => 'assign_camera',
                'label' => __('dashboard.onboarding_step_assign_camera'),
                'completed' => $hasAssignedCamera,
                'route' => $this->cameraTargetRoute($organization),
                'action' => __('dashboard.onboarding_action_assign_camera'),
            ],
            [
                'key' => 'camera_online',
                'label' => __('dashboard.onboarding_step_first_camera_online'),
                'completed' => $hasOnlineCamera,
                'route' => route('camera-health.index'),
                'action' => __('dashboard.onboarding_action_check_health'),
            ],
            [
                'key' => 'first_event',
                'label' => __('dashboard.onboarding_step_first_event'),
                'completed' => $hasEvents,
                'route' => route('reports.index'),
                'action' => __('dashboard.onboarding_action_open_reports'),
            ],
        ];

        $completedSteps = collect($steps)->where('completed', true)->count();
        $nextStep = collect($steps)->firstWhere('completed', false);

        return [
            'steps' => $steps,
            'completed_steps' => $completedSteps,
            'total_steps' => count($steps),
            'progress_percent' => (int) round(($completedSteps / max(1, count($steps))) * 100),
            'is_complete' => $completedSteps === count($steps),
            'next_step' => $nextStep,
        ];
    }

    protected function cameraTargetRoute(Organization $organization): string
    {
        /** @var Camera|null $camera */
        $camera = $organization->cameras->sortByDesc('id')->first();

        return $camera
            ? route('cameras.edit', $camera)
            : route('cameras.create');
    }
}
