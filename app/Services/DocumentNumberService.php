<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DocumentNumberService
{
    public function seriesFor(string $documentType): string
    {
        return match ($documentType) {
            'boleta' => 'B001',
            'factura' => 'F001',
            'nota_venta' => 'NV01',
            default => throw new InvalidArgumentException('Tipo de comprobante no válido.'),
        };
    }

    /**
     * Must be called inside the same transaction that creates the sale.
     */
    public function next(string $series): int
    {
        DB::table('comprobante_series')->insertOrIgnore([
            'serie' => $series,
            'ultimo_correlativo' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counter = DB::table('comprobante_series')
            ->where('serie', $series)
            ->lockForUpdate()
            ->first();

        if (!$counter) {
            throw new RuntimeException("No se pudo bloquear la serie {$series}.");
        }

        $next = (int) $counter->ultimo_correlativo + 1;

        DB::table('comprobante_series')
            ->where('serie', $series)
            ->update([
                'ultimo_correlativo' => $next,
                'updated_at' => now(),
            ]);

        return $next;
    }
}
