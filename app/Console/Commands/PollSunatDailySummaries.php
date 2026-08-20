<?php

namespace App\Console\Commands;

use App\Models\SunatDailySummary;
use App\Services\Sunat\SunatDailySummaryService;
use Illuminate\Console\Command;

class PollSunatDailySummaries extends Command
{
    protected $signature = 'sunat:poll-daily-summaries {--limit=20}';
    protected $description = 'Consulta tickets pendientes de los Resúmenes Diarios';

    public function handle(SunatDailySummaryService $service): int
    {
        $summaries = SunatDailySummary::where('status', SunatDailySummary::STATUS_TICKET)
            ->whereNotNull('ticket')->oldest('sent_at')->limit((int) $this->option('limit'))->get();
        foreach ($summaries as $summary) {
            try {
                $service->check($summary);
                $this->line($summary->identifier.': '.$summary->fresh()->status);
            } catch (\Throwable $exception) {
                $this->warn($summary->identifier.': '.$exception->getMessage());
            }
        }
        return self::SUCCESS;
    }
}
