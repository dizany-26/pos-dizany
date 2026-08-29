<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('efectivo_recibido', 12, 2)->nullable()->after('saldo');
            $table->decimal('vuelto', 12, 2)->nullable()->after('efectivo_recibido');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['efectivo_recibido', 'vuelto']);
        });
    }
};
