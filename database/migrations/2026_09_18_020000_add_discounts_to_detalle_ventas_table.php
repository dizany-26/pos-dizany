<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->decimal('precio_original_presentacion', 12, 4)->nullable()->after('precio_presentacion');
            $table->string('descuento_tipo', 12)->nullable()->after('precio_original_presentacion');
            $table->decimal('descuento_valor', 12, 4)->default(0)->after('descuento_tipo');
            $table->decimal('descuento_monto', 12, 2)->default(0)->after('descuento_valor');
            $table->string('descuento_motivo', 255)->nullable()->after('descuento_monto');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropColumn([
                'precio_original_presentacion',
                'descuento_tipo',
                'descuento_valor',
                'descuento_monto',
                'descuento_motivo',
            ]);
        });
    }
};
