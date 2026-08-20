<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sunat_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('environment', 20)->default('beta');
            $table->string('sol_user')->nullable();
            $table->text('sol_password')->nullable();
            $table->string('certificate_path')->nullable();
            $table->text('certificate_password')->nullable();
            $table->timestamp('certificate_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('electronic_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venta_id');
            $table->string('document_type', 2);
            $table->string('series', 10);
            $table->unsignedBigInteger('number');
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

            $table->unique('venta_id');
            $table->unique(['document_type', 'series', 'number'], 'electronic_document_number_unique');
            $table->index(['status', 'created_at']);
        });

        Schema::create('electronic_document_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electronic_document_id')
                ->constrained('electronic_documents')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('environment', 20);
            $table->string('result', 30);
            $table->string('sunat_code', 20)->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->unique(['electronic_document_id', 'attempt_number'], 'electronic_attempt_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_document_attempts');
        Schema::dropIfExists('electronic_documents');
        Schema::dropIfExists('sunat_settings');
    }
};
