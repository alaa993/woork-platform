@extends('layouts.app')
@section('title', __('dashboard.profile_title'))
@section('page', __('dashboard.profile_title'))

@section('content')
<div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-6 shadow-lg max-w-xl">
  <h1 class="text-lg font-semibold mb-2">{{ __('dashboard.profile_intro') }}</h1>
  <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 mt-4">
    @csrf
    @method('PUT')

    <div>
      <label class="block text-sm font-medium mb-1">{{ __('dashboard.profile_name') }}</label>
      <input name="name" value="{{ old('name', $user->name) }}"
             class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:bg-slate-900 dark:border-white/10 dark:text-white">
      @error('name')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">{{ __('dashboard.profile_email') }}</label>
      <input name="email" value="{{ old('email', $user->email) }}"
             class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:bg-slate-900 dark:border-white/10 dark:text-white">
      @error('email')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">{{ __('dashboard.profile_language') }}</label>
      <select name="language"
              class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:bg-slate-900 dark:border-white/10 dark:text-white">
        @foreach(['en'=>'English','ar'=>'العربية','tr'=>'Türkçe'] as $code => $label)
          <option value="{{ $code }}" @selected(old('language', $user->language ?? app()->getLocale()) === $code)>{{ $label }}</option>
        @endforeach
      </select>
    </div>

    <button class="w-full rounded-xl bg-emerald-600 text-white py-2 font-semibold hover:bg-emerald-500 transition">
      {{ __('dashboard.profile_save') }}
    </button>
  </form>
</div>
@endsection
