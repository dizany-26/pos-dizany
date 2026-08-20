<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('tax_regime', 30);
            $table->string('emission_system', 40);
            $table->string('environment', 20)->default('beta');
            $table->string('default_tax_treatment', 20)->default('gravada');
            $table->decimal('igv_rate', 5, 2)->default(18);
            $table->boolean('active')->default(false);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->integer('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['active', 'valid_from']);
            $table->foreign('confirmed_by')->references('id')->on('usuarios')->nullOnDelete();
        });

        Schema::create('tax_profile_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_profile_id')->constrained()->cascadeOnDelete();
            $table->string('capability', 60);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['tax_profile_id', 'capability']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('tax_profile_id')->nullable()->after('usuario_id')->constrained()->nullOnDelete();
            $table->string('emission_system', 40)->nullable()->after('tipo_comprobante');
            $table->string('tax_treatment', 20)->default('gravada')->after('emission_system');
            $table->decimal('igv_rate', 5, 2)->default(0)->after('tax_treatment');
            $table->decimal('op_exoneradas', 12, 2)->default(0)->after('op_gravadas');
            $table->decimal('op_inafectas', 12, 2)->default(0)->after('op_exoneradas');
        });

        Schema::create('manual_tax_documents', function (Blueprint $table) {
            $table->id();
            $table->integer('venta_id');
            $table->string('document_type', 20);
            $table->string('series', 10);
            $table->unsignedBigInteger('number');
            $table->dateTime('issued_at');
            $table->decimal('total', 12, 2);
            $table->string('status', 20)->default('issued');
            $table->string('pdf_path')->nullable();
            $table->text('notes')->nullable();
            $table->integer('linked_by')->nullable();
            $table->timestamps();

            $table->unique('venta_id');
            $table->unique(['document_type', 'series', 'number']);
            $table->foreign('venta_id')->references('id')->on('ventas')->cascadeOnDelete();
            $table->foreign('linked_by')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_tax_documents');
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_profile_id');
            $table->dropColumn(['emission_system', 'tax_treatment', 'igv_rate', 'op_exoneradas', 'op_inafectas']);
        });
        Schema::dropIfExists('tax_profile_capabilities');
        Schema::dropIfExists('tax_profiles');
    }
};
