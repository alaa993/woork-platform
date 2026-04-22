<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar')?'rtl':'ltr' }}"
  x-data="{dark:localStorage.theme==='dark'}"
  x-init="document.documentElement.classList.toggle('dark', dark)"
  x-on:darkmode.window="dark=!dark; localStorage.theme=dark?'dark':'light'; document.documentElement.classList.toggle('dark', dark)">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Woork')</title>
  <meta name="description" content="@yield('meta_description','AI that tracks work — not people.')">
  <link rel="icon" href="{{ asset('assets/favicon.ico') }}">
  @vite(['resources/css/app.css','resources/js/app.js'])
  <script src="https://unpkg.com/alpinejs@3.x.x" defer></script>
</head>
<body class="min-h-screen bg-zinc-50 text-zinc-800 dark:bg-zinc-950 dark:text-zinc-100 antialiased">
  <header class="sticky top-0 z-40 bg-white/80 dark:bg-zinc-900/80 backdrop-blur border-b border-zinc-200/60 dark:border-white/10">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
      <a href="{{ route('landing') }}" class="flex items-center gap-3">
        <img src="{{ asset('assets/woork-logo.png') }}" class="h-8 w-8 rounded-md" alt="woork"/>
        <span class="font-bold text-lg">woork</span>
      </a>
      <nav class="hidden md:flex items-center gap-4 text-sm">
        <a href="{{ route('landing') }}?lang=en" class="hover:text-emerald-600">EN</a>
        <a href="{{ route('landing') }}?lang=ar" class="hover:text-emerald-600">AR</a>
        <a href="{{ route('landing') }}?lang=tr" class="hover:text-emerald-600">TR</a>
        <x-button variant="outline" x-on:click="$dispatch('darkmode')">Toggle theme</x-button>
        <x-button as="a" href="{{ route('login') }}">Start free trial</x-button>
      </nav>
    </div>
  </header>
  <main>@yield('content')</main>
  <footer class="mt-20 border-t border-zinc-200/60 dark:border-white/10">
    <div class="max-w-7xl mx-auto px-4 py-8 text-sm text-zinc-500 dark:text-zinc-400 flex flex-col md:flex-row gap-4 md:items-center md:justify-between">
      <div>© {{ date('Y') }} Woork, Inc.</div>
      <div class="flex gap-4"><a href="/privacy">Privacy</a><a href="/terms">Terms</a></div>
    </div>
  </footer>
</body>
</html>
