@props(['headers'=>[]])
<div class="overflow-auto rounded-2xl border border-slate-200 bg-white/90 shadow-sm dark:bg-slate-900/80 dark:border-slate-700">
  <table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
      <tr>
        @foreach($headers as $h)
          <th class="px-3 py-2 text-start font-medium">{{ $h }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
      {{ $slot }}
    </tbody>
  </table>
</div>
