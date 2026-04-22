<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\OrganizationOnboardingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\{Alert, DailySummary, Event};
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(OrganizationOnboardingService $onboardingService)
    {
        $user = Auth::user();

        // سوبر أدمن: اسمح بالدخول حتى بدون منظمة
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return view('dashboard.index', [
                'todayScore'        => 0,
                'activeEmployees'   => 0,
                'phoneEvents'       => 0,
                'awayEvents'        => 0,
                'weekly_labels'     => ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
                'weekly_values'     => [0,0,0,0,0,0,0],
                'events_breakdown'  => ['Work'=>0,'Phone'=>0,'Away'=>0],
                'summary_rows'      => [],
                'summary_totals'    => ['work'=>0,'idle'=>0,'phone'=>0,'away'=>0,'employees'=>0],
                'alerts'            => [],
            ]);
        }

        // باقي الأدوار: يلزم منظمة
        abort_unless($user && $user->organization_id, 403);
        $orgId = (int) $user->organization_id;
        $organization = Organization::with(['agentDevices', 'cameras'])->findOrFail($orgId);
        $onboarding = $onboardingService->summary($organization);

        $todayStart = Carbon::today();
        $todayEnd   = (clone $todayStart)->endOfDay();

        $data = Cache::remember("dash:v4:org:{$orgId}", 60, function () use ($orgId, $todayStart, $todayEnd) {

            // KPIs اليوم
            $activeEmployees = Event::where('organization_id', $orgId)
                ->whereBetween('started_at', [$todayStart, $todayEnd])
                ->distinct('employee_id')
                ->count('employee_id');

            $phoneEvents = Event::where('organization_id', $orgId)
                ->whereBetween('started_at', [$todayStart, $todayEnd])
                ->where('type', 'phone')
                ->count();

            $awayEvents = Event::where('organization_id', $orgId)
                ->whereBetween('started_at', [$todayStart, $todayEnd])
                ->where('type', 'away')
                ->count();

            $todayScore = (int) round(
                DailySummary::where('organization_id', $orgId)
                    ->whereDate('date', $todayStart->toDateString())
                    ->avg('score') ?? 0
            );

            // Area chart: آخر 7 أيام
            $days = collect(range(0,6))->map(fn($i) => Carbon::today()->subDays(6 - $i));
            $weekly_labels = $days->map(fn($d) => $d->format('D'))->values()->all();

            $scoresByDay = DailySummary::where('organization_id', $orgId)
                ->whereBetween('date', [$days->first()->toDateString(), $days->last()->toDateString()])
                ->get(['date','score'])
                ->groupBy(fn($row) => Carbon::parse($row->date)->toDateString())
                ->map(fn($rows) => (float) $rows->avg('score'));

            $weekly_values = $days->map(function ($d) use ($scoresByDay) {
                $key = $d->toDateString();
                return (float) round(($scoresByDay[$key] ?? 0), 2);
            })->values()->all();

            // Donut: تجميعة الأنواع خلال 7 أيام
            $since = Carbon::today()->subDays(6);
            $counts = Event::where('organization_id', $orgId)
                ->where('started_at', '>=', $since)
                ->selectRaw("type, COUNT(*) as c")
                ->groupBy('type')
                ->pluck('c', 'type')
                ->toArray();

            $events_breakdown = [
                'Work'  => (int) ($counts['work_active'] ?? 0),
                'Phone' => (int) ($counts['phone'] ?? 0),
                'Away'  => (int) ($counts['away'] ?? 0),
            ];

            $todaySummaries = DailySummary::where('organization_id', $orgId)
                ->whereDate('date', $todayStart)
                ->with('employee')
                ->orderByDesc('work_minutes')
                ->get();

            $summaryRows = $todaySummaries->map(function ($summary) {
                $employeeName = $summary->employee?->name ?? __('dashboard.unknown_employee');
                $total = max(1, $summary->work_minutes + $summary->idle_minutes + $summary->phone_minutes + $summary->away_minutes);
                $score = round(($summary->work_minutes / $total) * 100);
                return [
                    'name' => $employeeName,
                    'work_minutes' => $summary->work_minutes,
                    'idle_minutes' => $summary->idle_minutes,
                    'phone_minutes' => $summary->phone_minutes,
                    'away_minutes' => $summary->away_minutes,
                    'score' => $summary->score,
                    'percentage' => $score,
                ];
            })->values()->all();

            $totals = [
                'work' => $todaySummaries->sum('work_minutes'),
                'idle' => $todaySummaries->sum('idle_minutes'),
                'phone' => $todaySummaries->sum('phone_minutes'),
                'away' => $todaySummaries->sum('away_minutes'),
                'employees' => $todaySummaries->count(),
            ];

            // تقارير وتحليلات إضافية
            $last7Start = Carbon::today()->subDays(6);
            $prev7Start = Carbon::today()->subDays(13);
            $prev7End   = Carbon::today()->subDays(7);

            $avgScore7 = (float) DailySummary::where('organization_id', $orgId)
                ->whereBetween('date', [$last7Start->toDateString(), $todayStart->toDateString()])
                ->avg('score');

            $avgScorePrev7 = (float) DailySummary::where('organization_id', $orgId)
                ->whereBetween('date', [$prev7Start->toDateString(), $prev7End->toDateString()])
                ->avg('score');

            $scoreDelta = round($avgScore7 - $avgScorePrev7, 1);

            $bestToday = DailySummary::where('organization_id', $orgId)
                ->whereDate('date', $todayStart)
                ->with('employee')
                ->orderByDesc('score')
                ->first();

            $lowestToday = DailySummary::where('organization_id', $orgId)
                ->whereDate('date', $todayStart)
                ->with('employee')
                ->orderBy('score')
                ->first();

            $totalMinutes = max(1, $totals['work'] + $totals['idle'] + $totals['phone'] + $totals['away']);
            $utilization = (int) round(($totals['work'] / $totalMinutes) * 100);
            $avgPhone = $totals['employees'] ? round($totals['phone'] / $totals['employees'], 1) : 0;
            $avgAway  = $totals['employees'] ? round($totals['away'] / $totals['employees'], 1) : 0;

            $aiInsights = [
                [
                    'title' => __('dashboard.ai_trend_title'),
                    'value' => round($avgScore7, 1),
                    'delta' => $scoreDelta,
                    'status' => $scoreDelta >= 0 ? 'up' : 'down',
                    'note' => $scoreDelta >= 0 ? __('dashboard.ai_trend_up') : __('dashboard.ai_trend_down'),
                ],
                [
                    'title' => __('dashboard.ai_util_title'),
                    'value' => $utilization,
                    'delta' => null,
                    'status' => $utilization >= 70 ? 'up' : 'down',
                    'note' => $utilization >= 70 ? __('dashboard.ai_util_ok') : __('dashboard.ai_util_low'),
                ],
                [
                    'title' => __('dashboard.ai_focus_title'),
                    'value' => $avgPhone,
                    'delta' => null,
                    'status' => $avgPhone <= 20 ? 'up' : 'down',
                    'note' => $avgPhone <= 20 ? __('dashboard.ai_focus_good') : __('dashboard.ai_focus_risk'),
                ],
            ];

            $recommendations = [
                [
                    'title' => __('dashboard.ai_reco_focus_title'),
                    'desc'  => $avgPhone > 20
                        ? __('dashboard.ai_reco_focus_high', ['minutes' => $avgPhone])
                        : __('dashboard.ai_reco_focus_ok', ['minutes' => $avgPhone]),
                ],
                [
                    'title' => __('dashboard.ai_reco_away_title'),
                    'desc'  => $avgAway > 20
                        ? __('dashboard.ai_reco_away_high', ['minutes' => $avgAway])
                        : __('dashboard.ai_reco_away_ok', ['minutes' => $avgAway]),
                ],
                [
                    'title' => __('dashboard.ai_reco_best_title'),
                    'desc'  => $bestToday
                        ? __('dashboard.ai_reco_best_desc', [
                            'name' => $bestToday->employee?->name ?? __('dashboard.unknown_employee'),
                            'score' => (int) $bestToday->score,
                        ])
                        : __('dashboard.ai_reco_best_empty'),
                ],
            ];

            $reports = [
                [
                    'label' => __('dashboard.report_avg_score_7d'),
                    'value' => round($avgScore7, 1),
                ],
                [
                    'label' => __('dashboard.report_utilization'),
                    'value' => $utilization . '%',
                ],
                [
                    'label' => __('dashboard.report_top_employee'),
                    'value' => $bestToday
                        ? ($bestToday->employee?->name ?? __('dashboard.unknown_employee')) . ' · ' . (int) $bestToday->score
                        : __('dashboard.report_na'),
                ],
                [
                    'label' => __('dashboard.report_low_employee'),
                    'value' => $lowestToday
                        ? ($lowestToday->employee?->name ?? __('dashboard.unknown_employee')) . ' · ' . (int) $lowestToday->score
                        : __('dashboard.report_na'),
                ],
            ];

            $alerts = Alert::where('organization_id', $orgId)
                ->latest('id')
                ->limit(6)
                ->get(['message', 'level', 'source', 'resolved_at', 'created_at'])
                ->map(function ($alert) {
                    return [
                        'title' => $alert->message,
                        'time' => optional($alert->resolved_at ?? $alert->created_at)?->diffForHumans(),
                        'level' => $alert->level,
                        'source' => $alert->source,
                        'is_resolved' => (bool) $alert->resolved_at,
                    ];
                })
                ->values()
                ->all();

            return [
                'todayScore'        => $todayScore,
                'activeEmployees'   => $activeEmployees,
                'phoneEvents'       => $phoneEvents,
                'awayEvents'        => $awayEvents,
                'weekly_labels'     => $weekly_labels,
                'weekly_values'     => $weekly_values,
                'events_breakdown'  => $events_breakdown,
                'summary_rows'      => $summaryRows,
                'summary_totals'    => $totals,
                'ai_insights'       => $aiInsights,
                'ai_recommendations'=> $recommendations,
                'report_rows'       => $reports,
                'alerts'            => $alerts,
            ];
        });

        return view('dashboard.index', array_merge($data, [
            'onboarding' => $onboarding,
        ]));
    }
}
