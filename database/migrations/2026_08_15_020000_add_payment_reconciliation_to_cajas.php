<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->json('metodos_esperados')->nullable()->after('diferencia');
            $table->json('metodos_declarados')->nullable()->after('metodos_esperados');
            $table->json('metodos_diferencias')->nullable()->after('metodos_declarados');
        });
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->dropColumn(['metodos_esperados', 'metodos_declarados', 'metodos_diferencias']);
        });
    }
};
