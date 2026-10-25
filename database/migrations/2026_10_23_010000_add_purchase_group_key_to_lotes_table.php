<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('lotes', 'purchase_group_key')) {
            Schema::table('lotes', function (Blueprint $table) {
                $table->uuid('purchase_group_key')->nullable()->index()->after('request_key');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lotes', 'purchase_group_key')) {
            Schema::table('lotes', function (Blueprint $table) {
                $table->dropIndex(['purchase_group_key']);
                $table->dropColumn('purchase_group_key');
            });
        }
    }
};
