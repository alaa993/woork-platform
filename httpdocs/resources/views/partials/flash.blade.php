@if (session('ok'))
  <div class="mb-3 rounded-lg border border-emerald-300/60 bg-emerald-50 text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-900/30 dark:text-emerald-200 px-3 py-2 text-sm">
    {{ session('ok') }}
  </div>
@endif

@if (session('error'))
  <div class="mb-3 rounded-lg border border-rose-300/60 bg-rose-50 text-rose-800 dark:border-rose-500/40 dark:bg-rose-900/30 dark:text-rose-200 px-3 py-2 text-sm">
    {{ session('error') }}
  </div>
@endif

@if (session('status'))
  <div class="mb-3 rounded-lg border border-emerald-300/60 bg-emerald-50 text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-900/30 dark:text-emerald-200 px-3 py-2 text-sm">
    {{ session('status') }}
  </div>
@endif

@if ($errors->any())
  <div class="mb-3 rounded-lg border border-rose-300/60 bg-rose-50 text-rose-800 dark:border-rose-500/40 dark:bg-rose-900/30 dark:text-rose-200 px-3 py-2 text-sm">
    <ul class="list-disc ms-5">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
