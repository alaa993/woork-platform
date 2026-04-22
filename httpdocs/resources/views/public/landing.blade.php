@extends('layouts.landing')
@section('title','Woork — From video to numbers.')
@section('content')
<section class="relative">
  <div class="mx-auto max-w-7xl px-4 grid lg:grid-cols-2 gap-10 items-center">
    <div class="space-y-6">
      <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 px-3 py-1 text-xs">
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> {{ __('public.hero.badge') }}
      </div>
      <h1 class="text-4xl md:text-5xl font-extrabold leading-tight">{!! __('public.hero.title_html') !!}</h1>
      <p class="text-lg text-slate-600 dark:text-slate-300">{!! __('public.hero.subtitle_html') !!}</p>
      <div class="flex flex-wrap items-center gap-3">
        <a href="{{ route('signup') }}" class="woork-btn-primary rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-500 hover:to-emerald-400 text-white px-6 py-3 text-sm font-semibold shadow-lg shadow-emerald-600/30 transition-all">{{ __('public.hero.cta_start') }}</a>
        <a href="#pricing" class="rounded-2xl border border-slate-300 dark:border-white/10 px-5 py-3 text-sm hover:bg-slate-100 dark:hover:bg-white/5 shadow-sm">{{ __('public.hero.cta_pricing') }}</a>
      </div>
      <div class="grid grid-cols-2 gap-4 pt-2 text-sm">
        <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-4">{{ __('public.hero.pill1') }}</div>
        <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-4">{{ __('public.hero.pill2') }}</div>
      </div>
    </div>
    <div class="relative">
      <div class="absolute -inset-6 rounded-3xl bg-emerald-500/10 blur-2xl -z-10"></div>
      <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-3 shadow-2xl">
        <img src="{{ asset('assets/img/as.jpg') }}" alt="Dashboard" class="rounded-xl w-full object-cover">
      </div>
      <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 text-center">{{ __('public.hero.caption') }}</div>
    </div>
  </div>
</section>

<section id="features" class="mx-auto max-w-7xl px-4 py-16">
  <h2 class="text-2xl md:text-3xl font-semibold tracking-tight mb-8">{{ __('public.why.title') }}</h2>
  <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @php $features=[
      [__('public.why.ai'), __('public.why.ai_desc')],
      [__('public.why.privacy'), __('public.why.privacy_desc')],
      [__('public.why.realtime'), __('public.why.realtime_desc')],
      [__('public.why.alerts'), __('public.why.alerts_desc')],
    ]; @endphp
    @foreach($features as [$title,$desc])
      <div class="group rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-6 hover:shadow-xl transition-shadow">
        <div class="mb-3 h-8 w-8 rounded-md bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
          <x-icon name="dashboard" class="h-4 w-4"/>
        </div>
        <h3 class="font-medium mb-1">{{ $title }}</h3>
        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $desc }}</p>
      </div>
    @endforeach
  </div>
</section>

<section id="platform" class="mx-auto max-w-7xl px-4 py-16">
  <div class="text-center max-w-3xl mx-auto">
    <h2 class="text-2xl md:text-3xl font-semibold tracking-tight mb-2">{{ __('public.platform.title') }}</h2>
    <p class="text-sm text-slate-600 dark:text-slate-300 mb-10">{{ __('public.platform.caption') }}</p>
  </div>

  <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
    @php
      $platformCards = [
        [
          'icon' => 'camera',
          'title' => __('public.platform.agent_title'),
          'desc' => __('public.platform.agent_desc'),
        ],
        [
          'icon' => 'bell',
          'title' => __('public.platform.alerts_title'),
          'desc' => __('public.platform.alerts_desc'),
        ],
        [
          'icon' => 'policy',
          'title' => __('public.platform.compliance_title'),
          'desc' => __('public.platform.compliance_desc'),
        ],
      ];
    @endphp

    @foreach($platformCards as $card)
      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-6 space-y-3 shadow-lg">
        <div class="h-10 w-10 rounded-xl bg-emerald-500/15 border border-emerald-500/40 flex items-center justify-center text-emerald-600 dark:text-emerald-300">
          <x-icon :name="$card['icon']" class="h-5 w-5"/>
        </div>
        <h3 class="font-semibold text-lg">{{ $card['title'] }}</h3>
        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $card['desc'] }}</p>
      </div>
    @endforeach
  </div>

  <div class="text-center mt-10">
    <a href="#pricing" class="woork-link-accent inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 hover:text-emerald-500">
      {{ __('public.platform.cta') }}
      <x-icon name="export" class="h-4 w-4"/>
    </a>
  </div>
</section>

<section id="pricing" class="mx-auto max-w-7xl px-4 pb-16">
  <h2 class="text-2xl md:text-3xl font-semibold tracking-tight mb-8">{{ __('public.pricing.title') }}</h2>
  <div class="grid md:grid-cols-3 gap-6">
    @php $plans=[
      ['name'=>__('public.pricing.starter.name'),'tag'=>__('public.pricing.starter.tag'),'price'=>'0','limit'=>__('public.pricing.starter.limit'),'perk'=>__('public.pricing.starter.perk'),'link'=>'/login'],
      ['name'=>__('public.pricing.pro.name'),'tag'=>__('public.pricing.pro.tag'),'price'=>'39','limit'=>__('public.pricing.pro.limit'),'perk'=>__('public.pricing.pro.perk'),'link'=>'/login'],
      ['name'=>__('public.pricing.enterprise.name'),'tag'=>__('public.pricing.enterprise.tag'),'price'=>'—','limit'=>__('public.pricing.enterprise.limit'),'perk'=>__('public.pricing.enterprise.perk'),'link'=>'/login'],
    ]; @endphp
    @foreach($plans as $p)
      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur p-6 flex flex-col">
        <div class="text-xs text-emerald-700 dark:text-emerald-300">{{ $p['tag'] }}</div>
        <h3 class="mt-1 text-xl font-semibold">{{ $p['name'] }}</h3>
        <div class="mt-4 text-4xl font-semibold tracking-tight">@if($p['price']==='—')<span class="text-lg">{{ __('public.pricing.contact') }}</span>@else ${{ $p['price'] }}<span class="text-sm text-slate-500 dark:text-slate-400">/mo</span>@endif</div>
        <ul class="mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300">
          <li>• {{ $p['limit'] }}</li>
          <li>• {{ $p['perk'] }}</li>
          <li>• {{ __('public.pricing.privacy') }}</li>
        </ul>
        <a href="{{ $p['link'] }}" class="woork-btn-primary mt-6 inline-flex justify-center rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2">{{ __('public.pricing.cta') }}</a>
      </div>
    @endforeach
  </div>
</section>

<section id="faq" class="mx-auto max-w-5xl px-4 pb-16">
  <h2 class="text-2xl md:text-3xl font-semibold tracking-tight mb-8">{{ __('public.faq.title') }}</h2>
  <div class="divide-y divide-slate-200/70 dark:divide-white/10 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/60 dark:bg-white/5 backdrop-blur">
    <details class="p-6" open>
      <summary class="cursor-pointer font-medium">{{ __('public.faq.q1') }}</summary>
      <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('public.faq.a1') }}</p>
    </details>
    <details class="p-6">
      <summary class="cursor-pointer font-medium">{{ __('public.faq.q2') }}</summary>
      <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('public.faq.a2') }}</p>
    </details>
    <details class="p-6">
      <summary class="cursor-pointer font-medium">{{ __('public.faq.q3') }}</summary>
      <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ __('public.faq.a3') }}</p>
    </details>
  </div>
</section>
@endsection
