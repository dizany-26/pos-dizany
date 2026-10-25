<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lote_costo_correcciones')) {
            Schema::create('lote_costo_correcciones', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('lote_id');
                $table->integer('usuario_id')->nullable();
                $table->decimal('costo_anterior', 12, 6);
                $table->decimal('costo_nuevo', 12, 6);
                $table->unsignedInteger('unidades_vendidas')->default(0);
                $table->unsignedInteger('detalles_venta_afectados')->default(0);
                $table->string('motivo', 500);
                $table->timestamps();

                $table->index('lote_id');
                $table->index('usuario_id');
                $table->foreign('lote_id')->references('id')->on('lotes')->cascadeOnDelete();
                $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lote_costo_correcciones');
    }
};
