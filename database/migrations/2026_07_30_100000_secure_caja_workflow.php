<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE cajas MODIFY estado ENUM('abierta','pendiente_cierre','cerrada') NOT NULL DEFAULT 'abierta'");

        Schema::table('cajas', function (Blueprint $table) {
            $table->integer('abierta_por')->nullable()->after('usuario_id');
            $table->dateTime('cierre_solicitado_en')->nullable()->after('monto_inicial');
            $table->decimal('monto_declarado', 12, 2)->nullable()->after('cierre_solicitado_en');
            $table->integer('cerrada_por')->nullable()->after('cerrada_en');
            $table->integer('aprobada_por')->nullable()->after('cerrada_por');

            $table->foreign('abierta_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('cerrada_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('aprobada_por')->references('id')->on('usuarios')->nullOnDelete();
        });

        Schema::create('caja_operaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('caja_id');
            $table->integer('usuario_id');
            $table->enum('tipo', ['refuerzo', 'retiro']);
            $table->decimal('monto', 12, 2);
            $table->string('origen_destino', 120);
            $table->string('motivo', 255);
            $table->timestamps();

            $table->index(['caja_id', 'tipo']);
            $table->foreign('caja_id')->references('id')->on('cajas')->cascadeOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_operaciones');

        Schema::table('cajas', function (Blueprint $table) {
            $table->dropForeign(['abierta_por']);
            $table->dropForeign(['cerrada_por']);
            $table->dropForeign(['aprobada_por']);
            $table->dropColumn([
                'abierta_por',
                'cierre_solicitado_en',
                'monto_declarado',
                'cerrada_por',
                'aprobada_por',
            ]);
        });

        DB::statement("ALTER TABLE cajas MODIFY estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta'");
    }
};
