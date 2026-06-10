<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::firstOrCreate(
            ['slug' => 'basic'],
            [
                'name' => 'Basic',
                'cameras_limit' => 3,
                'employees_limit' => 15,
                'price_monthly' => 29,
                'price_yearly' => 299,
                'trial_days' => 14,
                'features' => ['agent_devices_limit' => 2],
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'cameras_limit' => 10,
                'employees_limit' => 50,
                'price_monthly' => 79,
                'price_yearly' => 790,
                'trial_days' => 14,
                'features' => ['agent_devices_limit' => 5],
                'is_active' => true,
            ]
        );
    }
}
