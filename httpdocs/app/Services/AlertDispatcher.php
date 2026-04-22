<?php

namespace App\Services;

use App\Models\Alert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AlertDispatcher
{
    /**
     * Dispatch alert payload across requested channels.
     *
     * @param  array  $payload
     * @param  array  $channels
     * @return void
     */
    public function dispatch(array $payload, array $channels = ['in_app']): void
    {
        foreach ($channels as $channel) {
            $handler = 'send'.Str::studly($channel);
            if (method_exists($this, $handler)) {
                $this->{$handler}($payload);
            } else {
                $this->sendInApp($payload);
            }
        }
    }

    protected function sendInApp(array $payload): void
    {
        Alert::updateOrCreate(
            [
                'organization_id' => $payload['organization_id'],
                'camera_id'       => $payload['camera_id'] ?? null,
                'agent_device_id' => $payload['agent_device_id'] ?? null,
                'employee_id'     => $payload['employee_id'],
                'room_id'         => $payload['room_id'],
                'kind'            => $payload['kind'],
                'channel'         => 'in_app',
                'source'          => $payload['source'] ?? 'analytics',
            ],
            [
                'level'     => $payload['level'],
                'message'   => $payload['message'],
                'rules'     => $payload['rules'],
                'is_active' => true,
                'resolved_at' => null,
            ]
        );
    }

    protected function sendEmail(array $payload): void
    {
        Log::info('[alert][email]', $payload);
    }

    protected function sendSlack(array $payload): void
    {
        Log::info('[alert][slack]', $payload);
    }
}
