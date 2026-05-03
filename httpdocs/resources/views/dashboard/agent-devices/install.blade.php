@extends('layouts.app')
@section('title', __('dashboard.agent_install_title'))
@section('page', __('dashboard.agent_install_title'))
@section('content')
<div class="space-y-6">
  @if(!($onboarding['is_complete'] ?? false))
    <div class="rounded-2xl border border-emerald-200/70 bg-emerald-50/80 p-4 dark:border-emerald-900/30 dark:bg-emerald-950/20">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ __('dashboard.onboarding_next_step') }}</div>
          <div class="mt-1 text-sm text-emerald-700/80 dark:text-emerald-200/80">
            {{ $onboarding['next_step']['label'] ?? __('dashboard.onboarding_complete') }}
          </div>
        </div>
        <a href="{{ route('onboarding.index') }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white">{{ __('dashboard.getting_started') }}</a>
      </div>
    </div>
  @endif

  <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-gradient-to-br from-emerald-50 via-white to-teal-50 dark:from-emerald-950/20 dark:via-slate-950 dark:to-slate-950 p-6 shadow-sm">
    <h2 class="text-2xl font-semibold tracking-tight">{{ __('dashboard.agent_install_title') }}</h2>
    <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">{{ __('dashboard.agent_install_intro') }}</p>
    <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
      Choose the installer that matches the customer PC, paste the pairing token into the Woork Agent app, then start the service from the app. No command line is required for subscribers.
    </p>
    <p class="mt-2 max-w-3xl text-sm font-medium text-amber-700 dark:text-amber-300">
      Recommended system: Windows 10/11 64-bit. Use 32-bit only for older Windows 10 machines, and Windows 7 Legacy only for unsupported legacy PCs.
    </p>
    <p class="mt-2 max-w-3xl text-xs text-slate-500 dark:text-slate-400">
      During internal testing, Chrome may show "not commonly downloaded" because the installer is not code-signed yet. Production distribution requires a code-signing certificate.
    </p>
    <div class="mt-4 flex flex-wrap gap-3">
      @foreach($downloadVariants as $variant)
        <a href="{{ $variant['download_url'] }}" class="{{ $variant['is_primary'] ? 'woork-btn-primary bg-emerald-600 text-white' : ($variant['is_legacy'] ? 'border border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950/20 dark:text-amber-200' : 'border dark:border-white/10') }} rounded-xl px-4 py-2 text-sm">
          {{ __('dashboard.agent_download') }} {{ $variant['label'] }}
        </a>
      @endforeach
      <a href="{{ route('agent-releases.index') }}" class="rounded-xl border px-4 py-2 text-sm dark:border-white/10">{{ __('dashboard.agent_releases') }}</a>
      <a href="{{ route('agent-devices.validation', $agentDevice) }}" class="rounded-xl border px-4 py-2 text-sm dark:border-white/10">{{ __('dashboard.agent_validation_title') }}</a>
      <a href="{{ route('agent-devices.show', $agentDevice) }}" class="rounded-xl border px-4 py-2 text-sm dark:border-white/10">{{ __('dashboard.agent_open_install_guide') }}</a>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-[1fr_.95fr]">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
      <h3 class="font-semibold">{{ __('dashboard.agent_install_requirements') }}</h3>

      <ol class="mt-4 space-y-4 text-sm">
        <li class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="font-semibold">1. {{ __('dashboard.agent_step_download') }}</div>
          @foreach($downloadVariants as $variant)
            <div class="mt-2 {{ $variant['is_legacy'] ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-slate-400' }}">
              {{ $variant['label'] }}:
              <span class="font-mono">{{ $variant['download_url'] }}</span>
              @if(!empty($variant['artifact_size']))
                <span>({{ number_format($variant['artifact_size'] / 1024 / 1024, 2) }} MB)</span>
              @endif
            </div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $variant['description'] }}</div>
          @endforeach
        </li>
        <li class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="font-semibold">2. Install Woork Agent</div>
          <div class="mt-2 text-slate-500 dark:text-slate-400">Use the package that matches the OS architecture exactly. Windows 10/11 x64 is the primary build. Windows 10 x86 is for 32-bit systems. Windows 7 Legacy is a fallback compatibility build only.</div>
        </li>
        <li class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="font-semibold">3. Pair this device</div>
          <div class="mt-2 text-slate-500 dark:text-slate-400">Paste this token into the Woork Agent control app and press Pair Device.</div>
          <div class="mt-3 rounded-lg bg-slate-950 text-emerald-300 font-mono text-sm p-3 break-all">{{ $agentDevice->pairing_token }}</div>
        </li>
        <li class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="font-semibold">4. Start and verify</div>
          <div class="mt-2 text-slate-500 dark:text-slate-400">Click Start Agent. The app should show Server Connected and camera status after cameras are added in the platform.</div>
        </li>
      </ol>
    </div>

    <div class="space-y-6">
      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.agent_pairing_token') }}</h3>
        <div class="mt-3 rounded-xl bg-slate-950 text-emerald-300 font-mono text-sm p-4 break-all">{{ $agentDevice->pairing_token }}</div>
        @if(!empty($primaryVariant['release']))
          <div class="mt-4 space-y-1 text-xs text-slate-500 dark:text-slate-400">
            <div>{{ __('dashboard.agent_release_version') }}: {{ $primaryVariant['release']->version }}</div>
            <div>{{ __('dashboard.agent_release_platform') }}: {{ $primaryVariant['label'] }}</div>
            @if(!empty($primaryVariant['artifact_size']))
              <div>{{ __('dashboard.agent_release_size') }}: {{ number_format($primaryVariant['artifact_size'] / 1024 / 1024, 2) }} MB</div>
            @endif
            @if($primaryVariant['release']->checksum_sha256)
              <div class="font-mono break-all">sha256: {{ $primaryVariant['release']->checksum_sha256 }}</div>
            @endif
          </div>
        @endif
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.agent_runtime_checks_title') }}</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_runtime_checks_intro') }}</p>
        <div class="mt-4 space-y-4">
          <div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_runtime_checks_doctor') }}</div>
            <pre class="mt-2 overflow-auto rounded-xl bg-slate-950 p-3 text-xs text-emerald-300">Open Woork Agent > Run Doctor</pre>
          </div>
          <div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_runtime_checks_benchmark') }}</div>
            <pre class="mt-2 overflow-auto rounded-xl bg-slate-950 p-3 text-xs text-emerald-300">Open Woork Agent > Start Agent</pre>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
        <h3 class="font-semibold">{{ __('dashboard.agent_install_title') }} API</h3>
        <dl class="mt-4 space-y-3 text-sm">
          <div>
            <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_api_register') }}</dt>
            <dd class="font-mono text-xs break-all">{{ $registerEndpoint }}</dd>
          </div>
          <div>
            <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_api_config') }}</dt>
            <dd class="font-mono text-xs break-all">{{ $configEndpoint }}</dd>
          </div>
          <div>
            <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_api_heartbeat') }}</dt>
            <dd class="font-mono text-xs break-all">{{ $heartbeatEndpoint }}</dd>
          </div>
          <div>
            <dt class="text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_api_ingest') }}</dt>
            <dd class="font-mono text-xs break-all">{{ $ingestEndpoint }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</div>
@endsection
