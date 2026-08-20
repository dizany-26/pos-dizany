<?php

namespace App\Services\Sunat;

use App\Models\ElectronicCreditNote;
use App\Models\ElectronicDocument;
use App\Models\Venta;
use App\Services\DocumentNumberService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ElectronicCreditNotePreparer
{
    public function __construct(private readonly DocumentNumberService $numbers) {}

    public function prepare(Venta $sale, string $reasonCode, string $reason): ElectronicCreditNote
    {
        $sale->loadMissing('electronicDocument');
        $original = $sale->electronicDocument;
        if (! $original || ! in_array($original->status, [ElectronicDocument::STATUS_ACCEPTED, ElectronicDocument::STATUS_OBSERVED], true)) {
            throw ValidationException::withMessages(['venta' => 'Solo se puede emitir una nota sobre un comprobante aceptado por SUNAT.']);
        }
        if ($original->document_type !== '01') {
            throw ValidationException::withMessages(['venta' => 'En esta etapa Beta las notas automáticas se habilitan primero para facturas.']);
        }
        if ($sale->electronicCreditNotes()->whereIn('status', ['draft', 'ready', 'sending', 'accepted', 'observed'])->exists()) {
            throw ValidationException::withMessages(['venta' => 'Esta venta ya tiene una nota de crédito vigente o en proceso.']);
        }

        return DB::transaction(function () use ($sale, $original, $reasonCode, $reason): ElectronicCreditNote {
            $series = 'FC01';
            $number = $this->numbers->next($series);
            $snapshot = $original->snapshot;
            $snapshot['document'] = [
                'type' => '07', 'series' => $series, 'number' => str_pad((string) $number, 8, '0', STR_PAD_LEFT),
                'issued_at' => now()->format('Y-m-d H:i:s'), 'currency' => 'PEN',
            ];
            $snapshot['reference'] = [
                'document_type' => $original->document_type,
                'series_number' => $original->series.'-'.str_pad((string) $original->number, 8, '0', STR_PAD_LEFT),
                'reason_code' => $reasonCode,
                'reason' => trim($reason),
            ];

            return ElectronicCreditNote::create([
                'venta_id' => $sale->id,
                'electronic_document_id' => $original->id,
                'series' => $series,
                'number' => $number,
                'reason_code' => $reasonCode,
                'reason' => trim($reason),
                'snapshot' => $snapshot,
            ]);
        });
    }
}
