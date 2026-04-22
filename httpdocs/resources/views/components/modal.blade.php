@props(['open'=>false,'title'=>null])
<div x-data="{open:@js($open)}">
  <div x-show="open" class="fixed inset-0 z-50 bg-black/30 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
    <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-xl">
      <div class="px-5 pt-4 flex items-center justify-between">
        <div class="text-sm font-semibold">{{ $title }}</div>
        <button class="px-3 py-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800" @click="open=false">✕</button>
      </div>
      <div class="p-5">{{ $slot }}</div>
    </div>
  </div>
</div>
