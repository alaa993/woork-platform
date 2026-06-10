@extends('layouts.landing')

@section('title', __('auth.login.verify_title'))

@section('content')
<div class="max-w-md mx-auto px-4 py-16">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-6 shadow-sm">
    <h1 class="text-lg font-semibold mb-4">{{ __('auth.login.verify_title') }}</h1>

    <form method="POST" action="{{ route('otp.verify') }}" class="space-y-3">
      @csrf
      <input type="hidden" name="phone" value="{{ old('phone', $phone ?? '') }}">

      @if (session('status'))
        <div class="rounded-lg border border-emerald-300/70 bg-emerald-50 dark:bg-emerald-900/20 p-3 text-sm">{{ session('status') }}</div>
      @endif
      @if ($errors->any())
        <div class="rounded-lg border border-red-300/70 bg-red-50 dark:bg-red-900/20 p-3 text-sm">
          <ul class="list-disc ps-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      @endif
      
      <input
        name="code"
        required
        maxlength="6"
        inputmode="numeric"
        pattern="[0-9]*"
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
