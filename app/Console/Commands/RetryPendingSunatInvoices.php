<?php

namespace App\Console\Commands;

use App\Jobs\SendElectronicInvoice;
use App\Models\SunatSetting;
use App\Models\Venta;
use App\Models\ElectronicCreditNote;
use App\Jobs\SendElectronicCreditNote;
use Illuminate\Console\Command;

class RetryPendingSunatInvoices extends Command
{
    protected $signature = 'sunat:retry-pending {--limit=20}';
    protected $description = 'Reintenta facturas electrónicas pendientes o con error en SUNAT Beta';

    public function handle(): int
    {
        if (! SunatSetting::current()->enabled) {
            $this->components->info('La emisión SUNAT está desactivada. No se enviaron documentos.');
            return self::SUCCESS;
        }

        $sales = Venta::query()
            ->where('tipo_comprobante', 'factura')
            ->whereIn('estado', ['pagado','credito','pendiente'])
            ->where('igv', '>', 0)
            ->whereIn('estado_sunat', ['pendiente', 'error'])
            ->whereRaw('(SELECT COUNT(*) FROM electronic_document_attempts a INNER JOIN electronic_documents d ON d.id = a.electronic_document_id WHERE d.venta_id = ventas.id) < 5')
            ->oldest('fecha')
            ->limit(max(1, min(100, (int) $this->option('limit'))))
            ->get();

        foreach ($sales as $sale) {
            SendElectronicInvoice::dispatch($sale->id);
        }

        $notes=ElectronicCreditNote::query()
            ->whereIn('status',['draft','ready','error'])
            ->withCount('attempts')->having('attempts_count','<',5)
            ->oldest('created_at')->limit(max(1,min(100,(int)$this->option('limit'))))->get();
        foreach($notes as $note){ SendElectronicCreditNote::dispatch($note->id); }

        $this->components->info($sales->count().' factura(s) y '.$notes->count().' nota(s) colocadas en la cola SUNAT.');
        return self::SUCCESS;
    }
}
