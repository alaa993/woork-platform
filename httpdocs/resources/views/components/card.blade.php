@props(['title'=>null,'subtitle'=>null,'actions'=>null])
<div {{ $attributes->merge(['class'=>'rounded-2xl border border-slate-200/80 bg-white/90 backdrop-blur shadow-sm dark:bg-slate-900/80 dark:border-slate-700']) }}>
  @if($title || $actions)
    <div class="flex items-center justify-between px-5 pt-4">
      <div>
        @if($title)<div class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $title }}</div>@endif
        @if($subtitle)<div class="text-xs text-slate-500">{{ $subtitle }}</div>@endif
      </div>
      <div class="flex items-center gap-2">{{ $actions }}</div>
    </div>
  @endif
  <div class="p-5">{{ $slot }}</div>
</div>
