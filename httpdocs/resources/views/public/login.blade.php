@extends('layouts.landing')
@section('title','Sign in — Woork')
@section('content')
<div class="max-w-md mx-auto px-4 py-16">
  <x-card title="Sign in with WhatsApp OTP">
    <form method="POST" action="{{ route('otp.request') }}" class="space-y-3">@csrf
      <label class="block text-sm font-medium">Phone (WhatsApp)</label>
      <x-input name="phone" placeholder="+1 202 555 0142" />
      <x-button class="w-full">Send code</x-button>
      <p class="text-xs text-slate-500 dark:text-slate-400">We’ll send a one-time code via WhatsApp.</p>
    </form>
  </x-card>
</div>
@endsection
