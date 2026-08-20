<?php

namespace App\Jobs;

use App\Models\Venta;
use App\Services\Sunat\SunatEmissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendElectronicInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public readonly int $saleId)
    {
        $this->onQueue('sunat');
    }

    public function handle(SunatEmissionService $service): void
    {
        $sale = Venta::find($this->saleId);
        if (! $sale || $sale->tipo_comprobante !== 'factura') {
            return;
        }
        $service->send($sale);
    }
}
