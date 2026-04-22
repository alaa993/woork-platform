<?php

namespace App\Services;

use App\Models\Event;
use App\Models\DailySummary;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DailySummaryGenerator
{
    /**
     * Convert events of the requested date (and optionally organization) into daily summaries.
     *
     * @param  Carbon|string|null  $date
     * @param  int|null  $organizationId
     * @return Collection<DailySummary>
     */
    public function runForDate(Carbon|string|null $date = null, ?int $organizationId = null): Collection
    {
        $target = Carbon::parse($date ?? now()->toDateString())->startOfDay();
        $eventsQuery = Event::whereDate('started_at', $target);

        if ($organizationId) {
            $eventsQuery->where('organization_id', $organizationId);
        }

        $events = $eventsQuery->get();
        return $this->aggregateEvents($events, $target);
    }

    protected function aggregateEvents(Collection $events, Carbon $date): Collection
    {
        $grouped = [];

        foreach ($events as $event) {
            $key = $this->makeKey($event->organization_id, $event->employee_id);

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'organization_id' => $event->organization_id,
                    'employee_id'     => $event->employee_id,
                    'room_id'         => $event->room_id,
                    'work_minutes'    => 0,
                    'idle_minutes'    => 0,
                    'phone_minutes'   => 0,
                    'away_minutes'    => 0,
                    'phone_count'     => 0,
                    'away_count'      => 0,
                ];
            }

            $record = &$grouped[$key];
            $record['room_id'] = $event->room_id ?? $record['room_id'];
            $minutes = $this->minutesFromDuration($event->duration_seconds);

            switch ($event->type) {
                case 'work_active':
                case 'work':
                    $record['work_minutes'] += $minutes;
                    break;
                case 'idle':
                    $record['idle_minutes'] += $minutes;
                    break;
                case 'phone':
                    $record['phone_minutes'] += $minutes;
                    $record['phone_count']++;
                    break;
                case 'away':
                    $record['away_minutes'] += $minutes;
                    $record['away_count']++;
                    break;
                default:
                    // ignore unknown types for now
                    break;
            }
        }

        $results = collect();

        foreach ($grouped as $data) {
            $total = max(1, $data['work_minutes'] + $data['idle_minutes'] + $data['phone_minutes'] + $data['away_minutes']);
            $score = (int) max(0, min(100, round(
                ($data['work_minutes'] / $total) * 100
                - ($data['phone_minutes'] * 0.5)
                - ($data['away_minutes'] * 0.3)
            )));

            $summary = DailySummary::updateOrCreate(
                [
                    'organization_id' => $data['organization_id'],
                    'employee_id'     => $data['employee_id'],
                    'date'            => $date->toDateString(),
                ],
                [
                    'room_id'       => $data['room_id'],
                    'work_minutes'  => $data['work_minutes'],
                    'idle_minutes'  => $data['idle_minutes'],
                    'phone_minutes' => $data['phone_minutes'],
                    'away_minutes'  => $data['away_minutes'],
                    'phone_count'   => $data['phone_count'],
                    'away_count'    => $data['away_count'],
                    'score'         => $score,
                ]
            );

            $results->push($summary);
        }

        return $results;
    }

    protected function makeKey(int $orgId, int $employeeId): string
    {
        return "{$orgId}:{$employeeId}";
    }

    protected function minutesFromDuration(?int $seconds): int
    {
        return max(0, (int) floor(($seconds ?? 0) / 60));
    }
}
