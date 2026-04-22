@extends('layouts.app')
@section('title', __('dashboard.camera_health'))
@section('page', __('dashboard.camera_health'))
@section('content')
<div class="space-y-6">
  <div class="grid gap-4 md:grid-cols-4">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_total') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $stats['total'] }}</div>
    </div>
    <div class="rounded-2xl border border-emerald-200/70 dark:border-emerald-900/40 bg-emerald-50/80 dark:bg-emerald-950/10 p-5">
      <div class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('dashboard.camera_online') }}</div>
      <div class="mt-2 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $stats['online'] }}</div>
    </div>
    <div class="rounded-2xl border border-amber-200/70 dark:border-amber-900/40 bg-amber-50/80 dark:bg-amber-950/10 p-5">
      <div class="text-sm text-amber-700 dark:text-amber-300">{{ __('dashboard.camera_warning') }}</div>
      <div class="mt-2 text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ $stats['warning'] }}</div>
    </div>
    <div class="rounded-2xl border border-rose-200/70 dark:border-rose-900/40 bg-rose-50/80 dark:bg-rose-950/10 p-5">
      <div class="text-sm text-rose-700 dark:text-rose-300">{{ __('dashboard.camera_offline') }}</div>
      <div class="mt-2 text-2xl font-semibold text-rose-700 dark:text-rose-300">{{ $stats['offline'] }}</div>
    </div>
  </div>

  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
    <h2 class="text-lg font-semibold">{{ __('dashboard.camera_health') }}</h2>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_health_intro') }}</p>

    <div class="mt-4 overflow-auto">
      <table class="min-w-full text-sm">
        <thead class="text-slate-500 dark:text-slate-400">
          <tr>
            <th class="py-2 text-start">{{ __('woork.name') }}</th>
            <th class="py-2 text-start">{{ __('woork.room') }}</th>
            <th class="py-2 text-start">{{ __('woork.agent_device') }}</th>
            <th class="py-2 text-start">{{ __('dashboard.camera_runtime_detector') }}</th>
            <th class="py-2 text-start">{{ __('dashboard.camera_runtime_state') }}</th>
            <th class="py-2 text-start">{{ __('woork.stream_status') }}</th>
            <th class="py-2 text-start">{{ __('dashboard.agent_last_seen') }}</th>
            <th class="py-2 text-start">{{ __('dashboard.camera_recent_checks') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
          @forelse($cameras as $camera)
            <tr>
              <td class="py-2">
                <div><a href="{{ route('camera-health.show', $camera) }}" class="font-medium text-emerald-700 hover:underline dark:text-emerald-300">{{ $camera->name }}</a></div>
                @if($camera->health_message)
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ $camera->health_message }}</div>
                @endif
              </td>
              <td class="py-2">{{ $camera->room?->name ?? '—' }}</td>
              <td class="py-2">{{ $camera->agentDevice?->name ?? __('woork.unassigned') }}</td>
              <td class="py-2">
                <div>{{ $camera->runtime_diagnostics['detector'] ?? '—' }}</div>
                @if($camera->runtime_diagnostics['detector_bundle'])
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ $camera->runtime_diagnostics['detector_bundle'] }}</div>
                @endif
              </td>
              <td class="py-2">
                <div>{{ $camera->runtime_diagnostics['presence_state'] ?? '—' }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">
                  {{ __('dashboard.camera_runtime_people_count') }}: {{ $camera->runtime_diagnostics['person_count'] ?? '—' }}
                  ·
                  {{ __('dashboard.camera_runtime_phone_support') }}:
                  {{ is_null($camera->runtime_diagnostics['phone_supported']) ? '—' : ($camera->runtime_diagnostics['phone_supported'] ? __('dashboard.camera_runtime_supported') : __('dashboard.camera_runtime_not_supported')) }}
                </div>
              </td>
              <td class="py-2">
                <span class="rounded-full px-2.5 py-1 text-xs {{ $camera->stream_status === 'online' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : ($camera->stream_status === 'warning' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300') }}">
                  {{ $camera->stream_status ?: 'pending' }}
                </span>
              </td>
              <td class="py-2">{{ optional($camera->last_seen_at)->diffForHumans() ?? '—' }}</td>
              <td class="py-2">
                <div class="space-y-1">
                  @forelse($camera->heartbeats as $heartbeat)
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ optional($heartbeat->checked_at)->diffForHumans() ?? '—' }} · {{ $heartbeat->stream_status }} · {{ $heartbeat->analyzer ?? '—' }}</div>
                  @empty
                    <div class="text-xs text-slate-400">{{ __('woork.no_data') }}</div>
                  @endforelse
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="py-6 text-center text-slate-400">{{ __('woork.no_data') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
