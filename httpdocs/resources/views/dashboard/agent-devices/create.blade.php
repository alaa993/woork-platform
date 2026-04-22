@extends('layouts.app')
@section('title', __('dashboard.agent_create'))
@section('page', __('dashboard.agent_create'))
@section('content')
<div class="max-w-3xl rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
  @include('partials.plan-usage', ['usage' => $usage ?? null])
  <form method="POST" action="{{ route('agent-devices.store') }}" class="grid md:grid-cols-2 gap-4">
    @csrf

    <div class="md:col-span-2">
      <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.agent_devices_intro') }}</p>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('dashboard.agent_name') }}</label>
      <input name="name" value="{{ old('name', 'Branch PC') }}" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('dashboard.agent_os') }}</label>
      <select name="os" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
        @foreach(['windows', 'linux'] as $os)
          <option value="{{ $os }}" @selected(old('os', 'windows') === $os)>{{ $os }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="block text-sm mb-1">{{ __('dashboard.agent_version') }}</label>
      <input name="version" value="{{ old('version', '1.0.0') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
    </div>

    <div class="md:col-span-2 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/70 dark:bg-white/[0.03] p-4 text-sm text-slate-600 dark:text-slate-300">
      {{ __('dashboard.agent_install_requirements') }}
    </div>

    <div class="md:col-span-2 flex gap-3">
      <button class="rounded-xl bg-emerald-600 text-white px-4 py-2">{{ __('woork.save') }}</button>
      <a href="{{ route('agent-devices.index') }}" class="px-4 py-2 rounded-xl border dark:border-white/10">{{ __('woork.cancel') }}</a>
    </div>
  </form>
</div>
@endsection
