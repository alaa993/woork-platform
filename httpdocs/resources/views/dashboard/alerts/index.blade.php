@extends('layouts.app')
@section('title', __('dashboard.alerts'))
@section('page', __('dashboard.alerts'))
@section('content')
<div class="space-y-6">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
    <form method="GET" action="{{ route('alerts.index') }}" class="grid gap-4 md:grid-cols-4 xl:grid-cols-5">
      <div>
        <label class="block text-sm mb-1">{{ __('dashboard.alert_filter_source') }}</label>
        <select name="source" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">
          <option value="">{{ __('dashboard.alert_filter_all') }}</option>
          <option value="analytics" @selected(($filters['source'] ?? '') === 'analytics')>analytics</option>
          <option value="operations" @selected(($filters['source'] ?? '') === 'operations')>operations</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">{{ __('dashboard.alert_filter_level') }}</label>
        <select name="level" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">
          <option value="">{{ __('dashboard.alert_filter_all') }}</option>
          <option value="info" @selected(($filters['level'] ?? '') === 'info')>info</option>
          <option value="warning" @selected(($filters['level'] ?? '') === 'warning')>warning</option>
          <option value="critical" @selected(($filters['level'] ?? '') === 'critical')>critical</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">{{ __('dashboard.alert_filter_state') }}</label>
        <select name="state" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">
          <option value="">{{ __('dashboard.alert_filter_all') }}</option>
          <option value="active" @selected(($filters['state'] ?? '') === 'active')>{{ __('dashboard.alert_state_active') }}</option>
          <option value="resolved" @selected(($filters['state'] ?? '') === 'resolved')>{{ __('dashboard.alert_state_resolved') }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm mb-1">{{ __('dashboard.alert_filter_kind') }}</label>
        <select name="kind" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:border-slate-700">
          <option value="">{{ __('dashboard.alert_filter_all') }}</option>
          @foreach($kindOptions as $kind)
            <option value="{{ $kind }}" @selected(($filters['kind'] ?? '') === $kind)>{{ $kind }}</option>
          @endforeach
        </select>
      </div>
      <div class="flex items-end gap-3">
        <button class="rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white">{{ __('dashboard.alert_apply_filters') }}</button>
        <a href="{{ route('alerts.index') }}" class="rounded-xl border px-4 py-2 text-sm dark:border-white/10">{{ __('dashboard.alert_reset_filters') }}</a>
      </div>
    </form>
  </div>

  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
    <ul class="divide-y divide-slate-200/70 dark:divide-white/10">
      @forelse($alerts as $item)
        <li class="py-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="min-w-0">
            <div class="font-medium">{{ $item->message }}</div>
            <div class="mt-2 flex flex-wrap gap-2 text-xs">
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $item->kind }}</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $item->source }}</span>
              <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $item->channel }}</span>
              @if(!$item->is_active)
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ __('dashboard.alert_state_resolved') }}</span>
              @endif
            </div>
            <div class="mt-3 text-xs text-slate-500 dark:text-slate-400">
              @if($item->camera)
                {{ __('woork.cameras') }}: {{ $item->camera->name }}
              @endif
              @if($item->agentDevice)
                · {{ __('dashboard.agent_devices_title') }}: {{ $item->agentDevice->name }}
              @endif
              @if($item->employee)
                · {{ __('woork.employee') }}: {{ $item->employee->name }}
              @endif
              @if($item->room)
                · {{ __('woork.room') }}: {{ $item->room->name }}
              @endif
              @if($item->resolved_at)
                · {{ __('dashboard.alert_resolved') }}: {{ $item->resolved_at->diffForHumans() }}
              @endif
            </div>
          </div>

          <div class="flex items-center gap-2 self-start">
            <x-badge color="{{ $item->level === 'critical' ? 'rose' : ($item->level === 'warning' ? 'amber' : 'emerald') }}">{{ $item->level }}</x-badge>
            @if($item->is_active)
              <form method="POST" action="{{ route('alerts.resolve', $item) }}">@csrf
                <button class="rounded-xl border px-3 py-2 text-sm dark:border-white/10">{{ __('dashboard.alert_mark_resolved') }}</button>
              </form>
            @endif
          </div>
        </li>
      @empty
        <li class="py-6 text-center text-slate-400">{{ __('dashboard.no_alerts') }}</li>
      @endforelse
    </ul>

    <div class="mt-5">
      {{ $alerts->links() }}
    </div>
  </div>
</div>
@endsection
