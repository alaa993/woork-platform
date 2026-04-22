@extends('layouts.app')
@section('title', __('woork.create_camera'))
@section('page', __('woork.create_camera'))
@section('content')
<div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
  @include('partials.plan-usage', ['usage' => $usage ?? null])
  <div class="mb-5 grid gap-4 lg:grid-cols-[1.1fr_.9fr]">
    <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/80 p-4 dark:border-emerald-900/30 dark:bg-emerald-950/20">
      <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ __('dashboard.camera_setup_title') }}</div>
      <div class="mt-2 text-sm text-emerald-700/80 dark:text-emerald-200/80">{{ __('dashboard.camera_setup_intro') }}</div>
      <div class="mt-4 space-y-2 text-sm">
        <div>1. {{ __('dashboard.camera_setup_step_device') }}</div>
        <div>2. {{ __('dashboard.camera_setup_step_rtsp') }}</div>
        <div>3. {{ __('dashboard.camera_setup_step_mode') }}</div>
        <div>4. {{ __('dashboard.camera_setup_step_health') }}</div>
      </div>
      @if($agentDevices->isEmpty())
        <div class="mt-4 rounded-xl border border-amber-200/70 bg-amber-50/80 p-3 text-sm text-amber-800 dark:border-amber-900/30 dark:bg-amber-950/20 dark:text-amber-200">
          {{ __('dashboard.camera_setup_no_agent') }}
          <a href="{{ route('agent-devices.create') }}" class="underline">{{ __('dashboard.onboarding_action_create_device') }}</a>
        </div>
      @endif
      @if($rooms->isEmpty())
        <div class="mt-3 rounded-xl border border-amber-200/70 bg-amber-50/80 p-3 text-sm text-amber-800 dark:border-amber-900/30 dark:bg-amber-950/20 dark:text-amber-200">
          {{ __('dashboard.camera_setup_no_room') }}
          <a href="{{ route('rooms.create') }}" class="underline">{{ __('dashboard.rooms') }}</a>
        </div>
      @endif
    </div>

    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/[0.03]">
      <div class="text-sm font-semibold">{{ __('dashboard.camera_setup_modes') }}</div>
      <div class="mt-3 space-y-3">
        @foreach($analysisModes as $mode => $profile)
          <div class="rounded-xl border border-slate-200/70 p-3 dark:border-white/10">
            <div class="font-medium">{{ $profile['label'] }}</div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $profile['description'] }}</div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('cameras.store') }}" class="grid md:grid-cols-2 gap-4">@csrf

    <div>
      <label class="block text-sm mb-1">{{ __('woork.room') }}</label>
      <select name="room_id" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
        <option value="">{{ __('woork.select_room') }}</option>
        @foreach($rooms as $room)
          <option value="{{ $room->id }}" @selected(old('room_id', $rooms->first()->id ?? null) == $room->id)>{{ $room->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.agent_device') }}</label>
      <select name="agent_device_id" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
        <option value="">{{ __('woork.unassigned') }}</option>
        @foreach($agentDevices as $device)
          <option value="{{ $device->id }}" @selected(old('agent_device_id') == $device->id)>{{ $device->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.name') }}</label>
      <input name="name" value="{{ old('name', '') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.purpose') }}</label>
      <input name="purpose" value="{{ old('purpose', 'desk') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.analysis_mode') }}</label>
      <select name="analysis_mode" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
        @foreach($analysisModes as $mode => $profile)
          <option value="{{ $mode }}" @selected(old('analysis_mode', $defaultMode) === $mode)>{{ $profile['label'] }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.rtsp_url') }}</label>
      <input name="rtsp_url" value="{{ old('rtsp_url', '') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
      <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_setup_rtsp_hint') }}</div>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.status') }}</label>
      <input name="status" value="{{ old('status', 'pending') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.stream_status') }}</label>
      <input name="stream_status" value="{{ old('stream_status', 'pending') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('woork.health_message') }}</label>
      <input name="health_message" value="{{ old('health_message', '') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div class="md:col-span-2">
      <label class="block text-sm mb-1">{{ __('woork.roi') }}</label>
      <textarea name="roi" rows="4" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">{{ old('roi', $defaultRoiJson) }}</textarea>
      <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_setup_roi_hint') }}</div>
    </div>

    <div class="md:col-span-2">
      <label class="block text-sm mb-1">{{ __('woork.analysis_config') }}</label>
      <textarea name="analysis_config" rows="15" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">{{ old('analysis_config', $defaultAnalysisConfigJson) }}</textarea>
      <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_setup_analysis_hint') }}</div>
    </div>

    <div class="md:col-span-2">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', true))>
        <span>{{ __('woork.is_enabled') }}</span>
      </label>
    </div>

    <div class="md:col-span-2 flex gap-3">
      <button class="rounded-xl bg-emerald-600 text-white px-4 py-2">{{ __('woork.save') }}</button>
      <a href="{{ route('cameras.index') }}" class="px-4 py-2 rounded-xl border dark:border-white/10">{{ __('woork.cancel') }}</a>
    </div>
  </form>
</div>
@endsection
