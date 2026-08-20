<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronic_credit_notes', function (Blueprint $table) {
            $table->id();
            // La tabla ventas es heredada y usa INT con signo.
            $table->integer('venta_id');
            $table->unsignedBigInteger('electronic_document_id');
            $table->string('document_type', 2)->default('07');
            $table->string('series', 10);
            $table->unsignedBigInteger('number');
            $table->string('reason_code', 2);
            $table->string('reason', 250);
            $table->string('status', 30)->default('draft');
            $table->json('snapshot');
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('xml_hash', 128)->nullable();
            $table->string('sunat_code', 20)->nullable();
            $table->text('sunat_description')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('venta_id')->references('id')->on('ventas')->restrictOnDelete();
            $table->foreign('electronic_document_id')->references('id')->on('electronic_documents')->restrictOnDelete();
            $table->unique(['document_type', 'series', 'number'], 'electronic_credit_note_number_unique');
            $table->index(['venta_id', 'status']);
        });

        Schema::create('electronic_credit_note_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('electronic_credit_note_id');
            $table->unsignedSmallInteger('attempt_number');
            $table->string('environment', 20);
            $table->string('result', 30);
            $table->string('sunat_code', 20)->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();
            $table->unique(['electronic_credit_note_id', 'attempt_number'], 'credit_note_attempt_number_unique');
            $table->foreign('electronic_credit_note_id', 'credit_note_attempt_note_fk')
                ->references('id')->on('electronic_credit_notes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_credit_note_attempts');
        Schema::dropIfExists('electronic_credit_notes');
    }
};
