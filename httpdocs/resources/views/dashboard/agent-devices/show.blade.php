@extends('layouts.app')
@section('title', $agentDevice->name)
@section('page', $agentDevice->name)
@section('actions')
  <div class="flex items-center gap-2">
    <a href="{{ route('agent-devices.install', $agentDevice) }}" class="rounded-xl bg-emerald-600 text-white px-3 py-1.5 text-sm">{{ __('dashboard.agent_open_install_guide') }}</a>
    <a href="{{ route('agent-devices.validation', $agentDevice) }}" class="rounded-xl border px-3 py-1.5 text-sm dark:border-white/10">{{ __('dashboard.agent_validation_title') }}</a>
    <form method="POST" action="{{ route('agent-devices.rotate-token', $agentDevice) }}">@csrf
      <button class="rounded-xl border px-3 py-1.5 text-sm dark:border-white/10">{{ __('dashboard.agent_rotate_token') }}</button>
    </form>
  </div>
@endsection
@section('content')
<div class="space-y-6">
  @if(!($onboarding['is_complete'] ?? false))
    <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/80 p-4 dark:border-emerald-900/30 dark:bg-emerald-950/20">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ __('dashboard.onboarding_next_step') }}</div>
          <div class="mt-1 text-sm text-emerald-700/80 dark:text-emerald-200/80">{{ $onboarding['next_step']['label'] ?? __('dashboard.onboarding_complete') }}</div>
        </div>
        @if(!empty($onboarding['next_step']['route']))
          <a href="{{ $onboarding['next_step']['route'] }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white">
            {{ $onboarding['next_step']['action'] ?? __('dashboard.getting_started') }}
          </a>
        @endif
      </div>
    </div>
  @endif

  <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold">{{ $agentDevice->name }}</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_devices_intro') }}</p>
        </div>
        <span class="rounded-full px-2.5 py-1 text-xs {{ $agentDevice->status === 'online' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' }}">
          {{ $agentDevice->status }}
        </span>
      </div>

      <dl class="mt-5 grid md:grid-cols-2 gap-4 text-sm">
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_os') }}</dt>
          <dd class="mt-1 font-medium">{{ $agentDevice->os ?: 'windows' }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_version') }}</dt>
          <dd class="mt-1 font-medium">{{ $agentDevice->version ?: '1.0.0' }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_last_seen') }}</dt>
          <dd class="mt-1 font-medium">{{ optional($agentDevice->last_seen_at)->diffForHumans() ?? '—' }}</dd>
        </div>
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_cameras_count') }}</dt>
          <dd class="mt-1 font-medium">{{ $agentDevice->cameras->count() }}</dd>
        </div>
      </dl>
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.agent_pairing_token') }}</h3>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_copy_token') }}</p>
      <div class="mt-4 rounded-xl bg-slate-950 text-emerald-300 font-mono text-sm p-4 break-all">{{ $agentDevice->pairing_token }}</div>

      <div class="mt-5 space-y-3 text-sm">
        <div>
          <div class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_api_register') }}</div>
          <div class="font-mono text-xs break-all">{{ $registerEndpoint }}</div>
        </div>
        <div>
          <div class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_api_config') }}</div>
          <div class="font-mono text-xs break-all">{{ $configEndpoint }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h3 class="font-semibold">{{ __('dashboard.agent_assigned_cameras') }}</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_install_intro') }}</p>
      </div>
      <a href="{{ route('agent-devices.install', $agentDevice) }}" class="rounded-xl border px-3 py-2 text-sm dark:border-white/10">{{ __('dashboard.agent_open_install_guide') }}</a>
    </div>

    <div class="mt-4 overflow-auto">
      <table class="min-w-full text-sm">
        <thead class="text-slate-500 dark:text-slate-400">
          <tr>
            <th class="py-2 text-start">{{ __('woork.name') }}</th>
            <th class="py-2 text-start">{{ __('woork.room') }}</th>
            <th class="py-2 text-start">{{ __('woork.analysis_mode') }}</th>
            <th class="py-2 text-start">{{ __('woork.stream_status') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
          @forelse($agentDevice->cameras as $camera)
            <tr>
              <td class="py-2">{{ $camera->name }}</td>
              <td class="py-2">{{ $camera->room?->name ?? '—' }}</td>
              <td class="py-2">{{ $camera->analysis_mode }}</td>
              <td class="py-2">{{ $camera->stream_status ?: ($camera->status ?: 'pending') }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="py-6 text-center text-slate-400">{{ __('woork.no_data') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.agent_recent_heartbeats') }}</h3>
      <div class="mt-4 overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="text-slate-500 dark:text-slate-400">
            <tr>
              <th class="py-2 text-start">{{ __('dashboard.agent_status') }}</th>
              <th class="py-2 text-start">IP</th>
              <th class="py-2 text-start">{{ __('dashboard.agent_last_seen') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
            @forelse($agentDevice->heartbeats as $heartbeat)
              <tr>
                <td class="py-2">{{ $heartbeat->status }}</td>
                <td class="py-2 font-mono text-xs">{{ $heartbeat->ip_address ?? '—' }}</td>
                <td class="py-2">{{ optional($heartbeat->checked_at)->diffForHumans() ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="py-6 text-center text-slate-400">{{ __('woork.no_data') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.agent_camera_diagnostics') }}</h3>
      <div class="mt-4 overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="text-slate-500 dark:text-slate-400">
            <tr>
              <th class="py-2 text-start">{{ __('woork.name') }}</th>
              <th class="py-2 text-start">{{ __('woork.stream_status') }}</th>
              <th class="py-2 text-start">{{ __('dashboard.agent_last_seen') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
            @forelse($recentCameraHeartbeats as $heartbeat)
              <tr>
                <td class="py-2">
                  <div>{{ $heartbeat->camera?->name ?? '—' }}</div>
                  @if($heartbeat->health_message)
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $heartbeat->health_message }}</div>
                  @endif
                </td>
                <td class="py-2">{{ $heartbeat->stream_status }}</td>
                <td class="py-2">{{ optional($heartbeat->checked_at)->diffForHumans() ?? '—' }}</td>
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
