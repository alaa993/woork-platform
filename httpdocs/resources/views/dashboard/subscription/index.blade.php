@extends('layouts.app')
@section('title','Subscription')
@section('page','Subscription')
@section('content')
<div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
  @include('partials.plan-usage', ['usage' => $usage ?? null])
  <div class="flex items-center justify-between">
    <div>
      <div class="text-sm text-slate-500">You are on</div>
      <div class="text-xl font-semibold">{{ $plan->name ?? 'Trial' }}</div>
      <div class="mt-1 text-sm text-slate-500">
        Status:
        <span class="font-medium text-slate-700 dark:text-slate-200">{{ $sub->status ?? 'inactive' }}</span>
      </div>
    </div>
    <form method="POST" action="{{ route('billing.checkout') }}">@csrf <button class="rounded-xl bg-emerald-600 text-white px-4 py-2">Upgrade</button></form>
  </div>
  <form method="POST" action="{{ route('billing.portal') }}" class="mt-4">@csrf <button class="rounded-xl border px-4 py-2 dark:border-white/10">Open billing portal</button></form>
</div>
@endsection
