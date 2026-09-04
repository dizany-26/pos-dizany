<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 32)->unique();
            $table->string('cliente_nombre', 160);
            $table->string('cliente_telefono', 30);
            $table->string('tipo_entrega', 20);
            $table->text('direccion')->nullable();
            $table->json('items');
            $table->decimal('total', 12, 2);
            $table->string('estado', 20)->default('pendiente')->index();
            $table->unsignedBigInteger('venta_id')->nullable()->index();
            $table->timestamp('enviado_en')->nullable();
            $table->timestamp('atendido_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_catalogo');
    }
};
