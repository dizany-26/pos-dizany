<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->integer('usuario_id');
            $table->dateTime('abierta_en');
            $table->decimal('monto_inicial', 12, 2)->default(0);
            $table->dateTime('cerrada_en')->nullable();
            $table->decimal('ingresos_efectivo', 12, 2)->nullable();
            $table->decimal('egresos_efectivo', 12, 2)->nullable();
            $table->decimal('monto_esperado', 12, 2)->nullable();
            $table->decimal('monto_contado', 12, 2)->nullable();
            $table->decimal('diferencia', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->timestamps();

            $table->index(['usuario_id', 'estado']);
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });

        Schema::table('movimientos', function (Blueprint $table) {
            $table->unsignedBigInteger('caja_id')->nullable()->after('id');
            $table->integer('usuario_id')->nullable()->after('caja_id');
            $table->index('caja_id');
            $table->index('usuario_id');
            $table->foreign('caja_id')->references('id')->on('cajas')->nullOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropForeign(['caja_id']);
            $table->dropForeign(['usuario_id']);
            $table->dropColumn(['caja_id', 'usuario_id']);
        });

        Schema::dropIfExists('cajas');
    }
};
