<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobante_series', function (Blueprint $table) {
            $table->string('serie', 10)->primary();
            $table->unsignedBigInteger('ultimo_correlativo')->default(0);
            $table->timestamps();
        });

        foreach (['B001', 'F001', 'NV01'] as $serie) {
            DB::table('comprobante_series')->insert([
                'serie' => $serie,
                'ultimo_correlativo' => (int) (
                    DB::table('ventas')->where('serie', $serie)->max('correlativo') ?? 0
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('ventas', function (Blueprint $table) {
            $table->unique(
                ['serie', 'correlativo'],
                'ventas_serie_correlativo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique('ventas_serie_correlativo_unique');
        });

        Schema::dropIfExists('comprobante_series');
    }
};
