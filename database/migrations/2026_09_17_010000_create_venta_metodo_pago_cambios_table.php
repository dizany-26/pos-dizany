<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('venta_metodo_pago_cambios')) {
            return;
        }

        Schema::create('venta_metodo_pago_cambios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->unsignedBigInteger('movimiento_id')->nullable();
            $table->unsignedBigInteger('cambiado_por');
            $table->string('metodo_anterior', 30);
            $table->string('metodo_nuevo', 30);
            $table->string('motivo', 500);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['venta_id', 'created_at'], 'idx_venta_metodo_cambios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_metodo_pago_cambios');
    }
};
