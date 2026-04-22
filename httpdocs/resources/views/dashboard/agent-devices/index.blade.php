@extends('layouts.app')
@section('title', __('dashboard.agent_devices_title'))
@section('page', __('dashboard.agent_devices_title'))
@section('actions')
  <div class="flex items-center gap-2">
    <a href="{{ route('agent-devices.create') }}" class="rounded-xl bg-emerald-600 text-white px-3 py-1.5 text-sm">{{ __('dashboard.agent_create') }}</a>
  </div>
@endsection
@section('content')
<div class="space-y-6">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
    <h2 class="text-lg font-semibold">{{ __('dashboard.agent_devices_title') }}</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_devices_intro') }}</p>
  </div>

  <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($devices as $device)
      <a href="{{ route('agent-devices.show', $device) }}" class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5 shadow-sm hover:shadow-lg transition">
        <div class="flex items-start justify-between gap-4">
          <div>
            <div class="text-base font-semibold">{{ $device->name }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $device->os ?: 'windows' }} · {{ $device->version ?: '1.0.0' }}</div>
          </div>
          <span class="rounded-full px-2.5 py-1 text-xs {{ $device->status === 'online' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
            {{ $device->status }}
          </span>
        </div>

        <dl class="mt-4 space-y-2 text-sm">
          <div class="flex items-center justify-between gap-3">
            <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_cameras_count') }}</dt>
            <dd class="font-medium">{{ $device->cameras_count }}</dd>
          </div>
          <div class="flex items-center justify-between gap-3">
            <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_last_seen') }}</dt>
            <dd class="font-medium">{{ optional($device->last_seen_at)->diffForHumans() ?? '—' }}</dd>
          </div>
          <div class="flex items-center justify-between gap-3">
            <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_pairing_token') }}</dt>
            <dd class="font-mono text-xs">{{ $device->pairing_token }}</dd>
          </div>
        </dl>
      </a>
    @empty
      <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 dark:border-white/10 bg-white/50 dark:bg-white/[0.03] p-8 text-center text-sm text-slate-500 dark:text-slate-400">
        {{ __('dashboard.agent_no_devices') }}
      </div>
    @endforelse
  </div>

  <div>{{ $devices->links() }}</div>
</div>
@endsection
