@extends('layouts.app')
@section('title', __('dashboard.reports'))
@section('page', __('dashboard.reports'))
@section('content')
<div class="space-y-6">
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
        <div>
          <label class="block text-sm mb-1">{{ __('dashboard.report_date') }}</label>
          <input type="date" name="date" value="{{ $reportDate->toDateString() }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-800 shadow-sm dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700">
        </div>
        <button class="rounded-xl bg-emerald-600 text-white px-4 py-2">{{ __('woork.submit') }}</button>
      </form>

      <div class="flex flex-wrap gap-2">
        <a href="{{ route('export.daily.csv', ['date' => $reportDate->toDateString(), 'scope' => 'organization']) }}" class="rounded-xl border px-3 py-2 text-sm dark:border-white/10">
          {{ __('dashboard.report_export_organization') }}
        </a>
        <a href="{{ route('export.daily.csv', ['date' => $reportDate->toDateString(), 'scope' => 'system']) }}" class="rounded-xl border px-3 py-2 text-sm dark:border-white/10">
          {{ __('dashboard.report_export_system') }}
        </a>
        <a href="{{ route('export.daily.csv', ['date' => $reportDate->toDateString(), 'scope' => 'employees']) }}" class="rounded-xl border px-3 py-2 text-sm dark:border-white/10">
          {{ __('dashboard.report_export_employees') }}
        </a>
        <a href="{{ route('export.daily.csv', ['date' => $reportDate->toDateString(), 'scope' => 'rooms']) }}" class="rounded-xl border px-3 py-2 text-sm dark:border-white/10">
          {{ __('dashboard.report_export_rooms') }}
        </a>
      </div>
    </div>
  </div>

  <div class="grid gap-4 md:grid-cols-4">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.report_org_avg_score') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $organizationReport['avg_score'] }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.report_org_utilization') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $organizationReport['utilization'] }}%</div>
    </div>
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.report_org_phone_rate') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $organizationReport['phone_rate'] }}</div>
    </div>
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.report_org_away_rate') }}</div>
      <div class="mt-2 text-2xl font-semibold">{{ $organizationReport['away_rate'] }}</div>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold">{{ __('dashboard.report_week_comparison') }}</h3>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.report_comparison_current_vs_previous') }}</span>
      </div>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        @foreach(['score' => __('dashboard.table_score'), 'work' => __('dashboard.table_work'), 'phone' => __('dashboard.table_phone'), 'away' => __('dashboard.table_away')] as $key => $label)
          <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4 text-sm">
            <div class="text-slate-500 dark:text-slate-400">{{ $label }}</div>
            <div class="mt-2 text-lg font-semibold">{{ $comparisons['week'][$key]['current'] }}</div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              {{ __('dashboard.report_previous_period') }}: {{ $comparisons['week'][$key]['previous'] }}
              ·
              <span class="{{ $comparisons['week'][$key]['delta'] >= 0 ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">
                {{ $comparisons['week'][$key]['delta'] > 0 ? '+' : '' }}{{ $comparisons['week'][$key]['delta'] }}
              </span>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold">{{ __('dashboard.report_month_comparison') }}</h3>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.report_comparison_current_vs_previous') }}</span>
      </div>
      <div class="mt-4 grid gap-3 md:grid-cols-2">
        @foreach(['score' => __('dashboard.table_score'), 'work' => __('dashboard.table_work'), 'phone' => __('dashboard.table_phone'), 'away' => __('dashboard.table_away')] as $key => $label)
          <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4 text-sm">
            <div class="text-slate-500 dark:text-slate-400">{{ $label }}</div>
            <div class="mt-2 text-lg font-semibold">{{ $comparisons['month'][$key]['current'] }}</div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              {{ __('dashboard.report_previous_period') }}: {{ $comparisons['month'][$key]['previous'] }}
              ·
              <span class="{{ $comparisons['month'][$key]['delta'] >= 0 ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">
                {{ $comparisons['month'][$key]['delta'] > 0 ? '+' : '' }}{{ $comparisons['month'][$key]['delta'] }}
              </span>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr]">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold">{{ __('dashboard.report_weekly_trend') }}</h3>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ $reportDate->format('M Y') }}</span>
      </div>
      <div id="chart-reports-weekly" class="h-[260px] mt-3"></div>
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <h3 class="font-semibold">{{ __('dashboard.report_system_health') }}</h3>
      <div class="mt-4 space-y-3 text-sm">
        <div class="flex items-center justify-between rounded-xl border border-slate-200/70 dark:border-white/10 p-3">
          <span>{{ __('dashboard.camera_total') }}</span>
          <span class="font-semibold">{{ $systemReport['camera_total'] }}</span>
        </div>
        <div class="flex items-center justify-between rounded-xl border border-slate-200/70 dark:border-white/10 p-3">
          <span>{{ __('dashboard.camera_online') }}</span>
          <span class="font-semibold">{{ $systemReport['camera_online'] }}</span>
        </div>
        <div class="flex items-center justify-between rounded-xl border border-slate-200/70 dark:border-white/10 p-3">
          <span>{{ __('dashboard.camera_warning') }}</span>
          <span class="font-semibold">{{ $systemReport['camera_warning'] }}</span>
        </div>
        <div class="flex items-center justify-between rounded-xl border border-slate-200/70 dark:border-white/10 p-3">
          <span>{{ __('dashboard.camera_offline') }}</span>
          <span class="font-semibold">{{ $systemReport['camera_offline'] }}</span>
        </div>
        <div class="flex items-center justify-between rounded-xl border border-slate-200/70 dark:border-white/10 p-3">
          <span>{{ __('dashboard.report_operational_alerts') }}</span>
          <span class="font-semibold">{{ $systemReport['operational_alerts'] }}</span>
        </div>
        <div class="flex items-center justify-between rounded-xl border border-slate-200/70 dark:border-white/10 p-3">
          <span>{{ __('dashboard.report_detector_fallbacks') }}</span>
          <span class="font-semibold">{{ $systemReport['detector_fallbacks'] }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">{{ __('dashboard.report_employees') }}</h3>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ count($employeeReport['rows']) }} {{ __('dashboard.table_employee') }}</span>
      </div>
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="text-slate-500 dark:text-slate-400">
            <tr>
              <th class="py-2 text-start">{{ __('dashboard.table_employee') }}</th>
              <th class="py-2 text-start">{{ __('woork.room') }}</th>
              <th class="py-2 text-end">{{ __('dashboard.table_work') }}</th>
              <th class="py-2 text-end">{{ __('dashboard.table_phone') }}</th>
              <th class="py-2 text-end">{{ __('dashboard.table_away') }}</th>
              <th class="py-2 text-end">{{ __('dashboard.table_score') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
            @forelse($employeeReport['rows'] as $row)
              <tr>
                <td class="py-2">{{ $row['name'] }}</td>
                <td class="py-2">{{ $row['room'] }}</td>
                <td class="py-2 text-end">{{ $row['work_minutes'] }}</td>
                <td class="py-2 text-end">{{ $row['phone_minutes'] }}</td>
                <td class="py-2 text-end">{{ $row['away_minutes'] }}</td>
                <td class="py-2 text-end font-semibold">{{ $row['score'] }}</td>
              </tr>
            @empty
              <tr><td colspan="6" class="py-6 text-center text-slate-400">{{ __('dashboard.reports_empty') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">{{ __('dashboard.report_rooms') }}</h3>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ count($roomReport) }} {{ __('woork.rooms') }}</span>
      </div>
      <div class="overflow-auto">
        <table class="min-w-full text-sm">
          <thead class="text-slate-500 dark:text-slate-400">
            <tr>
              <th class="py-2 text-start">{{ __('woork.room') }}</th>
              <th class="py-2 text-end">{{ __('dashboard.active_employees') }}</th>
              <th class="py-2 text-end">{{ __('dashboard.table_work') }}</th>
              <th class="py-2 text-end">{{ __('dashboard.table_idle') }}</th>
              <th class="py-2 text-end">{{ __('dashboard.table_score') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
            @forelse($roomReport as $row)
              <tr>
                <td class="py-2">{{ $row['name'] }}</td>
                <td class="py-2 text-end">{{ $row['employees'] }}</td>
                <td class="py-2 text-end">{{ $row['work_minutes'] }}</td>
                <td class="py-2 text-end">{{ $row['idle_minutes'] }}</td>
                <td class="py-2 text-end font-semibold">{{ $row['avg_score'] }}</td>
              </tr>
            @empty
              <tr><td colspan="5" class="py-6 text-center text-slate-400">{{ __('dashboard.reports_empty') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <h3 class="font-semibold mb-3">{{ __('dashboard.report_top_employee') }}</h3>
      <div class="space-y-3">
        @forelse($employeeReport['top'] as $row)
          <div class="flex items-center justify-between rounded-xl border border-slate-200/70 dark:border-white/10 p-3 text-sm">
            <div>
              <div class="font-medium">{{ $row['name'] }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">{{ $row['room'] }}</div>
            </div>
            <div class="font-semibold">{{ $row['score'] }}</div>
          </div>
        @empty
          <div class="text-sm text-slate-400">{{ __('dashboard.reports_empty') }}</div>
        @endforelse
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 p-5">
      <h3 class="font-semibold mb-3">{{ __('dashboard.report_low_employee') }}</h3>
      <div class="space-y-3">
        @forelse($employeeReport['bottom'] as $row)
          <div class="flex items-center justify-between rounded-xl border border-slate-200/70 dark:border-white/10 p-3 text-sm">
            <div>
              <div class="font-medium">{{ $row['name'] }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">{{ $row['room'] }}</div>
            </div>
            <div class="font-semibold">{{ $row['score'] }}</div>
          </div>
        @empty
          <div class="text-sm text-slate-400">{{ __('dashboard.reports_empty') }}</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  <script>
    const weeklyLabels = {!! json_encode($weeklyLabels) !!};
    const weeklyScores = {!! json_encode($weeklyScores) !!};

    new ApexCharts(document.querySelector("#chart-reports-weekly"), {
      chart:{ type:'area', height:260, toolbar:{show:false}, fontFamily:'ui-sans-serif, system-ui' },
      series:[{ name:'Score', data: weeklyScores }],
      xaxis:{ categories: weeklyLabels, labels:{ style:{ colors: axisColor() }}},
      yaxis:{ labels:{ style:{ colors: axisColor() }}},
      colors:['#10b981'],
      dataLabels:{ enabled:false },
      stroke:{ width:2, curve:'smooth' },
      fill:{ type:'gradient', gradient:{ shadeIntensity:1, opacityFrom:0.35, opacityTo:0.05, stops:[0,90,100]}},
      grid:{ borderColor:gridColor(), strokeDashArray:4 }
    }).render();

    function axisColor(){ return document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569'; }
    function gridColor(){ return document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,.06)' : 'rgba(15,23,42,.06)'; }
  </script>
@endpush
