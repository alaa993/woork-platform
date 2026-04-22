@props(['name'=>null,'rows'=>5,'placeholder'=>null])
<textarea name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}"
 {{ $attributes->merge(['class'=>'w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700']) }}>{{ old($name) }}</textarea>
