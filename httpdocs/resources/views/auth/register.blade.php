{{-- resources/views/auth/register.blade.php --}}
@extends('layouts.landing')
@section('title','Create your Woork account')

@section('content')
<div class="max-w-md mx-auto px-4 py-10">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-6 shadow-sm">
    <h1 class="text-xl font-semibold mb-2">Complete your profile</h1>
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
      Phone verified: <b>{{ $phone }}</b>
    </p>

    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm mb-1">Full name</label>
        <input name="name" value="{{ old('name') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:text-slate-100">
        @error('name')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="block text-sm mb-1">Email</label>
        <input name="email" type="email" value="{{ old('email') }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 dark:bg-slate-900 dark:text-slate-100">
        @error('email')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
      </div>

      <button class="woork-btn-primary w-full rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2">
        Create account
      </button>
    </form>
  </div>
</div>
@endsection
