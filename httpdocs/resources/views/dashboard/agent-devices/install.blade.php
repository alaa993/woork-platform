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
      Download the Windows installer, paste the pairing token into the Woork Agent app, then start the service from the app. No command line is required for subscribers.
    </p>
    <p class="mt-2 max-w-3xl text-sm font-medium text-amber-700 dark:text-amber-300">
      Recommended system: Windows 10/11 64-bit. A limited Windows 7 legacy installer is available only when explicitly provided by support.
    </p>
    <p class="mt-2 max-w-3xl text-xs text-slate-500 dark:text-slate-400">
      During internal testing, Chrome may show "not commonly downloaded" because the installer is not code-signed yet. Production distribution requires a code-signing certificate.
    </p>
    <div class="mt-4 flex flex-wrap gap-3">
      <a href="{{ $downloadPath }}" class="woork-btn-primary rounded-xl bg-emerald-600 text-white px-4 py-2 text-sm">{{ __('dashboard.agent_download') }} Windows 10/11</a>
      @if(!empty($legacyDownloadPath))
        <a href="{{ $legacyDownloadPath }}" class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-950/20 dark:text-amber-200">
          Download Windows 7 Legacy
        </a>
      @endif
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
          <div class="mt-2 text-slate-500 dark:text-slate-400">
            Windows 10/11 installer:
            <span class="font-mono">{{ $downloadPath }}</span>
            @if(isset($installerSize))
              <span class="{{ $installerSize > 0 ? 'text-slate-500 dark:text-slate-400' : 'text-red-600 dark:text-red-300' }}">
                ({{ number_format($installerSize / 1024 / 1024, 2) }} MB)
              </span>
            @endif
          </div>
          @if(!empty($legacyDownloadPath))
            <div class="mt-2 text-amber-700 dark:text-amber-300">
              Windows 7 Legacy installer:
              <span class="font-mono">{{ $legacyDownloadPath }}</span>
              @if(isset($legacyInstallerSize))
                <span class="{{ $legacyInstallerSize > 0 ? 'text-amber-700 dark:text-amber-300' : 'text-red-600 dark:text-red-300' }}">
                  ({{ number_format($legacyInstallerSize / 1024 / 1024, 2) }} MB)
                </span>
              @endif
            </div>
          @endif
          @if((isset($installerSize) && $installerSize <= 0) || (isset($legacyInstallerSize) && $legacyInstallerSize <= 0))
            <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
              Installer file size is zero. Re-upload the EXE to public/downloads before testing.
            </div>
          @endif
          @if(!empty($release))
            <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_release_latest') }} {{ $release->version }} · {{ $release->platform }}</div>
          @endif
        </li>
        <li class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="font-semibold">2. Install Woork Agent</div>
          <div class="mt-2 text-slate-500 dark:text-slate-400">Run <span class="font-mono">WoorkAgentSetup-1.0.0.exe</span> on Windows 10/11 64-bit. It installs the control app and the background Windows service.</div>
          @if(!empty($legacyDownloadPath))
            <div class="mt-2 text-xs text-amber-700 dark:text-amber-300">For Windows 7 only, use <span class="font-mono">WoorkAgentSetup-LegacyWin7-1.0.0.exe</span>. It is a compatibility build and may have limited AI analysis performance.</div>
          @endif
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
        @if(!empty($release))
          <div class="mt-4 space-y-1 text-xs text-slate-500 dark:text-slate-400">
            <div>{{ __('dashboard.agent_release_version') }}: {{ $release->version }}</div>
            @if(isset($installerSize))
              <div>{{ __('dashboard.agent_release_size') }}: {{ number_format($installerSize / 1024 / 1024, 2) }} MB</div>
            @endif
            @if($release->checksum_sha256)
              <div class="font-mono break-all">sha256: {{ $release->checksum_sha256 }}</div>
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
