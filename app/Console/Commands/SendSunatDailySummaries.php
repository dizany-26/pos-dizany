<?php

namespace App\Console\Commands;

use App\Jobs\SendSunatDailySummary;
use App\Services\Sunat\SunatDailySummaryPreparer;
use App\Models\SunatSetting;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class SendSunatDailySummaries extends Command
{
    protected $signature = 'sunat:send-daily-summary {--date= : Fecha de emisión YYYY-MM-DD; por defecto ayer}';
    protected $description = 'Prepara y encola el Resumen Diario de boletas aún no informadas';

    public function handle(SunatDailySummaryPreparer $preparer): int
    {
        if (! SunatSetting::current()->enabled) {
            $this->info('La emisión SUNAT está desactivada; no se creó ningún Resumen Diario.');
            return self::SUCCESS;
        }
        $dates = $this->option('date')
            ? [CarbonImmutable::parse($this->option('date'))->startOfDay()]
            : collect(range(7, 1))->map(fn (int $days) => CarbonImmutable::today()->subDays($days))->all();

        foreach ($dates as $date) {
            try {
                $summary = $preparer->prepare($date);
            } catch (\Throwable $exception) {
                $this->error($date->format('Y-m-d').': '.$exception->getMessage());
                return self::FAILURE;
            }
            if (! $summary) {
                $this->line('Sin boletas pendientes para '.$date->format('Y-m-d').'.');
                continue;
            }
            SendSunatDailySummary::dispatch($summary->id);
            $this->info($summary->identifier.' preparado con '.$summary->items->count().' boleta(s).');
        }
        return self::SUCCESS;
    }
}
