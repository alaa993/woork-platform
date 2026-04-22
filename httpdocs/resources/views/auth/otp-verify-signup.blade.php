@extends('layouts.landing')
@section('title','Verify OTP')
@section('content')
<div class="max-w-md mx-auto px-4 py-10">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-6">
    <h1 class="text-2xl font-semibold mb-2">Verify code</h1>
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">We sent a 6-digit code to WhatsApp: <b>{{ $phone }}</b></p>
    @if (session('status'))<div class="mb-3 rounded-lg border border-emerald-300/70 bg-emerald-50 dark:bg-emerald-900/20 p-3 text-sm">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="mb-3 rounded-lg border border-red-300/70 bg-red-50 dark:bg-red-900/20 p-3 text-sm"><ul class="list-disc ps-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('signup.verify') }}" class="space-y-4">@csrf
      <input type="hidden" name="phone" value="{{ $phone }}">
      <div><label class="block text-sm mb-1">OTP code</label>
        <input name="code" maxlength="6" inputmode="numeric" pattern="[0-9]*" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm tracking-widest text-center dark:bg-slate-900 dark:border-white/10"></div>
      <button class="woork-btn-primary w-full rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2">Create my workspace</button>
    </form>
  </div>
</div>
@endsection
