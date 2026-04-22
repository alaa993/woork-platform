<?php

namespace App\Services;

class CameraSetupPresetService
{
    public function modes(): array
    {
        return [
            'desk_monitoring' => [
                'label' => 'Desk monitoring',
                'description' => 'Track work, idle, away, and phone events for a fixed workstation.',
            ],
            'entrance_monitoring' => [
                'label' => 'Entrance monitoring',
                'description' => 'Track presence around entrances and front-desk traffic.',
            ],
            'cashier_monitoring' => [
                'label' => 'Cashier monitoring',
                'description' => 'Track cashier presence and activity around the station.',
            ],
            'warehouse_monitoring' => [
                'label' => 'Warehouse monitoring',
                'description' => 'Track movement and zone activity for back-of-house areas.',
            ],
        ];
    }

    public function defaultRoi(): array
    {
        return [
            'work_zone' => [],
        ];
    }

    public function defaultAnalysisConfig(string $mode = 'desk_monitoring'): array
    {
        return match ($mode) {
            'entrance_monitoring' => [
                'analyzer' => 'vision_people',
                'fallback_analyzer' => 'motion_presence',
                'detector' => 'auto',
                'detector_bundle' => null,
                'dnn_model_path' => null,
                'dnn_config_path' => null,
                'dnn_labels_path' => null,
                'assigned_employee_id' => null,
                'healthcheck_interval_seconds' => 10,
                'min_event_gap_seconds' => 45,
                'idle_after_seconds' => 240,
                'away_after_seconds' => 120,
                'motion_threshold' => 12,
                'min_motion_ratio' => 0.01,
                'tracking_max_distance' => 100,
                'tracking_max_missing_frames' => 6,
                'presence_event_type' => 'entry',
                'phone_event_type' => null,
                'idle_event_type' => 'idle',
                'away_event_type' => 'away',
            ],
            'cashier_monitoring' => [
                'analyzer' => 'vision_people',
                'fallback_analyzer' => 'motion_presence',
                'detector' => 'auto',
                'detector_bundle' => null,
                'dnn_model_path' => null,
                'dnn_config_path' => null,
                'dnn_labels_path' => null,
                'assigned_employee_id' => null,
                'healthcheck_interval_seconds' => 10,
                'min_event_gap_seconds' => 60,
                'idle_after_seconds' => 240,
                'away_after_seconds' => 150,
                'motion_threshold' => 12,
                'min_motion_ratio' => 0.01,
                'tracking_max_distance' => 90,
                'tracking_max_missing_frames' => 6,
                'presence_event_type' => 'cashier_present',
                'phone_event_type' => 'phone',
                'idle_event_type' => 'idle',
                'away_event_type' => 'station_unattended',
            ],
            'warehouse_monitoring' => [
                'analyzer' => 'vision_people',
                'fallback_analyzer' => 'motion_presence',
                'detector' => 'auto',
                'detector_bundle' => null,
                'dnn_model_path' => null,
                'dnn_config_path' => null,
                'dnn_labels_path' => null,
                'assigned_employee_id' => null,
                'healthcheck_interval_seconds' => 10,
                'min_event_gap_seconds' => 60,
                'idle_after_seconds' => 360,
                'away_after_seconds' => 240,
                'motion_threshold' => 12,
                'min_motion_ratio' => 0.01,
                'tracking_max_distance' => 110,
                'tracking_max_missing_frames' => 8,
                'presence_event_type' => 'zone_entry',
                'phone_event_type' => null,
                'idle_event_type' => 'idle',
                'away_event_type' => 'away',
            ],
            default => [
                'analyzer' => 'vision_people',
                'fallback_analyzer' => 'motion_presence',
                'detector' => 'auto',
                'detector_bundle' => null,
                'dnn_model_path' => null,
                'dnn_config_path' => null,
                'dnn_labels_path' => null,
                'assigned_employee_id' => null,
                'healthcheck_interval_seconds' => 10,
                'min_event_gap_seconds' => 60,
                'idle_after_seconds' => 300,
                'away_after_seconds' => 180,
                'motion_threshold' => 12,
                'min_motion_ratio' => 0.01,
                'tracking_max_distance' => 90,
                'tracking_max_missing_frames' => 6,
                'presence_event_type' => 'work_active',
                'phone_event_type' => 'phone',
                'idle_event_type' => 'idle',
                'away_event_type' => 'away',
            ],
        };
    }

    public function toPrettyJson(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
