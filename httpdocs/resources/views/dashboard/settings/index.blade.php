@extends('layouts.app')
@section('title','Settings')
@section('page','Settings')
@section('content')
<div class="grid md:grid-cols-2 gap-6">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
    <div class="text-sm font-semibold mb-2">Language</div>
    <form method="POST" action="{{ route('settings.language') }}" class="flex items-center gap-3">@csrf
      <select name="lang" class="rounded-lg border border-slate-300 dark:border-white/10 bg-white dark:bg-white/5 px-2 py-1 text-sm">
        <option value="en" @selected(app()->getLocale()==='en')>English</option>
        <option value="ar" @selected(app()->getLocale()==='ar')>العربية</option>
        <option value="tr" @selected(app()->getLocale()==='tr')>Türkçe</option>
      </select>
      <button class="rounded-xl bg-emerald-600 text-white px-4 py-2">Save</button>
    </form>
  </div>
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-5">
    <div class="text-sm font-semibold mb-2">Organization</div>
    <form method="POST" action="{{ route('settings.organization') }}" class="space-y-3">@csrf
      <div>
        <label class="block text-sm mb-1">{{ __('woork.company_type') }}</label>
        <select name="company_type" class="w-full rounded-lg border border-slate-300 dark:border-white/10 bg-white dark:bg-white/5 px-2 py-1 text-sm">
          <option value="company" @selected(auth()->user()->organization?->company_type === 'company')>{{ __('woork.company_type.company') }}</option>
          <option value="restaurant" @selected(auth()->user()->organization?->company_type === 'restaurant')>{{ __('woork.company_type.restaurant') }}</option>
        </select>
      </div>
      <button class="rounded-xl bg-emerald-600 text-white px-4 py-2">{{ __('woork.save') }}</button>
    </form>
  </div>
</div>
@endsection
