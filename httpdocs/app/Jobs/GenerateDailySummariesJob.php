<?php

namespace App\Jobs;

use App\Services\DailySummaryGenerator;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDailySummariesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ?string $date = null
    ) {
    }

    public function handle(DailySummaryGenerator $generator): void
    {
        $generator->runForDate($this->date ?? Carbon::today());
    }
}
