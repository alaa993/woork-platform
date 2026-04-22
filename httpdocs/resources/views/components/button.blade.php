@props(['variant'=>'primary','size'=>'md','as'=>null,'href'=>null,'type'=>null])
@php
  $base='inline-flex items-center justify-center gap-2 rounded-xl font-medium transition focus:outline-none focus:ring-2 ring-emerald-300';
  $sizes=['sm'=>'px-3 py-1.5 text-sm','md'=>'px-4 py-2 text-sm','lg'=>'px-5 py-3 text-base'];
  $variants=[
    'primary'=>'woork-btn-primary bg-emerald-600 text-white hover:bg-emerald-500 active:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400',
    'outline'=>'border border-slate-300 hover:bg-slate-50 text-slate-700 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800',
    'ghost'=>'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800',
    'danger'=>'bg-rose-600 text-white hover:bg-rose-500'
  ];
  $classes=$base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
  $tag=$as ?? ($href ? 'a':'button');
@endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif @if($type) type="{{ $type }}" @endif {{ $attributes->merge(['class'=>$classes]) }}>
  {{ $slot }}
</{{ $tag }}>
