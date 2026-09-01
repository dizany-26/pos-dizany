<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pagos_venta')) {
            Schema::create('pagos_venta', function (Blueprint $table) {
                $table->id();
                $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
                $table->foreignId('usuario_id')->constrained('usuarios');
                $table->decimal('monto', 12, 2);
                $table->string('metodo_pago', 30);
                $table->decimal('efectivo_recibido', 12, 2)->nullable();
                $table->decimal('vuelto', 12, 2)->nullable();
                $table->dateTime('fecha_pago')->useCurrent();
                $table->timestamps();
                $table->index(['venta_id', 'metodo_pago'], 'idx_pagos_venta_metodo');
            });

            return;
        }

        Schema::table('pagos_venta', function (Blueprint $table) {
            if (! Schema::hasColumn('pagos_venta', 'efectivo_recibido')) {
                $table->decimal('efectivo_recibido', 12, 2)->nullable()->after('metodo_pago');
            }
            if (! Schema::hasColumn('pagos_venta', 'vuelto')) {
                $table->decimal('vuelto', 12, 2)->nullable()->after('efectivo_recibido');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pagos_venta')) {
            return;
        }

        Schema::table('pagos_venta', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['efectivo_recibido', 'vuelto'],
                fn (string $column) => Schema::hasColumn('pagos_venta', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
