<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->string('tipo_comprobante', 30)->nullable()->after('codigo_comprobante');
            $table->string('condicion_pago', 20)->default('contado')->after('tipo_comprobante');
            $table->string('metodo_pago', 30)->nullable()->after('condicion_pago');
            $table->date('fecha_vencimiento_pago')->nullable()->after('metodo_pago');
            $table->string('observaciones_compra', 500)->nullable()->after('fecha_vencimiento_pago');
        });
    }

    public function down(): void
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_comprobante',
                'condicion_pago',
                'metodo_pago',
                'fecha_vencimiento_pago',
                'observaciones_compra',
            ]);
        });
    }
};
