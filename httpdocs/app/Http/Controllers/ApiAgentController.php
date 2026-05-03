<?php

namespace App\Http\Controllers;

use App\Models\AgentDevice;
use App\Models\AgentHeartbeat;
use App\Models\Camera;
use App\Models\CameraHeartbeat;
use App\Models\Employee;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApiAgentController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pairing_token' => 'required|string',
            'device_uuid' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'version' => 'nullable|string|max:50',
            'os' => 'nullable|string|max:100',
            'capabilities' => 'nullable|array',
        ]);

        $device = AgentDevice::where('pairing_token', $data['pairing_token'])
            ->where('is_active', true)
            ->first();

        abort_unless($device, 401, 'Invalid pairing token');
        $this->assertOrganizationActive($device->organization);

        $existingDevice = AgentDevice::where('device_uuid', $data['device_uuid'])
            ->whereKeyNot($device->id)
            ->first();

        if ($existingDevice) {
            if ($existingDevice->organization_id !== $device->organization_id) {
                return response()->json([
                    'ok' => false,
                    'error' => 'This device UUID is already paired to another organization.',
                    'existing_agent_device_id' => $existingDevice->id,
                ], 409);
            }

            $plainToken = Str::random(60);

            DB::transaction(function () use ($device, $existingDevice, $data, $request, $plainToken) {
                Camera::where('agent_device_id', $device->id)->update([
                    'agent_device_id' => $existingDevice->id,
                ]);

                $existingDevice->fill([
                    'name' => $data['name'],
                    'device_uuid' => $data['device_uuid'],
                    'pairing_token' => $device->pairing_token,
                    'version' => $data['version'] ?? $existingDevice->version,
                    'os' => $data['os'] ?? $existingDevice->os,
                    'capabilities' => $data['capabilities'] ?? $existingDevice->capabilities,
                    'api_token_hash' => hash('sha256', $plainToken),
                    'status' => 'online',
                    'last_ip' => $request->ip(),
                    'last_seen_at' => now(),
                    'is_active' => true,
                ])->save();

                $device->update([
                    'device_uuid' => 'replaced-'.Str::uuid(),
                    'pairing_token' => 'replaced-'.Str::uuid(),
                    'api_token_hash' => null,
                    'status' => 'replaced',
                    'is_active' => false,
                ]);
            });

            return response()->json([
                'ok' => true,
                'token' => $plainToken,
                'organization_id' => $existingDevice->organization_id,
                'agent_device_id' => $existingDevice->id,
                'reused_existing_device' => true,
            ]);
        }

        if ($device->device_uuid !== $data['device_uuid']) {
            $device->device_uuid = $data['device_uuid'];
        }

        $plainToken = Str::random(60);

        $device->fill([
            'name' => $data['name'],
            'version' => $data['version'] ?? $device->version,
            'os' => $data['os'] ?? $device->os,
            'capabilities' => $data['capabilities'] ?? $device->capabilities,
            'api_token_hash' => hash('sha256', $plainToken),
            'status' => 'online',
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
        ])->save();

        return response()->json([
            'ok' => true,
            'token' => $plainToken,
            'organization_id' => $device->organization_id,
            'agent_device_id' => $device->id,
        ]);
    }

    public function config(Request $request): JsonResponse
    {
        $device = $this->requireAgentDevice($request);

        $cameras = Camera::where('organization_id', $device->organization_id)
            ->where('is_enabled', true)
            ->where(function ($query) use ($device) {
                $query->whereNull('agent_device_id')
                    ->orWhere('agent_device_id', $device->id);
            })
            ->with('room:id,name')
            ->orderBy('id')
            ->get([
                'id',
                'organization_id',
                'agent_device_id',
                'room_id',
                'name',
                'purpose',
                'analysis_mode',
                'rtsp_url',
                'roi',
                'analysis_config',
                'status',
                'stream_status',
                'health_message',
                'last_seen_at',
                'last_frame_at',
            ]);

        return response()->json([
            'ok' => true,
            'agent' => [
                'id' => $device->id,
                'organization_id' => $device->organization_id,
                'status' => $device->status,
            ],
            'cameras' => $cameras,
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $device = $this->requireAgentDevice($request);

        $data = $request->validate([
            'status' => 'nullable|string|max:50',
            'version' => 'nullable|string|max:50',
            'os' => 'nullable|string|max:100',
            'capabilities' => 'nullable|array',
            'cameras' => 'nullable|array',
            'cameras.*.id' => 'required|integer',
            'cameras.*.stream_status' => 'nullable|string|max:100',
            'cameras.*.analyzer' => 'nullable|string|max:100',
            'cameras.*.health_message' => 'nullable|string',
            'cameras.*.last_frame_at' => 'nullable|date',
            'cameras.*.fps' => 'nullable|numeric|min:0',
            'cameras.*.observations' => 'nullable|array',
        ]);

        $device->fill([
            'status' => $data['status'] ?? 'online',
            'version' => $data['version'] ?? $device->version,
            'os' => $data['os'] ?? $device->os,
            'capabilities' => $data['capabilities'] ?? $device->capabilities,
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
        ])->save();

        AgentHeartbeat::create([
            'agent_device_id' => $device->id,
            'status' => $device->status,
            'capabilities' => $device->capabilities,
            'checked_at' => now(),
            'ip_address' => $request->ip(),
        ]);

        foreach ($data['cameras'] ?? [] as $cameraStatus) {
            $lastFrameAt = isset($cameraStatus['last_frame_at'])
                ? Carbon::parse($cameraStatus['last_frame_at'])
                : now();

            Camera::where('organization_id', $device->organization_id)
                ->where('id', $cameraStatus['id'])
                ->where(function ($query) use ($device) {
                    $query->whereNull('agent_device_id')
                        ->orWhere('agent_device_id', $device->id);
                })
                ->update([
                    'agent_device_id' => $device->id,
                    'stream_status' => $cameraStatus['stream_status'] ?? 'online',
                    'health_message' => $cameraStatus['health_message'] ?? null,
                    'last_seen_at' => now(),
                    'last_frame_at' => $lastFrameAt,
                ]);

            CameraHeartbeat::create([
                'agent_device_id' => $device->id,
                'camera_id' => $cameraStatus['id'],
                'stream_status' => $cameraStatus['stream_status'] ?? 'online',
                'analyzer' => $cameraStatus['analyzer'] ?? null,
                'health_message' => $cameraStatus['health_message'] ?? null,
                'last_frame_at' => $lastFrameAt,
                'fps' => $cameraStatus['fps'] ?? null,
                'observations' => $cameraStatus['observations'] ?? null,
                'checked_at' => now(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function ingest(Request $request): JsonResponse
    {
        [$device, $organizationId] = $this->resolveContext($request);

        $data = $request->validate([
            'organization_id' => 'nullable|integer',
            'camera_id' => 'required|integer',
            'employee_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'events' => 'required|array|min:1',
            'events.*.type' => 'required|string|max:100',
            'events.*.employee_id' => 'nullable|integer',
            'events.*.room_id' => 'nullable|integer',
            'events.*.track_id' => 'nullable|string|max:255',
            'events.*.confidence' => 'nullable|numeric|min:0|max:100',
            'events.*.started_at' => 'required|date',
            'events.*.ended_at' => 'required|date',
            'events.*.meta' => 'nullable|array',
        ]);

        $organizationId = $organizationId ?? (int) $data['organization_id'];

        $camera = Camera::where('organization_id', $organizationId)
            ->whereKey($data['camera_id'])
            ->firstOrFail();

        if ($device && $camera->agent_device_id && $camera->agent_device_id !== $device->id) {
            abort(403, 'Camera is assigned to a different agent');
        }

        if ($device && ! $camera->agent_device_id) {
            $camera->agent_device_id = $device->id;
        }

        $camera->fill([
            'stream_status' => 'online',
            'last_seen_at' => now(),
            'last_frame_at' => now(),
        ])->save();

        $saved = 0;

        foreach ($data['events'] as $eventData) {
            $employeeId = (int) ($eventData['employee_id'] ?? $data['employee_id'] ?? 0);
            $roomId = (int) ($eventData['room_id'] ?? $data['room_id'] ?? $camera->room_id ?? 0);

            abort_unless($employeeId > 0, 422, 'employee_id is required');
            abort_unless($roomId > 0, 422, 'room_id is required');

            Employee::where('organization_id', $organizationId)->findOrFail($employeeId);
            Room::where('organization_id', $organizationId)->findOrFail($roomId);

            $startedAt = Carbon::parse($eventData['started_at']);
            $endedAt = Carbon::parse($eventData['ended_at']);

            Event::create([
                'organization_id' => $organizationId,
                'camera_id' => $camera->id,
                'agent_device_id' => $device?->id,
                'employee_id' => $employeeId,
                'room_id' => $roomId,
                'type' => $eventData['type'],
                'track_id' => $eventData['track_id'] ?? null,
                'confidence' => $eventData['confidence'] ?? null,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_seconds' => max(0, $endedAt->diffInSeconds($startedAt)),
                'meta' => $eventData['meta'] ?? null,
            ]);

            $saved++;
        }

        return response()->json(['ok' => true, 'saved' => $saved]);
    }

    protected function resolveContext(Request $request): array
    {
        $legacyHeader = 'Bearer '.config('woork.results_token');
        $token = $request->bearerToken();

        if ($token && ('Bearer '.$token) === $legacyHeader) {
            return [null, null];
        }

        $device = $this->requireAgentDevice($request);

        return [$device, $device->organization_id];
    }

    protected function requireAgentDevice(Request $request): AgentDevice
    {
        $token = $request->bearerToken();
        abort_unless($token, 401, 'Missing bearer token');

        $hash = hash('sha256', $token);

        $device = AgentDevice::where('api_token_hash', $hash)
            ->where('is_active', true)
            ->first();

        abort_unless($device, 401, 'Unauthorized agent');
        $this->assertOrganizationActive($device->organization);

        return $device;
    }

    protected function assertOrganizationActive(?Organization $organization): void
    {
        abort_unless($organization && $organization->isActive(), 403, 'Organization subscription is inactive');
    }
}
