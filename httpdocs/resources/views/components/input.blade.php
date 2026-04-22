@props(['type'=>'text','name'=>null,'value'=>null,'placeholder'=>null])
<input type="{{ $type }}" name="{{ $name }}" value="{{ old($name,$value) }}"
  placeholder="{{ $placeholder }}"
  {{ $attributes->merge(['class'=>'w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-300 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700']) }}>
