@extends('layouts.app')
@section('title', __('dashboard.launch_readiness'))
@section('page', __('dashboard.launch_readiness'))
@section('content')
<div class="space-y-6">
  <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-gradient-to-br from-emerald-50 via-white to-teal-50 dark:from-emerald-950/20 dark:via-slate-950 dark:to-slate-950 p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h2 class="text-2xl font-semibold tracking-tight">{{ __('dashboard.launch_readiness') }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">{{ __('dashboard.readiness_intro') }}</p>
      </div>
      <div class="rounded-2xl border border-emerald-200/70 bg-white/80 px-4 py-3 text-center dark:border-white/10 dark:bg-white/5">
        <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.onboarding_completion') }}</div>
        <div class="mt-1 text-2xl font-semibold">{{ $readiness['progress_percent'] }}%</div>
      </div>
    </div>
  </div>

  <div class="grid gap-4 md:grid-cols-5">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_devices') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $readiness['counters']['agent_devices'] }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_total') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $readiness['counters']['cameras'] }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_online') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $readiness['counters']['online_cameras'] }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.report_operational_alerts') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $readiness['counters']['operational_alerts'] }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.alerts') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $readiness['counters']['analytics_alerts'] }}</div>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.readiness_checks') }}</h3>
      <div class="mt-4 space-y-3">
        @foreach($readiness['checks'] as $check)
          <div class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200/70 p-4 dark:border-white/10">
            <div>
              <div class="font-medium">{{ $check['label'] }}</div>
              <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $check['detail'] }}</div>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs {{ $check['ok'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
              {{ $check['ok'] ? __('dashboard.onboarding_done') : __('dashboard.onboarding_pending') }}
            </span>
          </div>
        @endforeach
      </div>
    </div>

    <div class="space-y-6">
      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.readiness_next_actions') }}</h3>
        <div class="mt-4 grid gap-3">
          @forelse($readiness['next_actions'] as $action)
            <a href="{{ $action['route'] }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ $action['label'] }}</a>
          @empty
            <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/80 p-4 text-sm text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-950/20 dark:text-emerald-300">
              {{ __('dashboard.readiness_all_clear') }}
            </div>
          @endforelse
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.readiness_rollout_order') }}</h3>
        <div class="mt-4 space-y-3 text-sm">
          @foreach([
            __('dashboard.readiness_rollout_step_1'),
            __('dashboard.readiness_rollout_step_2'),
            __('dashboard.readiness_rollout_step_3'),
            __('dashboard.readiness_rollout_step_4'),
            __('dashboard.readiness_rollout_step_5'),
          ] as $index => $step)
            <div class="flex items-start gap-3 rounded-xl border border-slate-200/70 p-3 dark:border-white/10">
              <div class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $index + 1 }}</div>
              <div>{{ $step }}</div>
            </div>
          @endforeach
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.readiness_related') }}</h3>
        <div class="mt-4 grid gap-3">
          <a href="{{ route('onboarding.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.getting_started') }}</a>
          <a href="{{ route('agent-devices.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.agent_devices') }}</a>
          <a href="{{ route('camera-health.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.camera_health') }}</a>
          <a href="{{ route('reports.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.reports') }}</a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
