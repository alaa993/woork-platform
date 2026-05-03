@extends('layouts.app')
@section('title', __('dashboard.agent_release_publish'))
@section('page', __('dashboard.agent_release_publish'))
@section('content')
<div class="max-w-3xl rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
  <form method="POST" action="{{ route('admin.agent-releases.store') }}" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">
    @csrf

    <div>
      <label class="block text-sm mb-1">{{ __('dashboard.agent_release_version') }}</label>
      <input name="version" value="{{ old('version', '1.0.0') }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('dashboard.agent_release_channel') }}</label>
      <select name="channel" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
        @foreach(['stable', 'beta'] as $channel)
          <option value="{{ $channel }}" @selected(old('channel', 'stable') === $channel)>{{ $channel }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('dashboard.agent_release_platform') }}</label>
      <select name="platform" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
        @foreach($supportedPlatforms as $platform => $meta)
          <option value="{{ $platform }}" @selected(old('platform', 'windows-x64') === $platform)>{{ $meta['label'] }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('dashboard.agent_release_upload') }}</label>
      <input type="file" name="artifact" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_release_upload_hint') }}</p>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('dashboard.agent_release_artifact') }}</label>
      <input name="artifact_path" value="{{ old('artifact_path', 'downloads/WoorkAgentSetup-1.0.0.exe') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('dashboard.agent_release_published') }}</label>
      <input type="datetime-local" name="published_at" value="{{ old('published_at') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div class="flex items-center pt-7">
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
        <span>{{ __('dashboard.agent_release_active') }}</span>
      </label>
    </div>

    <div class="md:col-span-2">
      <label class="block text-sm mb-1">{{ __('dashboard.agent_release_notes') }}</label>
      <textarea name="notes" rows="6" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">{{ old('notes', "Windows installer\nControl app included\nRuns as Windows service\nCamera diagnostics enabled") }}</textarea>
    </div>

    <div class="md:col-span-2 flex gap-3">
      <button class="rounded-xl bg-emerald-600 text-white px-4 py-2">{{ __('woork.save') }}</button>
      <a href="{{ route('agent-releases.index') }}" class="px-4 py-2 rounded-xl border dark:border-white/10">{{ __('woork.cancel') }}</a>
    </div>
  </form>
</div>
@endsection
