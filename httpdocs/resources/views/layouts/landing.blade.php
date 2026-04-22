<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}"
      x-data="{open:false, dark: localStorage.theme==='dark'}"
      x-init="document.documentElement.classList.toggle('dark', dark)"
      x-on:darkmode.window="dark=!dark; localStorage.theme=dark?'dark':'light'; document.documentElement.classList.toggle('dark', dark)">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title','Woork — From video to numbers')</title>
  <meta name="description" content="@yield('meta_description','Privacy-first workplace analytics.')">
  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="#10b981">
  <link rel="icon" href="{{ asset('assets/favicon.ico') }}">

  {{-- عناوين أنيقة للغات العربية/اللاتينية --}}
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@500;700&family=Poppins:wght@500;700&display=swap">
  <style>
    :root { --brand: #10b981; }
    h1,h2,h3,.brand-heading {
      font-family: {{ app()->isLocale('ar') ? "'Cairo', sans-serif" : "'Poppins', system-ui, sans-serif" }};
    }
  </style>

  @vite(['resources/css/app.css','resources/js/app.js'])
  <script src="https://unpkg.com/alpinejs@3.x.x" defer></script>
</head>

<body class="bg-white text-slate-800 dark:bg-slate-950 dark:text-slate-100 antialiased">
  <header class="fixed inset-x-0 top-0 z-50">
    <div class="mx-auto max-w-7xl px-4">
      <div class="mt-4 rounded-2xl border border-slate-200/70 dark:border-white/10
                  bg-white/70 dark:bg-white/[0.04] backdrop-blur shadow-lg">
        <div class="h-14 px-3 flex items-center justify-between">

          {{-- الشعار --}}
          <a href="{{ route('landing') }}" class="flex items-center gap-2 mt-[2px]">
            <img src="{{ asset('assets/img/woork-logo.svg') }}?v=5"
                 class="h-10 md:h-12 w-auto block"
                 alt="woork logo">
          </a>

          {{-- نافبار دسكتوب --}}
          <nav class="hidden md:flex items-center gap-6 text-sm">
            <a href="#features" class="hover:text-emerald-600 dark:hover:text-emerald-400">{{ __('public.nav.features') }}</a>
            <a href="#pricing"  class="hover:text-emerald-600 dark:hover:text-emerald-400">{{ __('public.nav.pricing') }}</a>
            <a href="#faq"      class="hover:text-emerald-600 dark:hover:text-emerald-400">{{ __('public.nav.faq') }}</a>

            {{-- مبدّل اللغات يحافظ على نفس الرابط مع تبديل ?lang --}}
            @php $langs = ['en'=>'EN','ar'=>'AR','tr'=>'TR']; @endphp
            @foreach($langs as $code => $label)
              <a href="{{ request()->fullUrlWithQuery(['lang'=>$code]) }}"
                 class="{{ app()->getLocale()===$code ? 'font-semibold underline underline-offset-4' : '' }}">
                {{ $label }}
              </a>
            @endforeach

           
		
			  
			  
            <a href="{{ route('login') }}"
              class="rounded-lg border px-3 py-1.5 text-xs hover:bg-slate-100 dark:border-white/10 dark:hover:bg-white/10">
              {{ __('auth.login.title') }}
            </a>

            {{-- زر ابدأ بخلفية صلبة لتفادي مشاكل كروم --}}
            <a href="{{ route('signup') }}"
               class="woork-btn-primary rounded-lg bg-emerald-600 hover:bg-emerald-500
                      text-white px-4 py-2 font-medium shadow-md shadow-emerald-600/30">
              {{ __('public.nav.start') }}
            </a>
          </nav>

        
          <button class="md:hidden p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-white/10" @click="open=!open" aria-label="Menu">
            <x-icon name="menu" class="h-5 w-5"/>
          </button>
        </div>

        {{-- قائمة الموبايل --}}
        <div class="md:hidden border-t border-slate-200/70 dark:border-white/10 px-3 py-3" x-show="open" x-collapse>
          <div class="flex flex-col gap-2 text-sm">
            <a href="#features" class="py-1">{{ __('public.nav.features') }}</a>
            <a href="#pricing"  class="py-1">{{ __('public.nav.pricing') }}</a>
            <a href="#faq"      class="py-1">{{ __('public.nav.faq') }}</a>

            <div class="flex gap-3 items-center py-2">
              @foreach($langs as $code => $label)
                <a href="{{ request()->fullUrlWithQuery(['lang'=>$code]) }}"
                   class="{{ app()->getLocale()===$code ? 'font-semibold underline underline-offset-4' : '' }}">
                  {{ $label }}
                </a>
              @endforeach
              <button class="ms-auto rounded-lg border px-2 py-1 text-xs dark:border-white/10"
                      x-on:click="$dispatch('darkmode')">
                {{ __('public.nav.theme') }}
              </button>
            </div>

            <a href="{{ route('login') }}"
               class="woork-btn-primary rounded-lg bg-emerald-600 text-white px-4 py-2 text-center">
              {{ __('public.nav.start') }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  {{-- ✅ عزل المحتوى لمنع مشاكل التراص مع البلور في كروم --}}
  <main class="pt-28 isolate">@yield('content')</main>

  <footer class="mt-20">
    <div class="mx-auto max-w-7xl px-4 py-10 text-sm text-slate-500 dark:text-slate-400
                flex flex-col md:flex-row items-center justify-between gap-4
                border-t border-slate-200/70 dark:border-white/10">
      <div>©️ {{ date('Y') }} Woork</div>
      <div class="flex gap-6">
        <a href="/privacy">{{ __('public.footer.privacy') }}</a>
        <a href="/terms">{{ __('public.footer.terms') }}</a>
        <a href="/contact">{{ __('public.footer.contact') }}</a>
      </div>
    </div>
  </footer>
</body>
</html>
