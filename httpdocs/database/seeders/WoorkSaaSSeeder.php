<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{AgentDevice, AgentRelease, Plan, Organization, Subscription, User, Room, Camera, Employee, Event};
use Carbon\Carbon;

class WoorkSaaSSeeder extends Seeder
{
    public function run(): void
    {
        $installerPath = public_path('downloads/WoorkAgentSetup-1.0.0.exe');
        $fallbackZipPath = public_path('downloads/woork-agent-windows-x64.zip');
        $agentArtifactPath = file_exists($installerPath)
            ? 'downloads/WoorkAgentSetup-1.0.0.exe'
            : 'downloads/woork-agent-windows-x64.zip';
        $agentArtifactAbsolutePath = public_path($agentArtifactPath);

        $basic = Plan::firstOrCreate(
            ['slug' => 'basic'],
            [
                'name'            => 'Basic',
                'cameras_limit'   => 3,
                'employees_limit' => 15,
                'price_monthly'   => 29,
                'price_yearly'    => 299,
                'trial_days'      => 14,
                'features'        => ['agent_devices_limit' => 2],
            ]
        );

        AgentRelease::firstOrCreate(
            ['version' => '1.0.0'],
            [
                'channel' => 'stable',
                'platform' => 'windows-x64',
                'artifact_path' => $agentArtifactPath,
                'artifact_name' => basename($agentArtifactPath),
                'checksum_sha256' => file_exists($agentArtifactAbsolutePath) ? hash_file('sha256', $agentArtifactAbsolutePath) : null,
                'artifact_size' => file_exists($agentArtifactAbsolutePath) ? filesize($agentArtifactAbsolutePath) : null,
                'notes' => file_exists($installerPath)
                    ? "Windows installer\nControl app included\nRuns as Windows service\nCamera diagnostics enabled"
                    : "Developer ZIP fallback\nBuild WoorkAgentSetup-1.0.0.exe before customer distribution",
                'is_active' => true,
                'published_at' => Carbon::now(),
            ]
        );

        $org = Organization::firstOrCreate(
            ['email' => 'demo@org.test'],
            ['name' => 'Demo Org', 'language' => 'ar', 'plan_id' => $basic->id, 'company_type' => 'company']
        );

       // Demo Admin
$admin = User::firstOrCreate(
    ['phone' => '+10000000000'],
    [
        'organization_id' => $org->id,
        'name'            => 'Demo Admin',
        'email'           => 'admin@woork.site',
        'role'            => 'company_admin',
        'password'        => bcrypt('password123'), // ← أضف هذا السطر
    ]
);


        if (!$org->owner_user_id) {
            $org->owner_user_id = $admin->id;
            $org->save();
        }

        Subscription::firstOrCreate(
            ['organization_id' => $org->id, 'plan_id' => $basic->id],
            ['status' => 'trial', 'trial_ends_at' => Carbon::now()->addDays(14)]
        );

        $room = Room::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'Main Office']
        );

        $agent = AgentDevice::firstOrCreate(
            ['device_uuid' => 'demo-agent-001'],
            [
                'organization_id' => $org->id,
                'name' => 'Demo Edge Agent',
                'pairing_token' => 'PAIR-DEMO-001',
                'status' => 'online',
                'version' => '1.0.0',
                'os' => 'windows',
                'last_seen_at' => Carbon::now(),
            ]
        );

        $cam = Camera::firstOrCreate(
            ['organization_id' => $org->id, 'room_id' => $room->id, 'name' => 'Cam A'],
            [
                'agent_device_id' => $agent->id,
                'purpose' => 'desk',
                'analysis_mode' => 'desk_monitoring',
                'status' => 'ok',
                'stream_status' => 'online',
                'last_seen_at' => Carbon::now(),
                'last_frame_at' => Carbon::now(),
                'analysis_config' => [
                    'analyzer' => 'vision_people',
                    'fallback_analyzer' => 'motion_presence',
                    'detector' => 'auto',
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
            ]
        );

        $alice = Employee::firstOrCreate(
            ['organization_id' => $org->id, 'room_id' => $room->id, 'name' => 'Alice'],
            ['title' => 'Analyst']
        );
        $bob = Employee::firstOrCreate(
            ['organization_id' => $org->id, 'room_id' => $room->id, 'name' => 'Bob'],
            ['title' => 'Designer']
        );

        $cam->analysis_config = array_merge($cam->analysis_config ?? [], ['assigned_employee_id' => $alice->id]);
        $cam->save();

        $start = Carbon::today()->setHour(9);
        foreach ([$alice, $bob] as $e) {
            Event::firstOrCreate([
                'organization_id' => $org->id,
                'camera_id'       => $cam->id,
                'agent_device_id' => $agent->id,
                'employee_id'     => $e->id,
                'room_id'         => $room->id,
                'type'            => 'work_active',
                'started_at'      => $start,
                'ended_at'        => $start->copy()->addMinutes(60),
                'duration_seconds'=> 3600,
            ]);
            Event::firstOrCreate([
                'organization_id' => $org->id,
                'camera_id'       => $cam->id,
                'agent_device_id' => $agent->id,
                'employee_id'     => $e->id,
                'room_id'         => $room->id,
                'type'            => 'phone',
                'started_at'      => $start->copy()->addMinutes(60),
                'ended_at'        => $start->copy()->addMinutes(75),
                'duration_seconds'=> 900,
            ]);
            Event::firstOrCreate([
                'organization_id' => $org->id,
                'camera_id'       => $cam->id,
                'agent_device_id' => $agent->id,
                'employee_id'     => $e->id,
                'room_id'         => $room->id,
                'type'            => 'away',
                'started_at'      => $start->copy()->addMinutes(120),
                'ended_at'        => $start->copy()->addMinutes(140),
                'duration_seconds'=> 1200,
            ]);
        }

// Super Admin
User::firstOrCreate(
    ['phone' => '+10000000001'],
    [
        'name'      => 'Super Admin',
        'email'     => 'super@woork.site',
        'role'      => 'super_admin',
        'password'  => bcrypt('password123'), // ← أضف هذا السطر أيضًا
    ]
);
    }
}
