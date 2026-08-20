<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tax_profiles DROP FOREIGN KEY tax_profiles_confirmed_by_foreign');
        DB::statement('ALTER TABLE manual_tax_documents DROP FOREIGN KEY manual_tax_documents_linked_by_foreign');

        DB::statement('ALTER TABLE tax_profiles MODIFY confirmed_by INT NULL');
        DB::statement('ALTER TABLE manual_tax_documents MODIFY linked_by INT NULL');

        DB::statement('ALTER TABLE tax_profiles ADD CONSTRAINT tax_profiles_confirmed_by_foreign FOREIGN KEY (confirmed_by) REFERENCES usuarios(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE manual_tax_documents ADD CONSTRAINT manual_tax_documents_linked_by_foreign FOREIGN KEY (linked_by) REFERENCES usuarios(id) ON DELETE SET NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tax_profiles DROP FOREIGN KEY tax_profiles_confirmed_by_foreign');
        DB::statement('ALTER TABLE manual_tax_documents DROP FOREIGN KEY manual_tax_documents_linked_by_foreign');

        DB::statement('ALTER TABLE tax_profiles MODIFY confirmed_by BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE manual_tax_documents MODIFY linked_by BIGINT UNSIGNED NULL');

        DB::statement('ALTER TABLE tax_profiles ADD CONSTRAINT tax_profiles_confirmed_by_foreign FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE manual_tax_documents ADD CONSTRAINT manual_tax_documents_linked_by_foreign FOREIGN KEY (linked_by) REFERENCES users(id) ON DELETE SET NULL');
    }
};
