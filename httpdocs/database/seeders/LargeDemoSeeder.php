<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{AgentDevice, Plan, Organization, Subscription, User, Room, Camera, Employee};

class LargeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('en_US');

        // حجم البيانات (يمكن تعديله بسهولة)
        $orgCount        = 2;
        $roomsPerOrg     = 6;
        $camerasPerRoom  = 2;
        $employeesPerOrg = 120;
        $summaryDays     = 90;  // عدد أيام الملخصات اليومية
        $eventsDays      = 14;  // عدد أيام الأحداث التفصيلية

        $now = Carbon::now();

        $plan = Plan::firstOrCreate(
            ['slug' => 'analytics'],
            [
                'name'            => 'Analytics',
                'cameras_limit'   => 100,
                'employees_limit' => 2000,
                'price_monthly'   => 99,
                'price_yearly'    => 999,
                'trial_days'      => 14,
                'features'        => ['demo' => true, 'analytics' => true, 'agent_devices_limit' => 12],
            ]
        );

        for ($i = 1; $i <= $orgCount; $i++) {
            $orgEmail = "analytics{$i}@org.test";

            $org = Organization::firstOrCreate(
                ['email' => $orgEmail],
                [
                    'name'         => "Analytics Lab {$i}",
                    'company_type' => 'company',
                    'language'     => 'ar',
                    'plan_id'      => $plan->id,
                    'country'      => 'US',
                ]
            );

            // إذا كانت المنظمة موجودة مسبقاً، نظّف بياناتها التشغيلية لإعادة توليدها
            if (! $org->wasRecentlyCreated) {
                DB::table('events')->where('organization_id', $org->id)->delete();
                DB::table('daily_summaries')->where('organization_id', $org->id)->delete();
                DB::table('employees')->where('organization_id', $org->id)->delete();
                DB::table('cameras')->where('organization_id', $org->id)->delete();
                DB::table('rooms')->where('organization_id', $org->id)->delete();
            }

            $adminEmail = "admin{$i}@woork.test";
            $admin = User::firstOrCreate(
                ['email' => $adminEmail],
                [
                    'organization_id' => $org->id,
                    'name'            => "Analytics Admin {$i}",
                    'role'            => 'company_admin',
                    'password'        => bcrypt('password123'),
                ]
            );

            // نخزن الهاتف بصيغة أرقام فقط ليطابق normalizePhone
            $phoneBase = 19990000000 + $i;
            $phone = (string) $phoneBase;
            while (User::where('phone', $phone)->where('id', '!=', $admin->id)->exists()) {
                $phoneBase++;
                $phone = (string) $phoneBase;
            }

            $admin->fill([
                'organization_id' => $org->id,
                'name'            => "Analytics Admin {$i}",
                'phone'           => $phone,
                'role'            => 'company_admin',
                'password'        => bcrypt('password123'),
            ])->save();

            if (! $org->owner_user_id) {
                $org->owner_user_id = $admin->id;
                $org->save();
            }

            Subscription::firstOrCreate(
                ['organization_id' => $org->id, 'plan_id' => $plan->id],
                ['status' => 'trial', 'trial_ends_at' => $now->copy()->addDays(14)]
            );

            $agent = AgentDevice::firstOrCreate(
                ['device_uuid' => "analytics-agent-{$i}"],
                [
                    'organization_id' => $org->id,
                    'name' => "Analytics Edge {$i}",
                    'pairing_token' => "PAIR-ANALYTICS-{$i}",
                    'status' => 'online',
                    'version' => '1.0.0',
                    'os' => 'windows',
                    'last_seen_at' => $now,
                ]
            );

            // Rooms
            $roomRows = [];
            for ($r = 1; $r <= $roomsPerOrg; $r++) {
                $roomRows[] = [
                    'organization_id' => $org->id,
                    'name'            => "Room {$r}",
                    'location'        => $faker->city,
                    'notes'           => $faker->sentence(6),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            DB::table('rooms')->insert($roomRows);
            $roomIds = Room::where('organization_id', $org->id)->pluck('id')->all();

            // Cameras
            $cameraRows = [];
            foreach ($roomIds as $roomId) {
                for ($c = 1; $c <= $camerasPerRoom; $c++) {
                    $cameraRows[] = [
                        'organization_id' => $org->id,
                        'agent_device_id' => $agent->id,
                        'room_id'         => $roomId,
                        'name'            => "Cam {$c}",
                        'purpose'         => 'desk',
                        'analysis_mode'   => 'desk_monitoring',
                        'status'          => $faker->randomElement(['ok', 'ok', 'ok', 'offline', 'warning']),
                        'stream_status'   => $faker->randomElement(['online', 'online', 'offline', 'warning']),
                        'is_enabled'      => true,
                        'analysis_config' => json_encode([
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
                        ]),
                        'last_seen_at'    => $now,
                        'last_frame_at'   => $now,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }
            DB::table('cameras')->insert($cameraRows);

            // Employees
            $employeeRows = [];
            for ($e = 1; $e <= $employeesPerOrg; $e++) {
                $employeeRows[] = [
                    'organization_id' => $org->id,
                    'room_id'         => $roomIds[array_rand($roomIds)],
                    'name'            => $faker->name,
                    'title'           => $faker->jobTitle,
                    'photos'          => null,
                    'is_active'       => $faker->boolean(92),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            DB::table('employees')->insert($employeeRows);

            $employeeMap = Employee::where('organization_id', $org->id)
                ->get(['id', 'room_id'])
                ->mapWithKeys(fn ($row) => [$row->id => $row->room_id])
                ->all();

            $cameraByRoom = Camera::where('organization_id', $org->id)
                ->get(['id', 'room_id'])
                ->groupBy('room_id')
                ->map(fn ($rows) => $rows->pluck('id')->all())
                ->all();

            // Daily summaries (آخر $summaryDays يوم)
            $summaryRows = [];
            for ($d = 0; $d < $summaryDays; $d++) {
                $date = $now->copy()->subDays($d)->toDateString();
                foreach ($employeeMap as $empId => $roomId) {
                    $work = rand(240, 420);
                    $idle = rand(10, 90);
                    $phone = rand(5, 60);
                    $away = rand(5, 60);
                    $total = $work + $idle + $phone + $away;
                    $target = 8 * 60;

                    if ($total > $target) {
                        $work = max(0, $work - ($total - $target));
                    } elseif ($total < $target) {
                        $work += ($target - $total);
                    }

                    $total = max(1, $work + $idle + $phone + $away);
                    $score = (int) max(0, min(100, round(
                        ($work / $total) * 100
                        - ($phone * 0.5)
                        - ($away * 0.3)
                    )));

                    $summaryRows[] = [
                        'organization_id' => $org->id,
                        'employee_id'     => $empId,
                        'room_id'         => $roomId,
                        'date'            => $date,
                        'work_minutes'    => $work,
                        'idle_minutes'    => $idle,
                        'phone_minutes'   => $phone,
                        'away_minutes'    => $away,
                        'phone_count'     => rand(0, 6),
                        'away_count'      => rand(0, 4),
                        'score'           => $score,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];

                    if (count($summaryRows) >= 500) {
                        DB::table('daily_summaries')->upsert(
                            $summaryRows,
                            ['organization_id', 'employee_id', 'date'],
                            [
                                'room_id', 'work_minutes', 'idle_minutes', 'phone_minutes',
                                'away_minutes', 'phone_count', 'away_count', 'score', 'updated_at'
                            ]
                        );
                        $summaryRows = [];
                    }
                }
            }
            if ($summaryRows) {
                DB::table('daily_summaries')->upsert(
                    $summaryRows,
                    ['organization_id', 'employee_id', 'date'],
                    [
                        'room_id', 'work_minutes', 'idle_minutes', 'phone_minutes',
                        'away_minutes', 'phone_count', 'away_count', 'score', 'updated_at'
                    ]
                );
            }

            // Events (آخر $eventsDays يوم)
            $eventRows = [];
            $eventTypes = [
                ['type' => 'work_active', 'min' => 60, 'max' => 180],
                ['type' => 'phone', 'min' => 5, 'max' => 25],
                ['type' => 'away', 'min' => 5, 'max' => 30],
                ['type' => 'idle', 'min' => 5, 'max' => 45],
                ['type' => 'work_active', 'min' => 60, 'max' => 180],
            ];

            for ($d = 0; $d < $eventsDays; $d++) {
                $day = $now->copy()->subDays($d)->startOfDay()->addHours(9);
                foreach ($employeeMap as $empId => $roomId) {
                    $cursor = $day->copy();
                    foreach ($eventTypes as $et) {
                        $minutes = rand($et['min'], $et['max']);
                        $started = $cursor->copy();
                        $ended = $cursor->copy()->addMinutes($minutes);
                        $eventRows[] = [
                            'organization_id'  => $org->id,
                            'camera_id'        => $cameraByRoom[$roomId][array_rand($cameraByRoom[$roomId])],
                            'agent_device_id'  => $agent->id,
                            'employee_id'      => $empId,
                            'room_id'          => $roomId,
                            'type'             => $et['type'],
                            'started_at'       => $started,
                            'ended_at'         => $ended,
                            'duration_seconds' => $minutes * 60,
                            'created_at'       => $now,
                            'updated_at'       => $now,
                        ];
                        $cursor = $ended;

                        if (count($eventRows) >= 1000) {
                            DB::table('events')->insert($eventRows);
                            $eventRows = [];
                        }
                    }
                }
            }
            if ($eventRows) {
                DB::table('events')->insert($eventRows);
            }
        }
    }
}
