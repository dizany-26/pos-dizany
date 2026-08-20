<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            // `productos.id` proviene del esquema legado y es INT, no BIGINT.
            $table->integer('producto_id');
            $table->string('imagen');
            $table->unsignedTinyInteger('orden')->default(1);
            $table->timestamps();

            $table->index(['producto_id', 'orden']);
            $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_imagenes');
    }
};
