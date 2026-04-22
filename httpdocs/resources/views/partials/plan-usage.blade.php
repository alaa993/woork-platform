@php($resources = ['cameras' => __('woork.cameras'), 'employees' => __('woork.employees'), 'agent_devices' => __('dashboard.agent_devices_title')])

@if(!empty($usage))
  <div class="mb-4 rounded-2xl border border-slate-200/70 dark:border-white/10 bg-slate-50/80 dark:bg-white/[0.03] p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold">{{ __('woork.plan_usage') }}</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          {{ __('woork.current_plan') }}:
          <span class="font-medium text-slate-700 dark:text-slate-200">{{ $usage['plan']?->name ?? '—' }}</span>
        </p>
      </div>
      <a href="{{ route('subscription.index') }}" class="rounded-xl border px-3 py-1.5 text-xs dark:border-white/10">
        {{ __('woork.subscription_manage') }}
      </a>
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-3">
      @foreach($resources as $key => $label)
        @php($item = $usage[$key] ?? null)
        @if($item)
          <div class="rounded-xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/[0.03] p-3 text-sm">
            <div class="font-medium">{{ $label }}</div>
            <div class="mt-2 text-slate-500 dark:text-slate-400">
              {{ __('woork.used') }}: <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $item['used'] }}</span>
            </div>
            <div class="text-slate-500 dark:text-slate-400">
              {{ __('woork.limit') }}:
              <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $item['unlimited'] ? __('woork.unlimited') : $item['limit'] }}</span>
            </div>
            <div class="text-slate-500 dark:text-slate-400">
              {{ __('woork.remaining') }}:
              <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $item['unlimited'] ? __('woork.unlimited') : $item['remaining'] }}</span>
            </div>
          </div>
        @endif
      @endforeach
    </div>
  </div>
@endif
