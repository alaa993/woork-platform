@extends('layouts.app')
@section('title', __('dashboard.getting_started'))
@section('page', __('dashboard.getting_started'))
@section('content')
<div class="space-y-6">
  <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-gradient-to-br from-emerald-50 via-white to-teal-50 dark:from-emerald-950/20 dark:via-slate-950 dark:to-slate-950 p-6 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h2 class="text-2xl font-semibold tracking-tight">{{ __('dashboard.onboarding_title') }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">{{ __('dashboard.onboarding_subtitle') }}</p>
      </div>
      <div class="rounded-2xl border border-emerald-200/70 bg-white/80 px-4 py-3 text-center dark:border-white/10 dark:bg-white/5">
        <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.onboarding_completion') }}</div>
        <div class="mt-1 text-2xl font-semibold">{{ $onboarding['progress_percent'] }}%</div>
      </div>
    </div>

    <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-white/10">
      <div class="h-full rounded-full bg-emerald-500" style="width: {{ $onboarding['progress_percent'] }}%"></div>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.onboarding_steps') }}</h3>
      <div class="mt-4 space-y-3">
        @foreach($onboarding['steps'] as $index => $step)
          <div class="flex items-start justify-between gap-4 rounded-2xl border border-slate-200/70 p-4 dark:border-white/10">
            <div class="flex items-start gap-3">
              <div class="mt-0.5 flex h-7 w-7 items-center justify-center rounded-full {{ $step['completed'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300' }}">
                {{ $index + 1 }}
              </div>
              <div>
                <div class="font-medium">{{ $step['label'] }}</div>
                <div class="mt-1 text-xs {{ $step['completed'] ? 'text-emerald-600 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">
                  {{ $step['completed'] ? __('dashboard.onboarding_done') : __('dashboard.onboarding_pending') }}
                </div>
              </div>
            </div>
            @if(!$step['completed'])
              <a href="{{ $step['route'] }}" class="rounded-xl border px-3 py-2 text-sm dark:border-white/10">{{ $step['action'] }}</a>
            @endif
          </div>
        @endforeach
      </div>
    </div>

    <div class="space-y-6">
      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.onboarding_next_step') }}</h3>
        @if($onboarding['next_step'])
          <div class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ $onboarding['next_step']['label'] }}</div>
          <a href="{{ $onboarding['next_step']['route'] }}" class="mt-4 inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white">
            {{ $onboarding['next_step']['action'] }}
          </a>
        @else
          <div class="mt-3 text-sm text-emerald-600 dark:text-emerald-300">{{ __('dashboard.onboarding_complete') }}</div>
          <a href="{{ route('reports.index') }}" class="mt-4 inline-flex rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white">
            {{ __('dashboard.onboarding_action_open_reports') }}
          </a>
        @endif
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.onboarding_quick_links') }}</h3>
        <div class="mt-4 grid gap-3">
          <a href="{{ route('agent-devices.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.agent_devices') }}</a>
          <a href="{{ route('cameras.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.cameras') }}</a>
          <a href="{{ route('camera-health.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.camera_health') }}</a>
          <a href="{{ route('reports.index') }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.reports') }}</a>
          @if($organization->agentDevices->isNotEmpty())
            <a href="{{ route('agent-devices.validation', $organization->agentDevices->sortByDesc('id')->first()) }}" class="rounded-xl border px-4 py-3 text-sm dark:border-white/10">{{ __('dashboard.agent_validation_title') }}</a>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
