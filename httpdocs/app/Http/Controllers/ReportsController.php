<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Alert;
use App\Models\Camera;
use App\Models\DailySummary;
use App\Services\DailySummaryGenerator;
use Carbon\Carbon;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request, DailySummaryGenerator $generator): View
    {
        $user = Auth::user();
        abort_unless($user && $user->organization_id, 403);

        $date = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();
        $organizationId = (int) $user->organization_id;
        $generator->runForDate($date, $organizationId);

        $summaries = DailySummary::where('organization_id', $organizationId)
            ->whereDate('date', $date)
            ->with(['employee:id,name', 'room:id,name'])
            ->get();

        $weeklyRange = collect(range(0, 6))->map(fn ($offset) => $date->copy()->subDays(6 - $offset));
        $weeklyScores = DailySummary::where('organization_id', $organizationId)
            ->whereBetween('date', [$weeklyRange->first()->toDateString(), $weeklyRange->last()->toDateString()])
            ->get(['date', 'score'])
            ->groupBy(fn ($row) => Carbon::parse($row->date)->toDateString())
            ->map(fn ($rows) => round((float) $rows->avg('score'), 1));

        $organizationReport = $this->organizationReport($summaries);
        $employeeReport = $this->employeeReport($summaries);
        $roomReport = $this->roomReport($summaries);
        $systemReport = $this->systemReport($organizationId);
        $comparisons = $this->comparisonsReport($organizationId, $date);

        return view('dashboard.reports.index', [
            'reportDate' => $date,
            'organizationReport' => $organizationReport,
            'employeeReport' => $employeeReport,
            'roomReport' => $roomReport,
            'systemReport' => $systemReport,
            'comparisons' => $comparisons,
            'weeklyLabels' => $weeklyRange->map(fn ($day) => $day->format('D'))->values()->all(),
            'weeklyScores' => $weeklyRange->map(fn ($day) => $weeklyScores[$day->toDateString()] ?? 0)->values()->all(),
        ]);
    }

    public function daily(Request $request, DailySummaryGenerator $generator)
    {
        $user = Auth::user();
        abort_unless($user && $user->organization_id, 403);

        $date = $request->input('date', now()->toDateString());
        $generator->runForDate($date, $user->organization_id);

        $rows = DailySummary::where('organization_id', $user->organization_id)
            ->where('date', $date)
            ->orderBy('employee_id')
            ->get();

        return response()->json($rows);
    }

    protected function organizationReport($summaries): array
    {
        $employees = $summaries->count();
        $work = (int) $summaries->sum('work_minutes');
        $idle = (int) $summaries->sum('idle_minutes');
        $phone = (int) $summaries->sum('phone_minutes');
        $away = (int) $summaries->sum('away_minutes');
        $total = max(1, $work + $idle + $phone + $away);

        return [
            'employees' => $employees,
            'avg_score' => round((float) $summaries->avg('score'), 1),
            'work_minutes' => $work,
            'idle_minutes' => $idle,
            'phone_minutes' => $phone,
            'away_minutes' => $away,
            'utilization' => (int) round(($work / $total) * 100),
            'phone_rate' => $employees ? round($phone / $employees, 1) : 0,
            'away_rate' => $employees ? round($away / $employees, 1) : 0,
        ];
    }

    protected function employeeReport($summaries): array
    {
        $rows = $summaries
            ->map(function ($summary) {
                return [
                    'employee_id' => $summary->employee_id,
                    'name' => $summary->employee?->name ?? __('dashboard.unknown_employee'),
                    'room' => $summary->room?->name ?? '—',
                    'work_minutes' => (int) $summary->work_minutes,
                    'idle_minutes' => (int) $summary->idle_minutes,
                    'phone_minutes' => (int) $summary->phone_minutes,
                    'away_minutes' => (int) $summary->away_minutes,
                    'score' => (int) $summary->score,
                ];
            })
            ->sortByDesc('score')
            ->values();

        return [
            'rows' => $rows->all(),
            'top' => $rows->take(5)->values()->all(),
            'bottom' => $rows->sortBy('score')->take(5)->values()->all(),
        ];
    }

    protected function roomReport($summaries): array
    {
        return $summaries
            ->groupBy('room_id')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'room_id' => $first->room_id,
                    'name' => $first->room?->name ?? '—',
                    'employees' => $rows->count(),
                    'avg_score' => round((float) $rows->avg('score'), 1),
                    'work_minutes' => (int) $rows->sum('work_minutes'),
                    'idle_minutes' => (int) $rows->sum('idle_minutes'),
                    'phone_minutes' => (int) $rows->sum('phone_minutes'),
                    'away_minutes' => (int) $rows->sum('away_minutes'),
                ];
            })
            ->sortByDesc('avg_score')
            ->values()
            ->all();
    }

    protected function systemReport(int $organizationId): array
    {
        $cameras = Camera::where('organization_id', $organizationId)->get();
        $operationalAlerts = Alert::where('organization_id', $organizationId)
            ->where('source', 'operations')
            ->active()
            ->count();

        $fallbackCount = Alert::where('organization_id', $organizationId)
            ->where('source', 'operations')
            ->where('kind', 'detector_fallback')
            ->active()
            ->count();

        $offlineCount = $cameras->filter(fn ($camera) => in_array($camera->stream_status, ['offline', 'misconfigured', 'pending', null], true))->count();
        $warningCount = $cameras->where('stream_status', 'warning')->count();
        $onlineCount = $cameras->where('stream_status', 'online')->count();

        return [
            'camera_total' => $cameras->count(),
            'camera_online' => $onlineCount,
            'camera_warning' => $warningCount,
            'camera_offline' => $offlineCount,
            'operational_alerts' => $operationalAlerts,
            'detector_fallbacks' => $fallbackCount,
        ];
    }

    protected function comparisonsReport(int $organizationId, Carbon $date): array
    {
        $weekStart = $date->copy()->subDays(6)->toDateString();
        $weekEnd = $date->toDateString();
        $prevWeekStart = $date->copy()->subDays(13)->toDateString();
        $prevWeekEnd = $date->copy()->subDays(7)->toDateString();
        $monthStart = $date->copy()->startOfMonth()->toDateString();
        $monthEnd = $date->toDateString();
        $prevMonthStart = $date->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $prevMonthEnd = $date->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $currentWeek = DailySummary::where('organization_id', $organizationId)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->get();
        $previousWeek = DailySummary::where('organization_id', $organizationId)
            ->whereBetween('date', [$prevWeekStart, $prevWeekEnd])
            ->get();
        $currentMonth = DailySummary::where('organization_id', $organizationId)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get();
        $previousMonth = DailySummary::where('organization_id', $organizationId)
            ->whereBetween('date', [$prevMonthStart, $prevMonthEnd])
            ->get();

        return [
            'week' => $this->comparePeriods($currentWeek, $previousWeek),
            'month' => $this->comparePeriods($currentMonth, $previousMonth),
        ];
    }

    protected function comparePeriods($current, $previous): array
    {
        $currentScore = round((float) $current->avg('score'), 1);
        $previousScore = round((float) $previous->avg('score'), 1);
        $currentPhone = (int) $current->sum('phone_minutes');
        $previousPhone = (int) $previous->sum('phone_minutes');
        $currentAway = (int) $current->sum('away_minutes');
        $previousAway = (int) $previous->sum('away_minutes');
        $currentWork = (int) $current->sum('work_minutes');
        $previousWork = (int) $previous->sum('work_minutes');

        return [
            'score' => [
                'current' => $currentScore,
                'previous' => $previousScore,
                'delta' => round($currentScore - $previousScore, 1),
            ],
            'phone' => [
                'current' => $currentPhone,
                'previous' => $previousPhone,
                'delta' => $currentPhone - $previousPhone,
            ],
            'away' => [
                'current' => $currentAway,
                'previous' => $previousAway,
                'delta' => $currentAway - $previousAway,
            ],
            'work' => [
                'current' => $currentWork,
                'previous' => $previousWork,
                'delta' => $currentWork - $previousWork,
            ],
        ];
    }
}
