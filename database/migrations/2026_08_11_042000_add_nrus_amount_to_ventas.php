<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('op_nrus', 12, 2)->default(0)->after('op_inafectas');
        });

        DB::table('tax_profiles')->where('tax_regime', 'nrus')->update([
            'default_tax_treatment' => 'nrus_no_desglosado',
            'igv_rate' => 0,
        ]);
        if (DB::table('tax_profiles')->where('active', true)->where('tax_regime', 'nrus')->exists()) {
            DB::table('configuracion')->update(['igv' => 0]);
        }
    }

    public function down(): void
    {
        Schema::table('ventas', fn (Blueprint $table) => $table->dropColumn('op_nrus'));
    }
};
