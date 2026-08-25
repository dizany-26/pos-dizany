<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function ajaxStore(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        // Verificar si ya existe
        $nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');

        if (Categoria::where('nombre', $nombre)->exists()) {
            return response()->json([
                'error' => true,
                'message' => 'La categoría ya existe.'
            ]);
        }

        // Crear si no existe
        $categoria = Categoria::create([
            'nombre' => $nombre
        ]);

        return response()->json([
            'error' => false,
            'data'  => $categoria
        ]);
    }
}
?>
