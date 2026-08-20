<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // DIZANY autentica con App\Models\User sobre la tabla legada `usuarios`.
        // No crear la tabla estándar `users`, porque no forma parte del sistema.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Sin cambios: esta migración no crea ninguna tabla.
    }
};
