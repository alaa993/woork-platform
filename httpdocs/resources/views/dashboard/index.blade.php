@extends('layouts.app')
@section('title', __('dashboard.title'))
@section('page', __('dashboard.page_title'))

@section('content')
<div class="mx-auto max-w-7xl px-2 sm:px-4">

  @if(($onboarding['is_complete'] ?? false) === false)
    <div class="mb-6 rounded-2xl border border-emerald-200/70 bg-emerald-50/80 p-5 dark:border-emerald-900/30 dark:bg-emerald-950/20">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ __('dashboard.onboarding_title') }}</div>
          <div class="mt-1 text-sm text-emerald-700/80 dark:text-emerald-200/80">
            {{ __('dashboard.onboarding_progress_text', ['done' => $onboarding['completed_steps'] ?? 0, 'total' => $onboarding['total_steps'] ?? 0]) }}
          </div>
        </div>
        <div class="flex items-center gap-3">
          <div class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ $onboarding['progress_percent'] ?? 0 }}%</div>
          <a href="{{ route('onboarding.index') }}" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm text-white">{{ __('dashboard.getting_started') }}</a>
        </div>
      </div>
    </div>
  @endif

  {{-- Hero --}}
  <div class="relative overflow-hidden rounded-3xl p-6 md:p-8 mb-8 border border-slate-200/70 dark:border-white/10
              bg-white/70 dark:bg-white/5 backdrop-blur shadow-[0_8px_30px_rgba(0,0,0,.06)]">
    <div class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-emerald-500/15 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-24 size-72 rounded-full bg-teal-400/15 blur-3xl"></div>

    <div class="flex items-center justify-between gap-4 animate-[fadeIn_.5s_ease]">
      <div>
        <h1 class="text-2xl md:text-3xl font-bold tracking-tight">
          {{ __('dashboard.welcome_back') }} 👋
        </h1>
        <p class="text-sm text-slate-600 dark:text-slate-300 mt-1">
          {{ __('dashboard.quick_overview') }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <a href="{{ route('cameras.create') }}"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 dark:border-white/10
                  px-3 py-2 text-sm hover:bg-slate-50 dark:hover:bg-white/10 transition">
          <x-icon name="camera" class="w-4 h-4"/>
          {{ __('dashboard.add_camera') }}
        </a>
        <a href="{{ route('employees.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white
                  px-3 py-2 text-sm shadow-md shadow-emerald-600/20 transition">
          <x-icon name="user-add" class="w-4 h-4"/>
          {{ __('dashboard.invite_employee') }}
        </a>
      </div>
    </div>
  </div>

  {{-- Quick actions --}}
  @php
    $quickActions = [
      ['label' => __('dashboard.action_employees'), 'desc' => __('dashboard.quick_actions_desc'), 'route' => route('employees.index'), 'icon' => 'users'],
      ['label' => __('dashboard.action_cameras'), 'desc' => __('dashboard.quick_actions_desc'), 'route' => route('cameras.index'), 'icon' => 'camera'],
      ['label' => __('dashboard.action_policies'), 'desc' => __('dashboard.quick_actions_desc'), 'route' => route('policies.index'), 'icon' => 'policy'],
      ['label' => __('dashboard.action_profile'), 'desc' => __('dashboard.quick_actions_desc'), 'route' => route('profile.show'), 'icon' => 'settings'],
    ];
  @endphp

  <div class="grid md:grid-cols-4 gap-4 mb-6">
    @foreach($quickActions as $card)
      <a href="{{ $card['route'] }}"
         class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5 flex flex-col gap-3 shadow-sm hover:shadow-lg transition">
        <div class="h-10 w-10 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
          <x-icon :name="$card['icon']" class="h-5 w-5"/>
        </div>
        <div class="text-sm font-semibold">{{ $card['label'] }}</div>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $card['desc'] }}</p>
      </a>
    @endforeach
  </div>

  {{-- KPIs --}}
  @php
    $stats = [
      ['label'=>__('dashboard.today_score'),      'value'=>$todayScore      ?? 0, 'icon'=>'sparkles', 'chip'=>'from-emerald-500/20 to-emerald-500/10 text-emerald-600'],
      ['label'=>__('dashboard.active_employees'), 'value'=>$activeEmployees ?? 0, 'icon'=>'users',    'chip'=>'from-sky-500/20 to-sky-500/10 text-sky-600'],
      ['label'=>__('dashboard.phone_events'),     'value'=>$phoneEvents     ?? 0, 'icon'=>'phone',    'chip'=>'from-teal-500/20 to-teal-500/10 text-teal-600'],
      ['label'=>__('dashboard.away_events'),      'value'=>$awayEvents      ?? 0, 'icon'=>'clock',    'chip'=>'from-amber-500/20 to-amber-500/10 text-amber-600'],
    ];
  @endphp

  <div class="grid md:grid-cols-4 gap-4 mb-6">
    @foreach($stats as $s)
      <div class="rounded-2xl border border-slate-200/70 dark:border-white/10
                  bg-white/70 dark:bg-white/5 backdrop-blur p-5
                  shadow-[0_8px_30px_rgba(0,0,0,.05)] animate-[fadeUp_.4s_ease]
                 ">
        <div class="flex items-center justify-between">
          <div class="text-sm text-slate-500 dark:text-slate-400">{{ $s['label'] }}</div>
          <div class="h-9 w-9 rounded-lg bg-gradient-to-br {{ $s['chip'] }}
                      border border-white/40 dark:border-white/10
                      flex items-center justify-center">
            <x-icon :name="$s['icon']" class="w-4 h-4"/>
          </div>
        </div>
        <div class="mt-2 text-2xl font-semibold tracking-tight">{{ $s['value'] }}</div>
      </div>
    @endforeach
  </div>

  {{-- Charts & Alerts --}}
  <div class="grid lg:grid-cols-3 gap-6 items-start">
    <div class="lg:col-span-2 rounded-2xl border border-slate-200/70 dark:border-white/10
                bg-white/70 dark:bg-white/5 backdrop-blur p-5 shadow-[0_8px_30px_rgba(0,0,0,.05)]">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold">{{ __('dashboard.weekly_score') }}</h3>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ now()->format('M Y') }}</span>
      </div>
      <div id="chart-weekly" class="h-[260px]"></div>
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10
                bg-white/70 dark:bg-white/5 backdrop-blur p-5 shadow-[0_8px_30px_rgba(0,0,0,.05)]">
      <h3 class="font-semibold mb-3">{{ __('dashboard.events_by_type') }}</h3>
      <div id="chart-events" class="h-[260px]"></div>
    </div>

    <div class="lg:col-span-3 rounded-2xl border border-slate-200/70 dark:border-white/10
                bg-white/70 dark:bg-white/5 backdrop-blur p-5 shadow-[0_8px_30px_rgba(0,0,0,.05)]">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">{{ __('dashboard.recent_alerts') }}</h3>
        <a href="{{ route('alerts.index') }}"
           class="text-sm text-emerald-700 dark:text-emerald-300 hover:underline">{{ __('dashboard.view_all') }}</a>
      </div>

      <ul class="divide-y divide-slate-200/70 dark:divide-white/10">
        @forelse(($alerts ?? []) as $a)
          <li class="py-3 flex items-start gap-3">
            <span class="mt-1 h-2.5 w-2.5 rounded-full {{ ($a['level'] ?? 'info') === 'critical' ? 'bg-rose-500' : (($a['level'] ?? 'info') === 'warning' ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
            <div class="flex-1">
              <div class="text-sm">{{ $a['title'] }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">
                {{ $a['source'] ?? 'analytics' }} · {{ $a['level'] ?? 'info' }}
                @if(!empty($a['time']))
                  · {{ $a['time'] }}
                @endif
                @if(!empty($a['is_resolved']))
                  · {{ __('dashboard.alert_state_resolved') }}
                @endif
              </div>
            </div>
          </li>
        @empty
          <li class="py-6 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.no_alerts') }}</li>
        @endforelse
      </ul>
    </div>
  </div>

  {{-- AI Insights --}}
  <div class="grid lg:grid-cols-3 gap-6 mt-6">
    <div class="lg:col-span-2 rounded-2xl border border-slate-200/70 dark:border-white/10
                bg-white/70 dark:bg-white/5 backdrop-blur p-5 shadow-[0_8px_30px_rgba(0,0,0,.05)]">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">{{ __('dashboard.ai_title') }}</h3>
        <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.ai_subtitle') }}</span>
      </div>
      <div class="grid md:grid-cols-3 gap-4">
        @foreach(($ai_insights ?? []) as $insight)
          <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $insight['title'] }}</div>
            <div class="mt-2 text-2xl font-semibold">
              {{ is_numeric($insight['value']) ? number_format($insight['value'], 1) : $insight['value'] }}
              @if(!is_null($insight['delta']))
                <span class="text-xs ml-1 {{ $insight['status'] === 'up' ? 'text-emerald-600' : 'text-rose-600' }}">
                  {{ $insight['delta'] > 0 ? '+' : '' }}{{ $insight['delta'] }}
                </span>
              @endif
            </div>
            <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $insight['note'] }}</div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200/70 dark:border-white/10
                bg-white/70 dark:bg-white/5 backdrop-blur p-5 shadow-[0_8px_30px_rgba(0,0,0,.05)]">
      <h3 class="font-semibold mb-3">{{ __('dashboard.ai_reco_title') }}</h3>
      <ul class="space-y-3 text-sm">
        @forelse(($ai_recommendations ?? []) as $rec)
          <li class="rounded-lg border border-slate-200/70 dark:border-white/10 p-3">
            <div class="font-semibold">{{ $rec['title'] }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $rec['desc'] }}</div>
          </li>
        @empty
          <li class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.ai_empty') }}</li>
        @endforelse
      </ul>
    </div>
  </div>

  {{-- Reports --}}
  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10
              bg-white/70 dark:bg-white/5 backdrop-blur p-5 shadow-[0_8px_30px_rgba(0,0,0,.05)] mt-6">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold">{{ __('dashboard.reports_title') }}</h3>
      <span class="text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.reports_subtitle') }}</span>
    </div>
    <div class="grid md:grid-cols-4 gap-4">
      @forelse(($report_rows ?? []) as $row)
        <div class="rounded-xl border border-slate-200/70 dark:border-white/10 p-4">
          <div class="text-xs text-slate-500 dark:text-slate-400">{{ $row['label'] }}</div>
          <div class="mt-2 text-lg font-semibold">{{ $row['value'] }}</div>
        </div>
      @empty
        <div class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.reports_empty') }}</div>
      @endforelse
    </div>
  </div>

  <div class="rounded-2xl border border-slate-200/70 dark:border-white/10 bg-white/70 dark:bg-white/5 backdrop-blur p-5 shadow-[0_8px_30px_rgba(0,0,0,.05)] mt-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h3 class="font-semibold">{{ __('dashboard.text_report_title') }}</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.text_report_subtitle') }}</p>
      </div>
      <div class="text-xs text-slate-500 dark:text-slate-400">
        {{ __('dashboard.total_employees', ['count' => number_format($summary_totals['employees'] ?? 0)]) }}
      </div>
    </div>

    <div class="mt-4 overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="text-xs uppercase tracking-wide text-slate-400">
          <tr>
            <th class="py-2 text-left">{{ __('dashboard.table_employee') }}</th>
            <th class="py-2 text-right">{{ __('dashboard.table_work') }}</th>
            <th class="py-2 text-right">{{ __('dashboard.table_idle') }}</th>
            <th class="py-2 text-right">{{ __('dashboard.table_phone') }}</th>
            <th class="py-2 text-right">{{ __('dashboard.table_away') }}</th>
            <th class="py-2 text-right">{{ __('dashboard.table_score') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-white/10">
          @forelse($summary_rows ?? [] as $row)
            <tr>
              <td class="py-2">{{ $row['name'] }}</td>
              <td class="py-2 text-right font-semibold">{{ $row['work_minutes'] }}</td>
              <td class="py-2 text-right">{{ $row['idle_minutes'] }}</td>
              <td class="py-2 text-right">{{ $row['phone_minutes'] }}</td>
              <td class="py-2 text-right">{{ $row['away_minutes'] }}</td>
              <td class="py-2 text-right">{{ $row['score'] }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-6 text-center text-slate-500 dark:text-slate-400">{{ __('dashboard.table_empty') }}</td>
            </tr>
          @endforelse
        </tbody>
        @if(!empty($summary_rows))
          <tfoot class="text-xs text-slate-500 dark:text-slate-400">
            <tr>
              <td class="py-3 font-semibold">{{ __('dashboard.total_employees', ['count' => number_format($summary_totals['employees'] ?? 0)]) }}</td>
              <td class="py-3 text-right">{{ $summary_totals['work'] ?? 0 }}</td>
              <td class="py-3 text-right">{{ $summary_totals['idle'] ?? 0 }}</td>
              <td class="py-3 text-right">{{ $summary_totals['phone'] ?? 0 }}</td>
              <td class="py-3 text-right">{{ $summary_totals['away'] ?? 0 }}</td>
              <td class="py-3 text-right">—</td>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  @php
    $weeklyLabels    = $weekly_labels ?? ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $weeklyValues    = $weekly_values ?? [0,0,0,0,0,0,0];
    $eventsBreakdown = [
      ['name'=>__('dashboard.work'),  'value'=> (int) ($events_breakdown['Work'] ?? 0)],
      ['name'=>__('dashboard.phone'), 'value'=> (int) ($events_breakdown['Phone'] ?? 0)],
      ['name'=>__('dashboard.away'),  'value'=> (int) ($events_breakdown['Away'] ?? 0)],
    ];
  @endphp
  <script>
    const weeklyLabels   = {!! json_encode($weeklyLabels) !!};
    const weeklyValues   = {!! json_encode($weeklyValues) !!};
    const eventsBreakdown= {!! json_encode($eventsBreakdown) !!};

    const breakdownSeries = eventsBreakdown.map(i => i.value);
    const breakdownLabels = eventsBreakdown.map(i => i.name);

    new ApexCharts(document.querySelector("#chart-weekly"), {
      chart:{ type:'area', height:260, toolbar:{show:false}, fontFamily:'ui-sans-serif, system-ui' },
      series:[{ name:'Score', data: weeklyValues }],
      xaxis:{ categories: weeklyLabels, labels:{ style:{ colors: axisColor() }}},
      yaxis:{ labels:{ style:{ colors: axisColor() }}},
      colors:['#10b981'],
      dataLabels:{ enabled:false },
      stroke:{ width:2, curve:'smooth' },
      fill:{ type:'gradient', gradient:{ shadeIntensity:1, opacityFrom:0.35, opacityTo:0.05, stops:[0,90,100]}},
      grid:{ borderColor:gridColor(), strokeDashArray:4 }
    }).render();

    new ApexCharts(document.querySelector("#chart-events"), {
      chart:{ type:'donut', height:260, toolbar:{show:false} },
      series: breakdownSeries,
      labels: breakdownLabels,
      colors:['#10b981','#06b6d4','#f59e0b'],
      legend:{ position:'bottom', labels:{ colors: axisColor() } }
    }).render();

    function axisColor(){ return document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569'; }
    function gridColor(){ return document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,.06)' : 'rgba(15,23,42,.06)'; }
  </script>

  <style>
  @keyframes fadeUp{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  </style>
@endpush
