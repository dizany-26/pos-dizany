<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\DetalleVenta;
use App\Models\Devolucion;
use App\Models\DevolucionDetalle;
use App\Models\DevolucionLote;
use App\Models\Lote;
use App\Models\LoteMovimiento;
use App\Models\Movimiento;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleReturnService
{
    public function __construct(private readonly SaleReturnCalculator $calculator)
    {
    }

    public function register(Venta $sale, array $data, int $userId): Devolucion
    {
        return DB::transaction(function () use ($sale, $data, $userId) {
            $sale = Venta::query()->lockForUpdate()->findOrFail($sale->id);
            if (! $sale->activo || $sale->estado === 'anulada') {
                throw ValidationException::withMessages(['venta_id' => 'La venta está anulada o inactiva.']);
            }

            $requested = collect($data['detalles'])->keyBy(fn ($item) => (int) $item['detalle_venta_id']);
            $details = DetalleVenta::query()
                ->where('venta_id', $sale->id)
                ->whereIn('id', $requested->keys())
                ->lockForUpdate()
                ->get();

            if ($details->count() !== $requested->count()) {
                throw ValidationException::withMessages(['detalles' => 'Uno de los productos no pertenece a la venta.']);
            }

            $prepared = [];
            $returnTotal = 0.0;
            $saleBase = (float) DetalleVenta::where('venta_id', $sale->id)->sum('subtotal');
            $grossFactor = $saleBase > 0 ? (float) $sale->total / $saleBase : 1.0;
            foreach ($details as $detail) {
                $item = $requested[(int) $detail->id];
                $quantity = (int) $item['cantidad'];
                $returned = (int) DevolucionDetalle::query()
                    ->where('detalle_venta_id', $detail->id)
                    ->whereHas('devolucion', fn ($q) => $q->where('estado', 'registrada'))
                    ->sum('cantidad');
                $available = (int) $detail->cantidad - $returned;
                if ($quantity > $available) {
                    throw ValidationException::withMessages([
                        'detalles' => "Solo quedan {$available} unidades disponibles para devolver de {$detail->producto?->nombre}.",
                    ]);
                }

                $line = $this->calculator->line(
                    (int) $detail->cantidad,
                    (int) $detail->unidades_afectadas,
                    round((float) $detail->subtotal * $grossFactor, 4),
                    $quantity
                );
                $allocations = $this->allocateLots($detail, $line['units']);
                $reversedProfit = round((float) $detail->ganancia * $quantity / max(1, (int) $detail->cantidad), 4);
                $netAmount = round((float) $detail->subtotal * $quantity / max(1, (int) $detail->cantidad), 4);
                $cost = round($netAmount - $reversedProfit, 4);
                // Si el producto no vuelve al inventario, además de perder la
                // utilidad se conserva su costo como pérdida real.
                $profitAdjustment = (bool) ($item['reintegra_stock'] ?? true)
                    ? $reversedProfit
                    : $netAmount;
                $returnTotal = round($returnTotal + $line['amount'], 2);
                $prepared[] = compact('detail', 'item', 'quantity', 'line', 'allocations', 'cost', 'profitAdjustment');
            }

            if ($returnTotal <= 0) {
                throw ValidationException::withMessages(['detalles' => 'Selecciona al menos un producto para devolver.']);
            }

            $paid = (float) $sale->pagos()->sum('monto');
            $priorReturns = (float) $sale->devoluciones()->sum('importe_total');
            $priorRefunds = (float) $sale->devoluciones()->sum('monto_reembolsado');
            $distribution = $this->calculator->distribution(
                (float) $sale->total,
                $paid,
                $priorReturns,
                $priorRefunds,
                $returnTotal
            );
            $refund = $distribution['refund'];
            $balanceReduction = min((float) $sale->saldo, $distribution['balance_reduction']);

            $cashbox = null;
            $method = $refund > 0 ? strtolower((string) ($data['metodo_reembolso'] ?? '')) : null;
            if ($refund > 0) {
                if (! in_array($method, array_keys(Caja::mediosConciliables()), true)) {
                    throw ValidationException::withMessages(['metodo_reembolso' => 'Selecciona el método del reembolso.']);
                }
                $cashbox = Caja::query()
                    ->where('usuario_id', $userId)
                    ->where('estado', 'abierta')
                    ->lockForUpdate()
                    ->latest('abierta_en')
                    ->first();
                if (! $cashbox) {
                    throw ValidationException::withMessages(['caja' => 'Abre tu caja antes de registrar un reembolso.']);
                }
                if ($method === 'efectivo' && $cashbox->calcularEfectivo()['esperado'] + 0.001 < $refund) {
                    throw ValidationException::withMessages(['metodo_reembolso' => 'La caja no tiene efectivo suficiente para este reembolso.']);
                }
            }

            $requiresCreditNote = $sale->electronicDocument()
                ->whereIn('status', ['accepted', 'observed'])->exists()
                || $sale->manualTaxDocument()->exists();

            $return = Devolucion::create([
                'venta_id' => $sale->id,
                'usuario_id' => $userId,
                'caja_id' => $cashbox?->id,
                'fecha' => now(),
                'importe_total' => $returnTotal,
                'reduccion_saldo' => $balanceReduction,
                'monto_reembolsado' => $refund,
                'metodo_reembolso' => $method,
                'estado' => 'registrada',
                'motivo' => trim($data['motivo']),
                'requiere_nota_credito' => $requiresCreditNote,
            ]);

            foreach ($prepared as $entry) {
                $returnedDetail = $return->detalles()->create([
                    'detalle_venta_id' => $entry['detail']->id,
                    'cantidad' => $entry['quantity'],
                    'unidades_afectadas' => $entry['line']['units'],
                    'importe' => $entry['line']['amount'],
                    'costo' => $entry['cost'],
                    'ganancia_revertida' => $entry['profitAdjustment'],
                    'reintegra_stock' => (bool) ($entry['item']['reintegra_stock'] ?? true),
                ]);

                foreach ($entry['allocations'] as $allocation) {
                    DevolucionLote::create([
                        'devolucion_detalle_id' => $returnedDetail->id,
                        'lote_id' => $allocation['lot']->id,
                        'cantidad' => $allocation['quantity'],
                        'precio_lote' => $allocation['price'],
                    ]);
                    if ($returnedDetail->reintegra_stock) {
                        $lot = $allocation['lot'];
                        $stockBefore = (int) $lot->stock_actual;
                        $lot->increment('stock_actual', $allocation['quantity']);
                        $stockAfter = $stockBefore + (int) $allocation['quantity'];
                        LoteMovimiento::create([
                            'lote_id' => $lot->id,
                            'usuario_id' => $userId,
                            'tipo' => 'ajuste',
                            'cantidad' => (int) $allocation['quantity'],
                            'stock_antes' => $stockBefore,
                            'stock_despues' => $stockAfter,
                            'motivo' => "Reposición por devolución {$return->codigo}",
                            'creado_en' => now(),
                        ]);
                    }
                }
            }

            if ($balanceReduction > 0) {
                $newBalance = max(0, round((float) $sale->saldo - $balanceReduction, 2));
                $sale->saldo = $newBalance;
                $sale->save();
                Movimiento::query()
                    ->where('referencia_tipo', 'venta')
                    ->where('referencia_id', $sale->id)
                    ->where('estado', 'pendiente')
                    ->update([
                        'monto' => $newBalance,
                        'estado' => $newBalance > 0 ? 'pendiente' : 'anulado',
                    ]);
            }

            if ($refund > 0) {
                Movimiento::create([
                    'caja_id' => $cashbox->id,
                    'usuario_id' => $userId,
                    'fecha' => now()->toDateString(),
                    'hora' => now()->format('H:i:s'),
                    'tipo' => 'egreso',
                    'subtipo' => 'devolucion',
                    'concepto' => "Reembolso {$return->codigo} · venta {$sale->serie}-".str_pad((string) $sale->correlativo, 6, '0', STR_PAD_LEFT),
                    'monto' => $refund,
                    'metodo_pago' => $method,
                    'estado' => 'pagado',
                    'referencia_id' => $return->id,
                    'referencia_tipo' => 'devolucion',
                ]);
            }

            return $return->load(['venta.cliente', 'usuario', 'detalles.detalleVenta.producto']);
        });
    }

    private function allocateLots(DetalleVenta $detail, int $units): array
    {
        $allocations = [];
        $remaining = $units;
        $originals = $detail->lotes()->orderBy('id')->get();
        foreach ($originals as $original) {
            $alreadyReturned = (int) DevolucionLote::query()
                ->where('lote_id', $original->lote_id)
                ->whereHas('detalle', function ($q) use ($detail) {
                    $q->where('detalle_venta_id', $detail->id)
                        ->whereHas('devolucion', fn ($return) => $return->where('estado', 'registrada'));
                })->sum('cantidad');
            $available = max(0, (int) $original->cantidad - $alreadyReturned);
            if ($available === 0) continue;
            $take = min($remaining, $available);
            $lot = Lote::query()->lockForUpdate()->findOrFail($original->lote_id);
            $allocations[] = [
                'lot' => $lot,
                'quantity' => $take,
                'price' => (float) $original->precio_lote,
            ];
            $remaining -= $take;
            if ($remaining === 0) break;
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'detalles' => 'No se pudo identificar el lote original completo. No se realizó ningún cambio.',
            ]);
        }
        return $allocations;
    }
}
