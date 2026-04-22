@extends('layouts.app')
@section('title', __('dashboard.agent_validation_title'))
@section('page', __('dashboard.agent_validation_title'))
@section('content')
<div class="space-y-6">
  <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-gradient-to-br from-emerald-50 via-white to-teal-50 dark:from-emerald-950/20 dark:via-slate-950 dark:to-slate-950 p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h2 class="text-2xl font-semibold tracking-tight">{{ $agentDevice->name }}</h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('dashboard.agent_validation_intro') }}</p>
      </div>
      <div class="rounded-2xl border border-emerald-200/70 bg-white/80 px-4 py-3 text-center dark:border-white/10 dark:bg-white/5">
        <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.onboarding_completion') }}</div>
        <div class="mt-1 text-2xl font-semibold">{{ $validation['progress_percent'] }}%</div>
      </div>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.validation_checklist') }}</h3>
      <div class="mt-4 space-y-3">
        @foreach($validation['steps'] as $index => $step)
          <div class="flex items-start gap-3 rounded-2xl border border-slate-200/70 p-4 dark:border-white/10">
            <div class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full {{ $step['completed'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300' }}">
              {{ $index + 1 }}
            </div>
            <div class="min-w-0">
              <div class="font-medium">{{ $step['label'] }}</div>
              <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $step['detail'] }}</div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="space-y-6">
      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.validation_camera_summary') }}</h3>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <div class="rounded-xl border border-slate-200/70 p-4 dark:border-white/10">
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_total') }}</div>
            <div class="mt-1 text-xl font-semibold">{{ $validation['counters']['total'] }}</div>
          </div>
          <div class="rounded-xl border border-slate-200/70 p-4 dark:border-white/10">
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_online') }}</div>
            <div class="mt-1 text-xl font-semibold">{{ $validation['counters']['online'] }}</div>
          </div>
          <div class="rounded-xl border border-slate-200/70 p-4 dark:border-white/10">
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_warning') }}</div>
            <div class="mt-1 text-xl font-semibold">{{ $validation['counters']['warning'] }}</div>
          </div>
          <div class="rounded-xl border border-slate-200/70 p-4 dark:border-white/10">
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_offline') }}</div>
            <div class="mt-1 text-xl font-semibold">{{ $validation['counters']['offline'] }}</div>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.validation_commands') }}</h3>
        <div class="mt-4 space-y-4">
          @foreach($validation['commands'] as $label => $command)
            <div>
              <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.validation_command_'.$label) }}</div>
              <pre class="mt-2 overflow-auto rounded-xl bg-slate-950 p-3 text-xs text-emerald-300">{{ $command }}</pre>
            </div>
          @endforeach
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.validation_links') }}</h3>
        <div class="mt-4 grid gap-3">
          <a href="{{ route('agent-devices.install', $agentDevice) }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.agent_open_install_guide') }}</a>
          <a href="{{ route('camera-health.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.camera_health') }}</a>
          <a href="{{ route('reports.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.reports') }}</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
