<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_venta', 12, 2)->nullable();
            $table->decimal('precio_paquete', 12, 2)->nullable();
            $table->decimal('precio_caja', 12, 2)->nullable();
        });

        DB::table('productos')
            ->select('id')
            ->orderBy('id')
            ->each(function ($producto) {
                $loteConStock = DB::table('lotes')
                    ->where('producto_id', $producto->id)
                    ->where('activo', 1)
                    ->where('stock_actual', '>', 0)
                    ->orderByRaw('fecha_vencimiento IS NULL')
                    ->orderBy('fecha_vencimiento')
                    ->orderBy('fecha_ingreso')
                    ->orderBy('id')
                    ->first();

                if ($loteConStock) {
                    DB::table('productos')
                        ->where('id', $producto->id)
                        ->update([
                            'precio_venta' => $loteConStock->precio_unidad,
                            'precio_paquete' => $loteConStock->precio_paquete,
                            'precio_caja' => $loteConStock->precio_caja,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['precio_venta', 'precio_paquete', 'precio_caja']);
        });
    }
};
