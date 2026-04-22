<!doctype html>
<html lang="{{ app()->getLocale() }}" x-data="{dark:localStorage.theme==='dark'}" x-init="document.documentElement.classList.toggle('dark', dark)"
      x-on:darkmode.window="dark=!dark; localStorage.theme=dark?'dark':'light'; document.documentElement.classList.toggle('dark', dark)">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Woork')</title>
  <meta name="description" content="@yield('meta_description','AI that tracks work — not people.')">
  <link rel="icon" href="{{ asset('assets/favicon.ico') }}">
  @vite(['resources/css/app.css','resources/js/app.js'])
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script src="https://unpkg.com/alpinejs@3.x.x" defer></script>
</head>
<body class="min-h-screen bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-emerald-50 via-slate-50 to-white text-slate-800 dark:from-slate-950 dark:via-slate-950 dark:to-slate-950 dark:text-slate-100">
  <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200/60 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 h-16 flex items-center justify-between">
      <a href="/" class="flex items-center gap-3">
        <img src="{{ asset('assets/img/woork-logo.jpg') }}" class="h-8 w-8 rounded-md" alt="woork"/>
        
      </a>
      <nav class="hidden md:flex items-center gap-6 text-sm">
        <a href="/features" class="hover:text-slate-900 dark:hover:text-white">Features</a>
        <a href="/pricing" class="hover:text-slate-900 dark:hover:text-white">Pricing</a>
        <a href="/about" class="hover:text-slate-900 dark:hover:text-white">About</a>
        <a href="/contact" class="hover:text-slate-900 dark:hover:text-white">Contact</a>
        <a href="{{ route('login') }}" class="hover:text-slate-900 dark:hover:text-white">Sign in</a>
        <x-button as="a" href="{{ route('login') }}">Start free trial</x-button>
        <x-button variant="outline" x-on:click="$dispatch('darkmode')">Toggle theme</x-button>
      </nav>
    </div>
  </header>
  <main>@yield('content')</main>
  <footer class="mt-20 border-t border-slate-200 dark:border-slate-800">
    <div class="max-w-7xl mx-auto px-4 py-10 text-sm text-slate-500 dark:text-slate-400 flex flex-col md:flex-row gap-4 md:items-center md:justify-between">
      <div>© {{ date('Y') }} Woork, Inc.</div>
      <div class="flex gap-4"><a href="/privacy">Privacy</a><a href="/terms">Terms</a><a href="/cookies">Cookies</a></div>
    </div>
  </footer>
</body>
</html>
