<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['Encargado', 'Cajero', 'Almacén'] as $nombre) {
            DB::table('roles')->updateOrInsert(['nombre' => $nombre], ['nombre' => $nombre]);
        }
    }

    public function down(): void
    {
        $idsEnUso = DB::table('usuarios')->pluck('rol_id');

        DB::table('roles')
            ->whereIn('nombre', ['Encargado', 'Cajero', 'Almacén'])
            ->whereNotIn('id', $idsEnUso)
            ->delete();
    }
};
