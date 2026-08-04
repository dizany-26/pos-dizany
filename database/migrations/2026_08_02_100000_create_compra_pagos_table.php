<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compra_pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('movimiento_id');
            $table->integer('lote_id');
            $table->integer('usuario_id');
            $table->decimal('monto', 12, 2);
            $table->date('fecha');
            $table->string('metodo_pago', 30);
            $table->string('numero_operacion', 80)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->index(['movimiento_id', 'fecha']);
            $table->foreign('movimiento_id')->references('id')->on('movimientos')->cascadeOnDelete();
            $table->foreign('lote_id')->references('id')->on('lotes')->cascadeOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compra_pagos');
    }
};
