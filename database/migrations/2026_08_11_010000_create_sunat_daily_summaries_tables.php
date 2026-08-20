<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sunat_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('reference_date');
            $table->date('issue_date');
            $table->unsignedSmallInteger('sequence');
            $table->string('identifier', 30)->unique();
            $table->string('status', 30)->default('draft');
            $table->string('ticket', 100)->nullable()->index();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('xml_hash', 128)->nullable();
            $table->string('sunat_code', 20)->nullable();
            $table->text('sunat_description')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['issue_date', 'sequence'], 'sunat_daily_summary_sequence_unique');
            $table->index(['status', 'reference_date']);
        });

        Schema::create('sunat_daily_summary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sunat_daily_summary_id')->constrained()->cascadeOnDelete();
            // La tabla histórica ventas usa INT firmado, no BIGINT unsigned.
            $table->integer('venta_id');
            $table->string('condition_code', 2)->default('1');
            $table->json('snapshot');
            $table->timestamps();

            $table->unique(['sunat_daily_summary_id', 'venta_id'], 'sunat_summary_sale_unique');
            $table->index(['venta_id', 'condition_code']);
            $table->foreign('venta_id')->references('id')->on('ventas')->restrictOnDelete();
        });

        Schema::create('sunat_daily_summary_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sunat_daily_summary_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('operation', 20);
            $table->string('result', 30);
            $table->string('sunat_code', 20)->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->unique(['sunat_daily_summary_id', 'attempt_number'], 'sunat_summary_attempt_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunat_daily_summary_attempts');
        Schema::dropIfExists('sunat_daily_summary_items');
        Schema::dropIfExists('sunat_daily_summaries');
    }
};
