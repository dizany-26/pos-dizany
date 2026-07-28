<?php

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class SaleLineCalculator
{
    public function calculate(
        Producto $product,
        Collection $lots,
        int $quantity,
        string $presentation
    ): array {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }

        $unitsPerPresentation = $this->unitsPerPresentation($product, $presentation);
        $requiredUnits = $quantity * $unitsPerPresentation;
        $availableUnits = (int) $lots->sum('stock_actual');

        if ($availableUnits < $requiredUnits) {
            throw new RuntimeException(
                "STOCK|{$product->id}|{$product->nombre}|{$availableUnits}|{$requiredUnits}"
            );
        }

        $remainingUnits = $requiredUnits;
        $subtotal = 0.0;
        $cost = 0.0;
        $allocations = [];

        foreach ($lots as $lot) {
            if ($remainingUnits <= 0) {
                break;
            }

            $unitsUsed = min((int) $lot->stock_actual, $remainingUnits);
            if ($unitsUsed <= 0) {
                continue;
            }

            $presentationPrice = $this->presentationPrice($product, $presentation);
            if ($presentationPrice <= 0) {
                throw new RuntimeException(
                    "No hay precio público de {$presentation} definido para {$product->nombre}."
                );
            }

            $unitSalePrice = $presentationPrice / $unitsPerPresentation;
            $allocationSubtotal = round($unitsUsed * $unitSalePrice, 2);
            $allocationCost = round($unitsUsed * (float) $lot->precio_compra, 2);

            $allocations[] = [
                'lot' => $lot,
                'units' => $unitsUsed,
                'presentation_price' => $presentationPrice,
                'unit_sale_price' => $unitSalePrice,
                'subtotal' => $allocationSubtotal,
                'cost' => $allocationCost,
            ];

            $subtotal += $allocationSubtotal;
            $cost += $allocationCost;
            $remainingUnits -= $unitsUsed;
        }

        return [
            'quantity' => $quantity,
            'presentation' => $presentation,
            'units_per_presentation' => $unitsPerPresentation,
            'required_units' => $requiredUnits,
            'subtotal' => round($subtotal, 2),
            'cost' => round($cost, 2),
            'profit' => round($subtotal - $cost, 2),
            'average_presentation_price' => round($subtotal / $quantity, 2),
            'average_unit_price' => round($subtotal / $requiredUnits, 4),
            'allocations' => $allocations,
        ];
    }

    public function unitsPerPresentation(Producto $product, string $presentation): int
    {
        $units = match ($presentation) {
            'unidad' => 1,
            'paquete' => (int) $product->unidades_por_paquete,
            'caja' => $this->unitsPerBox($product),
            default => 0,
        };

        if ($units <= 0) {
            throw new InvalidArgumentException(
                "La presentación {$presentation} no está configurada para {$product->nombre}."
            );
        }

        return $units;
    }

    private function unitsPerBox(Producto $product): int
    {
        if ((int) $product->unidades_por_caja > 0) {
            return (int) $product->unidades_por_caja;
        }

        return (int) $product->unidades_por_paquete
            * (int) $product->paquetes_por_caja;
    }

    private function presentationPrice(Producto $product, string $presentation): float
    {
        return match ($presentation) {
            'unidad' => (float) $product->precio_venta,
            'paquete' => (float) $product->precio_paquete,
            'caja' => (float) $product->precio_caja,
            default => 0.0,
        };
    }
}
