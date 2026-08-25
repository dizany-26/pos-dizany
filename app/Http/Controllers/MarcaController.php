<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    public function ajaxStore(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255'
        ]);

        // Verificar si ya existe
        $nombre = mb_strtoupper(trim($request->nombre), 'UTF-8');

        if (Marca::where('nombre', $nombre)->exists()) {
            return response()->json([
                'error' => true,
                'message' => 'La marca ya existe.'
            ]);
        }

        // Crear marca
        $marca = Marca::create([
            'nombre' => $nombre
        ]);

        return response()->json([
            'error' => false,
            'data'  => $marca
        ]);
    }

}
?>
