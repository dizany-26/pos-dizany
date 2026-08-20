<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sunat_settings', function (Blueprint $table) {
            $table->string('fiscal_ruc', 11)->nullable()->after('environment');
            $table->string('legal_name')->nullable()->after('fiscal_ruc');
            $table->string('trade_name')->nullable()->after('legal_name');
        });

        Schema::create('sunat_establishments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 4)->default('0000');
            $table->string('name')->nullable();
            $table->string('ubigeo', 6);
            $table->string('department', 100);
            $table->string('province', 100);
            $table->string('district', 100);
            $table->string('address');
            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('code');
            $table->index(['active', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunat_establishments');
        Schema::table('sunat_settings', function (Blueprint $table) {
            $table->dropColumn(['fiscal_ruc', 'legal_name', 'trade_name']);
        });
    }
};
