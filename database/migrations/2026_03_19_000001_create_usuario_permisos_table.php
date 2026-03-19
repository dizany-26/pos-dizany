<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('usuario_permisos')) {
            return;
        }

        Schema::create('usuario_permisos', function (Blueprint $table) {
            $table->id();
            // `usuarios.id` is an `INT(11)` in the existing database dump, so
            // this FK column must use the same signed integer type.
            $table->integer('usuario_id');
            $table->string('permiso', 100);
            $table->unique(['usuario_id', 'permiso']);
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_permisos');
    }
};
