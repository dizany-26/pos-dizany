<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            // Estas tablas son heredadas y conservan PK INT firmadas.
            $table->integer('venta_id');
            $table->integer('usuario_id');
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->dateTime('fecha');
            $table->decimal('importe_total', 12, 2);
            $table->decimal('reduccion_saldo', 12, 2)->default(0);
            $table->decimal('monto_reembolsado', 12, 2)->default(0);
            $table->string('metodo_reembolso', 30)->nullable();
            $table->string('estado', 20)->default('registrada');
            $table->string('motivo', 500);
            $table->boolean('requiere_nota_credito')->default(false);
            $table->timestamps();
            $table->index(['venta_id', 'estado']);
            $table->index(['fecha', 'metodo_reembolso']);
            $table->foreign('venta_id')->references('id')->on('ventas');
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });

        Schema::create('devolucion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_id')->constrained('devoluciones')->cascadeOnDelete();
            $table->integer('detalle_venta_id');
            $table->unsignedInteger('cantidad');
            $table->unsignedInteger('unidades_afectadas');
            $table->decimal('importe', 12, 2);
            $table->decimal('costo', 12, 4)->default(0);
            $table->decimal('ganancia_revertida', 12, 4)->default(0);
            $table->boolean('reintegra_stock')->default(true);
            $table->timestamps();
            $table->index(['detalle_venta_id', 'devolucion_id'], 'idx_dev_detalle_venta');
            $table->foreign('detalle_venta_id')->references('id')->on('detalle_ventas');
        });

        Schema::create('devolucion_lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_detalle_id')->constrained('devolucion_detalles')->cascadeOnDelete();
            $table->integer('lote_id');
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_lote', 12, 6)->default(0);
            $table->timestamps();
            $table->index(['lote_id', 'devolucion_detalle_id'], 'idx_dev_lote_detalle');
            $table->foreign('lote_id')->references('id')->on('lotes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolucion_lotes');
        Schema::dropIfExists('devolucion_detalles');
        Schema::dropIfExists('devoluciones');
    }
};
