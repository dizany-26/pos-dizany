<?php

namespace App\Services\Sunat;

use App\Models\ElectronicDocument;
use App\Models\SunatDailySummary;
use App\Models\Venta;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SunatDailySummaryPreparer
{
    public function __construct(private readonly ElectronicDocumentPreparer $documentPreparer)
    {
    }

    public function prepare(CarbonInterface $referenceDate): ?SunatDailySummary
    {
        return DB::transaction(function () use ($referenceDate) {
            $sales = Venta::query()
                ->where('tipo_comprobante', 'boleta')
                ->whereDate('fecha', $referenceDate->toDateString())
                ->whereNotIn('estado', ['anulada', 'cancelada'])
                ->whereDoesntHave('dailySummaryItems', fn ($query) => $query->where('condition_code', '1'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($sales->isEmpty()) {
                return null;
            }

            $issueDate = now()->startOfDay();
            $sequence = (int) SunatDailySummary::query()
                ->whereDate('issue_date', $issueDate)
                ->lockForUpdate()
                ->max('sequence') + 1;
            $summary = SunatDailySummary::create([
                'reference_date' => $referenceDate->toDateString(),
                'issue_date' => $issueDate->toDateString(),
                'sequence' => $sequence,
                'identifier' => sprintf('RC-%s-%03d', $issueDate->format('Ymd'), $sequence),
                'status' => SunatDailySummary::STATUS_DRAFT,
            ]);

            foreach ($sales as $sale) {
                $document = $this->documentPreparer->prepare($sale);
                $document->update(['status' => ElectronicDocument::STATUS_PENDING_SUMMARY]);
                $summary->items()->create([
                    'venta_id' => $sale->id,
                    'condition_code' => '1',
                    'snapshot' => $document->snapshot,
                ]);
            }

            return $summary->load('items');
        }, 3);
    }

    public function prepareCancellation(Venta $sale): SunatDailySummary
    {
        $sale->loadMissing('electronicDocument');
        if ($sale->tipo_comprobante !== 'boleta' || ! $sale->electronicDocument || ! in_array($sale->electronicDocument->status, ['accepted','observed'], true)) {
            throw ValidationException::withMessages(['venta'=>'Solo se puede comunicar la baja de una boleta aceptada.']);
        }
        if ($sale->dailySummaryItems()->where('condition_code','3')->exists()) {
            throw ValidationException::withMessages(['venta'=>'La baja de esta boleta ya fue incluida en un Resumen Diario.']);
        }
        return DB::transaction(function () use ($sale) {
            $issueDate=now()->startOfDay();
            $sequence=(int)SunatDailySummary::whereDate('issue_date',$issueDate)->lockForUpdate()->max('sequence')+1;
            $summary=SunatDailySummary::create([
                'reference_date'=>$sale->fecha->toDateString(), 'issue_date'=>$issueDate->toDateString(),
                'sequence'=>$sequence, 'identifier'=>sprintf('RC-%s-%03d',$issueDate->format('Ymd'),$sequence),
                'status'=>SunatDailySummary::STATUS_DRAFT,
            ]);
            $summary->items()->create(['venta_id'=>$sale->id,'condition_code'=>'3','snapshot'=>$sale->electronicDocument->snapshot]);
            return $summary->load('items');
        },3);
    }
}
