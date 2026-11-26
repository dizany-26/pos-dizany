<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos_catalogo', function (Blueprint $table) {
            // usuarios.id en la base recuperada es INT con signo.
            $table->integer('atendido_por_usuario_id')->nullable()->index()->after('venta_id');
            $table->timestamp('tomado_en')->nullable()->after('atendido_por_usuario_id');
            $table->foreign('atendido_por_usuario_id')
                ->references('id')
                ->on('usuarios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos_catalogo', function (Blueprint $table) {
            $table->dropForeign(['atendido_por_usuario_id']);
            $table->dropColumn(['atendido_por_usuario_id', 'tomado_en']);
        });
    }
};
