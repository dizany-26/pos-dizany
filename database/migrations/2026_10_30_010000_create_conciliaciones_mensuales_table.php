<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conciliaciones_mensuales')) {
            return;
        }

        Schema::create('conciliaciones_mensuales', function (Blueprint $table) {
            $table->id();
            $table->date('periodo')->unique();
            // La tabla usuarios es heredada y usa INT firmado, no BIGINT.
            // Mantener el mismo tipo evita incompatibilidades en MySQL/Helio.
            $table->integer('usuario_id')->index();
            $table->json('esperado');
            $table->json('declarado');
            $table->decimal('total_esperado', 12, 2);
            $table->decimal('total_declarado', 12, 2);
            $table->decimal('diferencia', 12, 2);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliaciones_mensuales');
    }
};
