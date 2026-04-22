<?php
namespace App\Jobs;

use App\Models\{DailySummary, Policy};
use App\Services\{AlertDispatcher, DailySummaryGenerator};
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAlertsJob implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function handle(DailySummaryGenerator $generator, AlertDispatcher $dispatcher): void {
    $date = Carbon::today();
    $generator->runForDate($date);

    $summaries = DailySummary::whereDate('date', $date)->get();
    $policies = Policy::whereIn('organization_id', $summaries->pluck('organization_id')->unique())->get()->keyBy('organization_id');

    foreach($summaries as $summary){
      $policy = $policies[$summary->organization_id] ?? null;
      $thresholds = $this->thresholdsFor($policy);
      $channels = $this->channelsFor($policy);

      $this->checkPhoneUsage($summary, $thresholds, $dispatcher, $channels);
      $this->checkIdleTime($summary, $thresholds, $dispatcher, $channels);
      $this->checkAwayTime($summary, $thresholds, $dispatcher, $channels);
    }
  }

  protected function thresholdsFor(?Policy $policy): array {
    $defaults = config('woork.thresholds', []);
    if (!$policy) {
      return $defaults;
    }
    return array_merge($defaults, $policy->thresholds ?? []);
  }

  protected function channelsFor(?Policy $policy): array {
    $visibility = $policy?->visibility ?? [];
    $channels = $visibility['alert_channels'] ?? $visibility['channels'] ?? null;
    if (is_array($channels) && count($channels)) {
      return $channels;
    }
    return ['in_app'];
  }

  protected function checkPhoneUsage(DailySummary $summary, array $thresholds, AlertDispatcher $dispatcher, array $channels): void {
    $limit = $thresholds['phone_max_minutes'] ?? 20;
    if (($summary->phone_minutes ?? 0) <= $limit) {
      return;
    }
    $dispatcher->dispatch([
      'organization_id'=>$summary->organization_id,
      'employee_id'=>$summary->employee_id,
      'room_id'=>$summary->room_id,
      'kind'=>'excessive_phone',
      'level'=>'warning',
      'message'=>"Phone usage high: {$summary->phone_minutes} min",
      'rules'=>['limit'=>$limit,'actual'=>$summary->phone_minutes],
    ], $channels);
  }

  protected function checkIdleTime(DailySummary $summary, array $thresholds, AlertDispatcher $dispatcher, array $channels): void {
    $limit = $thresholds['long_idle_minutes'] ?? 25;
    if (($summary->idle_minutes ?? 0) <= $limit) {
      return;
    }
    $dispatcher->dispatch([
      'organization_id'=>$summary->organization_id,
      'employee_id'=>$summary->employee_id,
      'room_id'=>$summary->room_id,
      'kind'=>'long_idle',
      'level'=>'info',
      'message'=>"Idle too long: {$summary->idle_minutes} min",
      'rules'=>['limit'=>$limit,'actual'=>$summary->idle_minutes],
    ], $channels);
  }

  protected function checkAwayTime(DailySummary $summary, array $thresholds, AlertDispatcher $dispatcher, array $channels): void {
    $limit = $thresholds['leave_max_minutes'] ?? 30;
    if (($summary->away_minutes ?? 0) <= $limit) {
      return;
    }
    $dispatcher->dispatch([
      'organization_id'=>$summary->organization_id,
      'employee_id'=>$summary->employee_id,
      'room_id'=>$summary->room_id,
      'kind'=>'away_excessive',
      'level'=>'warning',
      'message'=>"Away time exceeded: {$summary->away_minutes} min",
      'rules'=>['limit'=>$limit,'actual'=>$summary->away_minutes],
    ], $channels);
  }
}
