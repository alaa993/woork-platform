@extends('layouts.landing')

@section('title', __('auth.login.title'))

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-6 shadow-sm">
    <h1 class="text-lg font-semibold mb-4">{{ __('auth.login.title') }}</h1>
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-6">
      {{ __('auth.login.subtitle') }}
    </p>

    <form method="POST" action="{{ route('otp.request') }}" class="space-y-3">
      @csrf

      <label class="block text-sm font-medium mb-1">{{ __('auth.login.phone') }}</label>
      <input
        type="tel"
        name="phone"
        placeholder="{{ __('auth.login.phone_ph') }}"
        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
        required
      >

      <button class="woork-btn-primary w-full rounded-xl bg-emerald-600 text-white px-4 py-2 font-medium hover:bg-emerald-500 transition">
        {{ __('auth.login.send_otp') }}
      </button>
    </form>

    <p class="text-xs text-slate-500 mt-3 text-center">
      {{ __('auth.login.note') }}
    </p>
  </div>
</div>
@endsection
