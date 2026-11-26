<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas_borrador', function (Blueprint $table) {
            $table->id();
            // usuarios.id proviene de la base original y es INT con signo.
            $table->integer('usuario_id');
            $table->string('uuid', 80);
            $table->json('datos')->nullable();
            $table->timestamp('eliminado_en')->nullable()->index();
            $table->timestamps();

            $table->unique(['usuario_id', 'uuid']);
            $table->index('usuario_id');
            $table->foreign('usuario_id')
                ->references('id')
                ->on('usuarios')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas_borrador');
    }
};
