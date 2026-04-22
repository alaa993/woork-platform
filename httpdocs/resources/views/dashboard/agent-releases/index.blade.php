@extends('layouts.app')
@section('title', __('dashboard.agent_releases'))
@section('page', __('dashboard.agent_releases'))
@section('actions')
  @if((auth()->user()->role ?? null) === 'super_admin')
    <a href="{{ route('admin.agent-releases.create') }}" class="rounded-xl bg-emerald-600 text-white px-3 py-1.5 text-sm">{{ __('dashboard.agent_release_publish') }}</a>
  @endif
@endsection
@section('content')
<div class="space-y-6">
  <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-gradient-to-br from-sky-50 via-white to-emerald-50 dark:from-sky-950/20 dark:via-slate-950 dark:to-slate-950 p-6 shadow-sm">
    <h2 class="text-2xl font-semibold tracking-tight">{{ __('dashboard.agent_releases') }}</h2>
    <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">{{ __('dashboard.agent_releases_intro') }}</p>
    @if($latestStable)
      <div class="mt-4 flex flex-wrap items-center gap-3 text-sm">
        <span class="rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 px-3 py-1">{{ __('dashboard.agent_release_latest') }} {{ $latestStable->version }}</span>
        <a href="{{ asset($latestStable->artifact_path) }}" class="rounded-xl bg-emerald-600 text-white px-4 py-2">{{ __('dashboard.agent_download') }}</a>
      </div>
    @endif
  </div>

  <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse($releases as $release)
      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-lg font-semibold">{{ $release->version }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $release->platform }} · {{ $release->channel }}</div>
          </div>
          <span class="rounded-full px-2.5 py-1 text-xs {{ $release->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-slate-300' }}">
            {{ $release->is_active ? 'active' : 'inactive' }}
          </span>
        </div>

        <div class="mt-4 text-sm text-slate-500 dark:text-slate-400">
          <div>{{ __('dashboard.agent_release_published') }}: {{ optional($release->published_at)->toDateTimeString() ?? '—' }}</div>
          @if($release->artifact_size)
            <div>{{ __('dashboard.agent_release_size') }}: {{ number_format($release->artifact_size / 1024 / 1024, 2) }} MB</div>
          @endif
        </div>

        @if($release->notes)
          <div class="mt-4 rounded-xl border border-slate-200/70 dark:border-white/10 p-4 text-sm text-slate-600 dark:text-slate-300 whitespace-pre-line">{{ $release->notes }}</div>
        @endif

        <div class="mt-4 flex items-center justify-between gap-3">
          <div class="text-xs text-slate-500 dark:text-slate-400">
            <div class="font-mono break-all">{{ $release->artifact_path }}</div>
            @if($release->checksum_sha256)
              <div class="mt-1 font-mono break-all">sha256: {{ $release->checksum_sha256 }}</div>
            @endif
          </div>
          <a href="{{ asset($release->artifact_path) }}" class="rounded-xl border px-3 py-2 text-sm dark:border-white/10">{{ __('dashboard.agent_download') }}</a>
        </div>
      </div>
    @empty
      <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 dark:border-white/10 bg-white/50 dark:bg-white/[0.03] p-8 text-center text-sm text-slate-500 dark:text-slate-400">
        {{ __('dashboard.agent_release_empty') }}
      </div>
    @endforelse
  </div>

  <div>{{ $releases->links() }}</div>
</div>
@endsection
