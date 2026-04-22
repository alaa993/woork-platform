<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar')?'rtl':'ltr' }}"
  x-data="{dark:localStorage.theme==='dark', open:false}"
  x-init="document.documentElement.classList.toggle('dark', dark)"
  x-on:darkmode.window="dark=!dark; localStorage.theme=dark?'dark':'light'; document.documentElement.classList.toggle('dark', dark)">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Woork Console')</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <script src="https://unpkg.com/alpinejs@3.x.x" defer></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-100 antialiased">
  <header class="sticky top-0 z-40 bg-white/70 dark:bg-white/[0.04] backdrop-blur border-b border-slate-200/70 dark:border-white/10">
    <div class="max-w-7xl mx-auto h-14 px-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button class="md:hidden p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" @click="open=!open" aria-label="Toggle menu"><x-icon name="menu"/></button>
         <a href="{{ route('landing') }}" class="flex items-center gap-2 mt-[2px]">
            <img src="{{ asset('assets/img/woork-logo.svg') }}?v=5"
                 class="h-10 md:h-12 w-auto block"
                 alt="woork logo">
          </a>
        <span class="hidden sm:inline text-xs text-slate-500 dark:text-slate-400 border-s ms-3 ps-3">Console</span>
      </div>
      <div class="flex items-center gap-2">
<div class="hidden md:flex items-center gap-2">
  <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}"
     class="rounded-lg border px-2 py-1 text-xs dark:border-white/10 {{ app()->getLocale() === 'en' ? 'bg-emerald-600 text-white' : '' }}">
     EN
  </a>
  <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}"
     class="rounded-lg border px-2 py-1 text-xs dark:border-white/10 {{ app()->getLocale() === 'ar' ? 'bg-emerald-600 text-white' : '' }}">
     AR
  </a>
  <a href="{{ request()->fullUrlWithQuery(['lang' => 'tr']) }}"
     class="rounded-lg border px-2 py-1 text-xs dark:border-white/10 {{ app()->getLocale() === 'tr' ? 'bg-emerald-600 text-white' : '' }}">
     TR
  </a>
</div>
		  
        <button class="rounded-lg border px-2 py-1 text-xs hover:bg-slate-100 dark:border-white/10 dark:hover:bg-white/10" x-on:click="$dispatch('darkmode')">Theme</button>
        <form method="POST" action="{{ route('logout') }}">@csrf
          <button class="rounded-lg border px-3 py-1.5 text-xs hover:bg-slate-100 dark:border-white/10 dark:hover:bg-white/10">Logout</button>
        </form>
      </div>
    </div>
  </header>

  <div class="max-w-7xl mx-auto grid md:grid-cols-[240px_1fr] gap-4 px-4 py-4">
    <!-- Sidebar -->
    <aside class="md:sticky md:top-16 md:h-[calc(100dvh-6rem)] p-3 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/[0.04] backdrop-blur shadow-sm"
           :class="{'hidden': !open, 'block': open, 'md:block': true}">
      @php($role = auth()->user()->role ?? 'company_admin')
   
      <nav class="text-sm">
        @foreach($menu as $item)
          @php($menuPrefix = \Illuminate\Support\Str::before($item['route'], '.'))
          @php($active = request()->routeIs($item['route']) || request()->routeIs($menuPrefix.'.*'))
          <a href="{{ route($item['route']) }}"
             class="flex items-center gap-2 px-3 py-2 rounded-xl my-1 hover:bg-slate-100 dark:hover:bg-white/10 {{ $active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : '' }}">
            <x-icon :name="$item['icon']" class="h-4 w-4"/>
            <span>{{ __($item['label']) }}</span>
          </a>
        @endforeach
		   <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}"
     class="rounded-lg border px-2 py-1 text-xs dark:border-white/10 {{ app()->getLocale() === 'en' ? 'bg-emerald-600 text-white' : '' }}">
     EN
  </a>
  <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}"
     class="rounded-lg border px-2 py-1 text-xs dark:border-white/10 {{ app()->getLocale() === 'ar' ? 'bg-emerald-600 text-white' : '' }}">
     AR
  </a>
  <a href="{{ request()->fullUrlWithQuery(['lang' => 'tr']) }}"
     class="rounded-lg border px-2 py-1 text-xs dark:border-white/10 {{ app()->getLocale() === 'tr' ? 'bg-emerald-600 text-white' : '' }}">
     TR
  </a>
      </nav>
    </aside>

    <!-- Main -->
    <section>
      <div class="mb-3 flex items-center justify-between">
        <h1 class="text-lg font-semibold">@yield('page','')</h1>
        @yield('actions')
      </div>
      @include('partials.flash')
      @yield('content')
    </section>
  </div>
  @stack('scripts')
</body>
</html>
