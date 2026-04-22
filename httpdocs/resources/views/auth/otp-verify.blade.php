@extends('layouts.landing')

@section('title', __('auth.login.verify_title'))

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-6 shadow-sm">
    <h1 class="text-lg font-semibold mb-4">{{ __('auth.login.verify_title') }}</h1>

    <form method="POST" action="{{ route('otp.verify') }}" class="space-y-3">
      @csrf
      <input type="hidden" name="phone" value="{{ old('phone', $phone ?? '') }}">
      
      <input
        name="code"
        placeholder="{{ __('auth.login.code') }}"
        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
      >

      <button class="woork-btn-primary w-full rounded-xl bg-emerald-600 text-white px-4 py-2 font-medium hover:bg-emerald-500 transition">
        {{ __('auth.login.verify') }}
      </button>
    </form>

  
  </div>
</div>
@endsection
