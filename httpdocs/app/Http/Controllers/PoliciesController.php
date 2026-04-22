<?php

namespace App\Http\Controllers;

use App\Models\Policy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JsonException;

class PoliciesController extends Controller
{
    public function index()
    {
        $policy = Policy::firstOrCreate(['organization_id' => Auth::user()->organization_id]);

        return view('dashboard.policies.index', compact('policy'));
    }

    public function update(Request $request): RedirectResponse
    {
        $policy = Policy::firstOrCreate(['organization_id' => Auth::user()->organization_id]);

        $validated = $request->validate([
            'save_video' => ['nullable', 'boolean'],
            'work_hours' => ['nullable', 'string'],
            'breaks' => ['nullable', 'string'],
            'visibility' => ['nullable', 'string'],
            'thresholds' => ['nullable', 'string'],
            'threshold_camera_offline_after_minutes' => ['nullable', 'integer', 'min:1'],
            'threshold_camera_warning_after_minutes' => ['nullable', 'integer', 'min:1'],
            'threshold_detector_fallback' => ['nullable', 'boolean'],
            'threshold_phone_detection_unavailable' => ['nullable', 'boolean'],
        ]);

        try {
            $thresholds = $this->decodeJsonField($validated['thresholds'] ?? null);
            $thresholds = array_merge($thresholds, [
                'camera_offline_after_minutes' => (int) ($validated['threshold_camera_offline_after_minutes'] ?? ($thresholds['camera_offline_after_minutes'] ?? 5)),
                'camera_warning_after_minutes' => (int) ($validated['threshold_camera_warning_after_minutes'] ?? ($thresholds['camera_warning_after_minutes'] ?? 3)),
                'detector_fallback' => (int) ($validated['threshold_detector_fallback'] ?? ($thresholds['detector_fallback'] ?? 1)),
                'phone_detection_unavailable' => (int) ($validated['threshold_phone_detection_unavailable'] ?? ($thresholds['phone_detection_unavailable'] ?? 1)),
            ]);

            $policy->update([
                'save_video' => $request->boolean('save_video', false),
                'work_hours' => $this->decodeJsonField($validated['work_hours'] ?? null),
                'breaks' => $this->decodeJsonField($validated['breaks'] ?? null),
                'visibility' => $this->decodeJsonField($validated['visibility'] ?? null),
                'thresholds' => $thresholds,
            ]);
        } catch (JsonException $exception) {
            return back()
                ->withInput()
                ->with('error', 'Invalid JSON in one of the policy fields.');
        }

        return back()->with('ok', 'Policies saved');
    }

    /**
     * @return array<mixed>|null
     *
     * @throws JsonException
     */
    protected function decodeJsonField(?string $value): ?array
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : null;
    }
}
