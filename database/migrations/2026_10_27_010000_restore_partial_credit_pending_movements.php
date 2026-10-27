<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ventas')
            ->where('estado', 'credito')
            ->where('saldo', '>', 0)
            ->orderBy('id')
            ->chunkById(100, function ($ventas): void {
                foreach ($ventas as $venta) {
                    $movimiento = DB::table('movimientos')
                        ->where('referencia_tipo', 'venta')
                        ->where('referencia_id', $venta->id)
                        ->where('subtipo', 'venta')
                        ->whereRaw('LOWER(metodo_pago) = ?', ['credito'])
                        ->whereIn('estado', ['pendiente', 'anulado'])
                        ->orderByDesc('id')
                        ->first();

                    if (! $movimiento) {
                        continue;
                    }

                    DB::table('movimientos')->where('id', $movimiento->id)->update([
                        'monto' => round((float) $venta->saldo, 2),
                        'estado' => 'pendiente',
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Es una reparación de datos; revertirla volvería a ocultar deudas válidas.
    }
};
