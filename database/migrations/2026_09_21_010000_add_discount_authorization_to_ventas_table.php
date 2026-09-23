<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // usuarios.id es INTEGER en el esquema legado; la FK debe usar el mismo tipo.
            $table->integer('descuento_autorizado_por')->nullable()->after('usuario_id');
            $table->dateTime('descuento_autorizado_at')->nullable()->after('descuento_autorizado_por');
            $table->foreign('descuento_autorizado_por')
                ->references('id')
                ->on('usuarios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['descuento_autorizado_por']);
            $table->dropColumn(['descuento_autorizado_por', 'descuento_autorizado_at']);
        });
    }
};
