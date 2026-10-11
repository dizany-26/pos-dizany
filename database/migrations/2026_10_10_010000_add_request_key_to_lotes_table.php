<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lotes', 'request_key')) {
            Schema::table('lotes', function (Blueprint $table) {
                $table->string('request_key', 100)->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lotes', 'request_key')) {
            Schema::table('lotes', function (Blueprint $table) {
                $table->dropUnique(['request_key']);
                $table->dropColumn('request_key');
            });
        }
    }
};
