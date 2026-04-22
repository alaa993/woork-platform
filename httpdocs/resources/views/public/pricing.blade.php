@extends('layouts.public')
@section('title','Pricing — Woork')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-16">
  <h1 class="text-4xl font-bold text-center mb-2">Simple, transparent pricing</h1>
  <p class="text-center text-slate-600 dark:text-slate-400 mb-10">Start with a 14-day free trial. No credit card required.</p>
  <div class="grid md:grid-cols-3 gap-6">
    @php($plans=[
      ['Starter', 'For small teams', 0, ['3 cameras','15 employees','Daily score','Email support']],
      ['Growth', 'Best for shops', 49, ['10 cameras','50 employees','Reports & exports','Priority support']],
      ['Scale', 'For organizations', 199, ['Unlimited cameras','Unlimited employees','SSO & audit logs','Dedicated manager']],
    ])
    @foreach($plans as [$name,$desc,$price,$features])
      <div class="rounded-2xl border bg-white/90 dark:bg-slate-900/80 p-6 shadow-sm">
        <div class="text-sm text-slate-500 dark:text-slate-400">{{ $desc }}</div>
        <div class="mt-1 text-2xl font-semibold">{{ $name }}</div>
        <div class="mt-4"><span class="text-4xl font-extrabold">${{ $price }}</span><span class="text-slate-500 dark:text-slate-400">/mo</span></div>
        <ul class="mt-4 space-y-2 text-sm">
          @foreach($features as $f)<li class="flex items-center gap-2"><span>✓</span>{{ $f }}</li>@endforeach
        </ul>
        <div class="mt-6"><x-button class="w-full" as="a" href="{{ route('login') }}">Start free trial</x-button></div>
      </div>
    @endforeach
  </div>
</div>
@endsection
