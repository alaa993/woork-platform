@props(['color'=>'emerald'])
<span {{ $attributes->merge(['class'=>"inline-flex items-center rounded-full px-2 py-0.5 text-xs bg-$color-100 text-$color-700 dark:bg-$color-900/30 dark:text-$color-300"]) }}>{{ $slot }}</span>
