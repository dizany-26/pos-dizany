<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->json('light_theme')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('configuracion', function (Blueprint $table) {
            $table->dropColumn('light_theme');
        });
    }
};
