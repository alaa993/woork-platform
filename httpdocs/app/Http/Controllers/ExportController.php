<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Camera;
use App\Models\DailySummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    public function dailyCsv(Request $request)
    {
        $organizationId = Auth::user()->organization_id;
        $date = $request->input('date', date('Y-m-d'));
        $scope = $request->input('scope', 'employees');
        $rows = DailySummary::where('organization_id', $organizationId)
            ->whereDate('date', $date)
            ->with(['employee:id,name', 'room:id,name'])
            ->get();

        $filename = 'woork_'.$scope.'_'.$date.'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($rows, $scope, $organizationId, $date) {
            $out = fopen('php://output', 'w');

            if ($scope === 'rooms') {
                fputcsv($out, ['room_id', 'room_name', 'employees', 'work_minutes', 'idle_minutes', 'phone_minutes', 'away_minutes', 'avg_score']);
                $grouped = $rows->groupBy('room_id');
                foreach ($grouped as $roomId => $roomRows) {
                    $first = $roomRows->first();
                    fputcsv($out, [
                        $roomId,
                        $first?->room?->name ?? '—',
                        $roomRows->count(),
                        (int) $roomRows->sum('work_minutes'),
                        (int) $roomRows->sum('idle_minutes'),
                        (int) $roomRows->sum('phone_minutes'),
                        (int) $roomRows->sum('away_minutes'),
                        round((float) $roomRows->avg('score'), 1),
                    ]);
                }
            } elseif ($scope === 'organization') {
                $employees = $rows->count();
                $work = (int) $rows->sum('work_minutes');
                $idle = (int) $rows->sum('idle_minutes');
                $phone = (int) $rows->sum('phone_minutes');
                $away = (int) $rows->sum('away_minutes');
                $total = max(1, $work + $idle + $phone + $away);

                fputcsv($out, ['date', 'employees', 'avg_score', 'utilization_percent', 'work_minutes', 'idle_minutes', 'phone_minutes', 'away_minutes', 'phone_rate', 'away_rate']);
                fputcsv($out, [
                    $date,
                    $employees,
                    round((float) $rows->avg('score'), 1),
                    (int) round(($work / $total) * 100),
                    $work,
                    $idle,
                    $phone,
                    $away,
                    $employees ? round($phone / $employees, 1) : 0,
                    $employees ? round($away / $employees, 1) : 0,
                ]);
            } elseif ($scope === 'system') {
                $cameras = Camera::where('organization_id', $organizationId)->get();
                $operationalAlerts = Alert::where('organization_id', $organizationId)
                    ->where('source', 'operations')
                    ->where('is_active', true)
                    ->count();
                $fallbackAlerts = Alert::where('organization_id', $organizationId)
                    ->where('source', 'operations')
                    ->where('kind', 'detector_fallback')
                    ->where('is_active', true)
                    ->count();

                fputcsv($out, ['date', 'camera_total', 'camera_online', 'camera_warning', 'camera_offline', 'operational_alerts', 'detector_fallbacks']);
                fputcsv($out, [
                    $date,
                    $cameras->count(),
                    $cameras->where('stream_status', 'online')->count(),
                    $cameras->where('stream_status', 'warning')->count(),
                    $cameras->filter(fn ($camera) => in_array($camera->stream_status, ['offline', 'misconfigured', 'pending', null], true))->count(),
                    $operationalAlerts,
                    $fallbackAlerts,
                ]);
            } else {
                fputcsv($out, ['employee_id', 'employee_name', 'room_name', 'work_minutes', 'idle_minutes', 'phone_minutes', 'away_minutes', 'phone_count', 'away_count', 'score']);
                foreach ($rows as $summary) {
                    fputcsv($out, [
                        $summary->employee_id,
                        $summary->employee?->name ?? '—',
                        $summary->room?->name ?? '—',
                        $summary->work_minutes,
                        $summary->idle_minutes,
                        $summary->phone_minutes,
                        $summary->away_minutes,
                        $summary->phone_count,
                        $summary->away_count,
                        $summary->score,
                    ]);
                }
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
