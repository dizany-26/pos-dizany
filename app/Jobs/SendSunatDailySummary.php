<?php

namespace App\Jobs;

use App\Models\SunatDailySummary;
use App\Services\Sunat\SunatDailySummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSunatDailySummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(public readonly int $summaryId)
    {
        $this->onQueue('sunat');
    }

    public function handle(SunatDailySummaryService $service): void
    {
        $summary = SunatDailySummary::find($this->summaryId);
        if ($summary) {
            $service->send($summary);
        }
    }
}
