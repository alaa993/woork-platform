@props(['label','value','delta'=>null])
<div class="rounded-2xl border border-slate-200 p-5 bg-gradient-to-br from-slate-50 to-white shadow-sm dark:from-slate-900 dark:to-slate-900 dark:border-slate-700">
  <div class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</div>
  <div class="mt-2 text-3xl font-semibold">{{ $value }}</div>
  @if($delta)<div class="mt-1 text-xs text-emerald-600">{{ $delta }}</div>@endif
</div>
