<?php

namespace App\Services;

use App\Models\AgentRelease;
use App\Models\Alert;
use App\Models\DailySummary;
use App\Models\Organization;

class LaunchReadinessService
{
    public function summary(Organization $organization): array
    {
        $organization->loadMissing(['subscription.plan', 'agentDevices.cameras', 'cameras', 'policies']);

        $onboarding = app(OrganizationOnboardingService::class)->summary($organization);
        $agentDevices = $organization->agentDevices;
        $cameras = $organization->cameras;
        $publishedRelease = AgentRelease::published()
            ->where('channel', 'stable')
            ->where('platform', 'windows-x64')
            ->latest('published_at')
            ->first();

        $onlineCameras = $cameras->where('stream_status', 'online')->count();
        $warningCameras = $cameras->where('stream_status', 'warning')->count();
        $activeOperationalAlerts = Alert::where('organization_id', $organization->id)
            ->where('source', 'operations')
            ->where('is_active', true)
            ->count();
        $activeAnalyticsAlerts = Alert::where('organization_id', $organization->id)
            ->where('source', 'analytics')
            ->where('is_active', true)
            ->count();
        $hasSummaries = DailySummary::where('organization_id', $organization->id)->exists();

        $checks = [
            [
                'key' => 'subscription',
                'label' => __('dashboard.readiness_subscription'),
                'ok' => $organization->isActive(),
                'detail' => $organization->isActive()
                    ? __('dashboard.readiness_subscription_ok')
                    : __('dashboard.readiness_subscription_missing'),
            ],
            [
                'key' => 'release',
                'label' => __('dashboard.readiness_release'),
                'ok' => (bool) $publishedRelease,
                'detail' => $publishedRelease
                    ? __('dashboard.readiness_release_ok', ['version' => $publishedRelease->version])
                    : __('dashboard.readiness_release_missing'),
            ],
            [
                'key' => 'onboarding',
                'label' => __('dashboard.readiness_onboarding'),
                'ok' => (bool) ($onboarding['is_complete'] ?? false),
                'detail' => __('dashboard.readiness_onboarding_detail', [
                    'done' => $onboarding['completed_steps'] ?? 0,
                    'total' => $onboarding['total_steps'] ?? 0,
                ]),
            ],
            [
                'key' => 'devices',
                'label' => __('dashboard.readiness_devices'),
                'ok' => $agentDevices->isNotEmpty() && $agentDevices->contains(fn ($device) => (bool) $device->last_seen_at),
                'detail' => __('dashboard.readiness_devices_detail', ['count' => $agentDevices->count()]),
            ],
            [
                'key' => 'cameras',
                'label' => __('dashboard.readiness_cameras'),
                'ok' => $cameras->isNotEmpty() && $onlineCameras > 0,
                'detail' => __('dashboard.readiness_cameras_detail', [
                    'total' => $cameras->count(),
                    'online' => $onlineCameras,
                    'warning' => $warningCameras,
                ]),
            ],
            [
                'key' => 'policies',
                'label' => __('dashboard.readiness_policies'),
                'ok' => $organization->policies->isNotEmpty(),
                'detail' => $organization->policies->isNotEmpty()
                    ? __('dashboard.readiness_policies_ok')
                    : __('dashboard.readiness_policies_missing'),
            ],
            [
                'key' => 'reports',
                'label' => __('dashboard.readiness_reports'),
                'ok' => $hasSummaries,
                'detail' => $hasSummaries
                    ? __('dashboard.readiness_reports_ok')
                    : __('dashboard.readiness_reports_missing'),
            ],
        ];

        $passed = collect($checks)->where('ok', true)->count();

        return [
            'checks' => $checks,
            'progress_percent' => (int) round(($passed / max(1, count($checks))) * 100),
            'passed_checks' => $passed,
            'total_checks' => count($checks),
            'counters' => [
                'agent_devices' => $agentDevices->count(),
                'cameras' => $cameras->count(),
                'online_cameras' => $onlineCameras,
                'operational_alerts' => $activeOperationalAlerts,
                'analytics_alerts' => $activeAnalyticsAlerts,
            ],
            'next_actions' => $this->nextActions($organization, $checks),
        ];
    }

    protected function nextActions(Organization $organization, array $checks): array
    {
        $actions = [];
        $failed = collect($checks)->where('ok', false)->pluck('key')->all();

        if (in_array('release', $failed, true)) {
            $actions[] = ['label' => __('dashboard.readiness_action_release'), 'route' => route('agent-releases.index')];
        }
        if (in_array('devices', $failed, true)) {
            $actions[] = ['label' => __('dashboard.readiness_action_device'), 'route' => route('agent-devices.index')];
        }
        if (in_array('cameras', $failed, true)) {
            $actions[] = ['label' => __('dashboard.readiness_action_camera'), 'route' => route('cameras.index')];
        }
        if (in_array('policies', $failed, true)) {
            $actions[] = ['label' => __('dashboard.readiness_action_policies'), 'route' => route('policies.index')];
        }
        if (in_array('reports', $failed, true)) {
            $actions[] = ['label' => __('dashboard.readiness_action_validation'), 'route' => route('reports.index')];
        }
        if (in_array('onboarding', $failed, true)) {
            $actions[] = ['label' => __('dashboard.readiness_action_onboarding'), 'route' => route('onboarding.index')];
        }

        return $actions;
    }
}
