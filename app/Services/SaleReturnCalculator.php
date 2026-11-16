<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class SaleReturnCalculator
{
    public function line(int $soldQuantity, int $affectedUnits, float $subtotal, int $returnQuantity): array
    {
        if ($soldQuantity < 1 || $returnQuantity < 1 || $returnQuantity > $soldQuantity) {
            throw ValidationException::withMessages([
                'detalles' => 'La cantidad a devolver no es válida.',
            ]);
        }

        $units = $affectedUnits * $returnQuantity / $soldQuantity;
        if (abs($units - round($units)) > 0.000001) {
            throw ValidationException::withMessages([
                'detalles' => 'La presentación seleccionada no permite una devolución parcial exacta de inventario.',
            ]);
        }

        return [
            'units' => (int) round($units),
            'amount' => round($subtotal * $returnQuantity / $soldQuantity, 2),
        ];
    }

    public function distribution(
        float $saleTotal,
        float $paid,
        float $priorReturns,
        float $priorRefunds,
        float $currentReturn
    ): array {
        $initialUnpaid = max(0, round($saleTotal - $paid, 2));
        $allReturns = round($priorReturns + $currentReturn, 2);
        $targetRefund = min($paid, max(0, round($allReturns - $initialUnpaid, 2)));
        $refund = max(0, round($targetRefund - $priorRefunds, 2));

        return [
            'refund' => (float) min($currentReturn, $refund),
            'balance_reduction' => (float) max(0, round($currentReturn - $refund, 2)),
        ];
    }
}
