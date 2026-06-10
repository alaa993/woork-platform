@extends('layouts.landing')
@section('title', __('public.footer.contact'))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-6">
    <h1 class="text-2xl font-semibold mb-4">{{ __('public.footer.contact') }}</h1>
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">Reach Woork support for onboarding, billing, or agent installation help.</p>
    <p class="text-sm"><strong>Email:</strong> support@woork.site</p>
    <p class="text-sm mt-2"><strong>Website:</strong> <a class="text-emerald-600" href="https://woork.site">woork.site</a></p>
  </div>
</div>
@endsection
