@extends('layouts.landing')
@section('title', __('signup.title'))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-6">
    <h1 class="text-2xl font-semibold mb-6">{{ __('signup.heading') }}</h1>

    @if ($errors->any())
      <div class="mb-4 rounded-lg border border-red-300/70 bg-red-50 dark:bg-red-900/20 p-3 text-sm">
        <ul class="list-disc ps-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <form method="POST" action="{{ route('signup.submit') }}" class="grid md:grid-cols-2 gap-5">
      @csrf

      <div class="md:col-span-2"><h2 class="text-lg font-medium mb-2">{{ __('signup.section.user') }}</h2></div>

      <div>
        <label class="block text-sm mb-1">{{ __('signup.full_name') }}</label>
        <input name="name" value="{{ old('name') }}" required
               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:bg-slate-900 dark:border-white/10">
      </div>

      <div>
        <label class="block text-sm mb-1">{{ __('signup.phone') }}</label>
        <input name="phone" value="{{ old('phone') }}" required
               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:bg-slate-900 dark:border-white/10">
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm mb-1">{{ __('signup.email_opt') }}</label>
        <input type="email" name="email" value="{{ old('email') }}"
               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:bg-slate-900 dark:border-white/10">
      </div>

      <div class="md:col-span-2 pt-2"><h2 class="text-lg font-medium mb-2">{{ __('signup.section.org') }}</h2></div>

      <div>
        <label class="block text-sm mb-1">{{ __('signup.org_name') }}</label>
        <input name="org_name" value="{{ old('org_name') }}" required
               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:bg-slate-900 dark:border-white/10">
      </div>

      <div>
        <label class="block text-sm mb-1">{{ __('signup.company_type') }}</label>
        <select name="company_type" required
                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:bg-slate-900 dark:border-white/10">
          <option value="company" @selected(old('company_type') === 'company')>{{ __('signup.company_type.company') }}</option>
          <option value="restaurant" @selected(old('company_type') === 'restaurant')>{{ __('signup.company_type.restaurant') }}</option>
        </select>
      </div>

      <div>
        <label class="block text-sm mb-1">{{ __('signup.language') }}</label>
        <select name="language"
                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm dark:bg-slate-900 dark:border-white/10">
          <option value="en">{{ __('signup.lang.en') }}</option>
          <option value="ar">{{ __('signup.lang.ar') }}</option>
          <option value="tr">{{ __('signup.lang.tr') }}</option>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm mb-1">{{ __('signup.plan') }}</label>
        <div class="flex flex-wrap gap-3">
          @foreach($plans as $p)
            <label class="flex items-center gap-2 rounded-xl border border-slate-300 dark:border-white/10 px-3 py-2">
              <input type="radio" name="plan" value="{{ $p->slug }}" {{ old('plan','starter')===$p->slug?'checked':'' }}>
              <span class="text-sm">{{ $p->name }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <div class="md:col-span-2 flex items-center gap-2">
        <input id="agree" type="checkbox" name="agree" value="1" required>
        <label for="agree" class="text-sm">{{ __('signup.agree') }}</label>
      </div>

      <div class="md:col-span-2">
        <button class="woork-btn-primary w-full rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2">
          {{ __('signup.submit') }}
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
