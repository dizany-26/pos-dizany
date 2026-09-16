<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ventas', 'request_key')) {
            Schema::table('ventas', fn (Blueprint $table) =>
                $table->string('request_key', 100)->nullable()->unique()->after('id')
            );
        }
        if (! Schema::hasColumn('ventas', 'anulada_por')) {
            Schema::table('ventas', fn (Blueprint $table) => $table->unsignedBigInteger('anulada_por')->nullable());
        }
        if (! Schema::hasColumn('ventas', 'anulada_at')) {
            Schema::table('ventas', fn (Blueprint $table) => $table->timestamp('anulada_at')->nullable());
        }
        if (! Schema::hasColumn('ventas', 'motivo_anulacion')) {
            Schema::table('ventas', fn (Blueprint $table) => $table->string('motivo_anulacion', 500)->nullable());
        }
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique(['request_key']);
            $table->dropColumn(['request_key', 'anulada_por', 'anulada_at', 'motivo_anulacion']);
        });
    }
};
