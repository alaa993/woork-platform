@extends('layouts.app')
@section('title', $camera->name)
@section('page', $camera->name)
@section('actions')
  <a href="{{ route('camera-health.index') }}" class="rounded-xl border px-3 py-1.5 text-sm dark:border-white/10">{{ __('dashboard.camera_back_to_health') }}</a>
@endsection
@section('content')
<div class="space-y-6">
  <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold">{{ $camera->name }}</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_health_intro') }}</p>
        </div>
        <span class="rounded-full px-2.5 py-1 text-xs {{ $camera->stream_status === 'online' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : ($camera->stream_status === 'warning' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300') }}">
          {{ $camera->stream_status ?: 'pending' }}
        </span>
      </div>

      <dl class="mt-5 grid md:grid-cols-2 gap-4 text-sm">
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('woork.room') }}</dt>
          <dd class="mt-1 font-medium">{{ $camera->room?->name ?? '—' }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('woork.agent_device') }}</dt>
          <dd class="mt-1 font-medium">{{ $camera->agentDevice?->name ?? __('woork.unassigned') }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('woork.analysis_mode') }}</dt>
          <dd class="mt-1 font-medium">{{ $camera->analysis_mode }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_runtime_analyzer') }}</dt>
          <dd class="mt-1 font-medium">{{ $camera->runtime_diagnostics['analyzer'] ?? '—' }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_runtime_detector') }}</dt>
          <dd class="mt-1 font-medium">{{ $camera->runtime_diagnostics['detector'] ?? '—' }}</dd>
          @if($camera->runtime_diagnostics['detector_bundle'])
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $camera->runtime_diagnostics['detector_bundle'] }}</div>
          @endif
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_last_seen') }}</dt>
          <dd class="mt-1 font-medium">{{ optional($camera->last_seen_at)->diffForHumans() ?? '—' }}</dd>
        </div>
      </dl>

      <div class="mt-5 grid gap-4 md:grid-cols-4 text-sm">
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_runtime_state') }}</div>
          <div class="mt-1 font-medium">{{ $camera->runtime_diagnostics['presence_state'] ?? '—' }}</div>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_runtime_people_count') }}</div>
          <div class="mt-1 font-medium">{{ $camera->runtime_diagnostics['person_count'] ?? '—' }}</div>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_runtime_phone_count') }}</div>
          <div class="mt-1 font-medium">{{ $camera->runtime_diagnostics['phone_count'] ?? '—' }}</div>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_runtime_phone_support') }}</div>
          <div class="mt-1 font-medium">
            {{ is_null($camera->runtime_diagnostics['phone_supported']) ? '—' : ($camera->runtime_diagnostics['phone_supported'] ? __('dashboard.camera_runtime_supported') : __('dashboard.camera_runtime_not_supported')) }}
          </div>
        </div>
      </div>

      @if(!empty($camera->runtime_diagnostics['active_track_ids']))
        <div class="mt-4 rounded-xl border border-slate-200/70 dark:border-white/10 p-4 text-sm">
          <div class="text-slate-500 dark:text-slate-400">{{ __('dashboard.camera_runtime_tracks') }}</div>
          <div class="mt-2 font-medium">{{ implode(', ', $camera->runtime_diagnostics['active_track_ids']) }}</div>
        </div>
      @endif

      @if($camera->health_message)
        <div class="mt-4 rounded-xl border border-amber-200/70 bg-amber-50/80 p-4 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/10 dark:text-amber-300">
          {{ $camera->health_message }}
        </div>
      @endif
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.camera_event_breakdown') }}</h3>
      <div class="mt-4 space-y-3">
        @forelse($eventBreakdown as $type => $count)
          <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200/70 dark:border-white/10 p-3 text-sm">
            <span>{{ $type }}</span>
            <span class="font-semibold">{{ $count }}</span>
          </div>
        @empty
          <div class="text-sm text-slate-400">{{ __('woork.no_data') }}</div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.camera_recent_checks') }}</h3>
      <div class="mt-4 overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="text-slate-500 dark:text-slate-400">
            <tr>
              <th class="py-2 text-start">{{ __('woork.stream_status') }}</th>
              <th class="py-2 text-start">{{ __('dashboard.camera_runtime_analyzer') }}</th>
              <th class="py-2 text-start">{{ __('dashboard.camera_runtime_fps') }}</th>
              <th class="py-2 text-start">{{ __('dashboard.agent_last_seen') }}</th>
              <th class="py-2 text-start">{{ __('dashboard.camera_last_frame') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
            @forelse($camera->heartbeats as $heartbeat)
              <tr>
                <td class="py-2">
                  <div>{{ $heartbeat->stream_status }}</div>
                  @if($heartbeat->health_message)
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $heartbeat->health_message }}</div>
                  @endif
                  @if($heartbeat->observations)
                    <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                      {{ __('dashboard.camera_runtime_state') }}: {{ $heartbeat->observations['presence_state'] ?? '—' }}
                      · {{ __('dashboard.camera_runtime_people_count') }}: {{ $heartbeat->observations['person_count'] ?? '—' }}
                      · {{ __('dashboard.camera_runtime_phone_count') }}: {{ $heartbeat->observations['phone_count'] ?? '—' }}
                    </div>
                  @endif
                </td>
                <td class="py-2">{{ $heartbeat->analyzer ?? '—' }}</td>
                <td class="py-2">{{ $heartbeat->fps ? number_format($heartbeat->fps, 2) : '—' }}</td>
                <td class="py-2">{{ optional($heartbeat->checked_at)->diffForHumans() ?? '—' }}</td>
                <td class="py-2">{{ optional($heartbeat->last_frame_at)->diffForHumans() ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="py-6 text-center text-slate-400">{{ __('woork.no_data') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.camera_recent_events') }}</h3>
      <div class="mt-4 overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="text-slate-500 dark:text-slate-400">
            <tr>
              <th class="py-2 text-start">{{ __('dashboard.table_employee') }}</th>
              <th class="py-2 text-start">{{ __('dashboard.camera_event_type') }}</th>
              <th class="py-2 text-start">{{ __('dashboard.camera_event_started') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
            @forelse($recentEvents as $event)
              <tr>
                <td class="py-2">{{ $event->employee?->name ?? __('dashboard.unknown_employee') }}</td>
                <td class="py-2">{{ $event->type }}</td>
                <td class="py-2">{{ optional($event->started_at)->diffForHumans() ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="py-6 text-center text-slate-400">{{ __('woork.no_data') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
