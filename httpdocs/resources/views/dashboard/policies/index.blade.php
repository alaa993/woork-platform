@extends('layouts.app')
@section('title', __('dashboard.policies'))
@section('page', __('dashboard.policies'))
@section('content')
<div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
  <form method="POST" action="{{ route('policies.update') }}" class="grid md:grid-cols-2 gap-6">@csrf @method('PUT')
    <div>
      <label class="block text-sm mb-1">{{ __('woork.visibility') }}: Save video</label>
      <select name="save_video" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">
        <option value="0" @selected(old('save_video',$policy->save_video ?? 0)==0)>Disabled</option>
        <option value="1" @selected(old('save_video',$policy->save_video ?? 0)==1)>Enabled</option>
      </select>
    </div>
    <div>
      <label class="block text-sm mb-1">{{ __('woork.work_hours') }} (JSON)</label>
      <textarea name="work_hours" rows="8" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">{{ old('work_hours', json_encode($policy->work_hours ?? [], JSON_PRETTY_PRINT)) }}</textarea>
    </div>

    <div class="md:col-span-2 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] p-4">
      <div class="text-sm font-semibold">{{ __('dashboard.report_system_health') }}</div>
      <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
        Operational thresholds that control when camera and detector alerts are raised.
      </div>

      @php($thresholds = $policy->thresholds ?? [])
      <div class="mt-4 grid md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm mb-1">Camera offline after (minutes)</label>
          <input type="number" min="1" name="threshold_camera_offline_after_minutes" value="{{ old('threshold_camera_offline_after_minutes', $thresholds['camera_offline_after_minutes'] ?? 5) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">
        </div>
        <div>
          <label class="block text-sm mb-1">Camera warning after (minutes)</label>
          <input type="number" min="1" name="threshold_camera_warning_after_minutes" value="{{ old('threshold_camera_warning_after_minutes', $thresholds['camera_warning_after_minutes'] ?? 3) }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">
        </div>
        <div>
          <label class="block text-sm mb-1">Detector fallback alert</label>
          <select name="threshold_detector_fallback" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">
            <option value="0" @selected((int) old('threshold_detector_fallback', $thresholds['detector_fallback'] ?? 1) === 0)>Disabled</option>
            <option value="1" @selected((int) old('threshold_detector_fallback', $thresholds['detector_fallback'] ?? 1) === 1)>Enabled</option>
          </select>
        </div>
        <div>
          <label class="block text-sm mb-1">Phone detection unsupported (show alert)</label>
          <select name="threshold_phone_detection_unavailable" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">
            <option value="0" @selected((int) old('threshold_phone_detection_unavailable', $thresholds['phone_detection_unavailable'] ?? 1) === 0)>Disabled</option>
            <option value="1" @selected((int) old('threshold_phone_detection_unavailable', $thresholds['phone_detection_unavailable'] ?? 1) === 1)>Enabled</option>
          </select>
        </div>
      </div>
    </div>

    <div class="md:col-span-2">
      <label class="block text-sm mb-1">{{ __('woork.thresholds') }} (JSON)</label>
      <textarea name="thresholds" rows="8" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">{{ old('thresholds', json_encode($policy->thresholds ?? [], JSON_PRETTY_PRINT)) }}</textarea>
      <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
        Tip: You can use the fields above, or edit the raw thresholds JSON directly.
      </div>
    </div>

    <div class="md:col-span-2"><button class="rounded-xl bg-emerald-600 text-white px-4 py-2">Save</button></div>
  </form>
</div>
@endsection
