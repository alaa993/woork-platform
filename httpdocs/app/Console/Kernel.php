<?php
namespace App\Console;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\{GenerateAlertsJob, GenerateDailySummariesJob, GenerateOperationalAlertsJob};

class Kernel extends ConsoleKernel {
  protected function schedule(Schedule $schedule): void {
    $schedule->job(new GenerateDailySummariesJob)->hourly();
    $schedule->job(new GenerateAlertsJob)->hourly();
    $schedule->job(new GenerateOperationalAlertsJob)->everyFiveMinutes();
  }
  protected function commands(): void {}
}
