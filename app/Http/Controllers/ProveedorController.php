<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function index(Request $request)
{
    $q = $request->search;

    $proveedores = Proveedor::query()
        ->when($q, function($query) use ($q) {
            $query->where('nombre', 'like', "%{$q}%")
                  ->orWhere('numero_documento', 'like', "%{$q}%")
                  ->orWhere('tipo_documento', 'like', "%{$q}%")
                  ->orWhere('contacto', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
        })
        ->latest()
        ->get();

    return view('proveedores.index', compact('proveedores'));
}

public function edit($id)
{
    $proveedor = Proveedor::findOrFail($id);
    return response()->json($proveedor);
}

public function verificarDocumento(Request $request)
{
    $data = $request->validate([
        'numero' => 'required|string|max:20',
        'excepto' => 'nullable|integer',
    ]);

    $proveedor = Proveedor::query()
        ->where('numero_documento', $data['numero'])
        ->when($data['excepto'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
        ->first(['id', 'nombre', 'tipo_documento', 'numero_documento']);

    return response()->json([
        'existe' => (bool) $proveedor,
        'proveedor' => $proveedor,
    ]);
}

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:150',
            'tipo_documento' => 'required|in:RUC,DNI,OTRO',
            'numero_documento' => [
                'required',
                'string',
                'max:20',
                'unique:proveedores,numero_documento',
                function ($attribute, $value, $fail) use ($request) {
                    $longitud = $request->tipo_documento === 'DNI' ? 8 : ($request->tipo_documento === 'RUC' ? 11 : null);
                    if ($longitud && (! ctype_digit($value) || strlen($value) !== $longitud)) {
                        $fail("El documento debe contener {$longitud} dígitos.");
                    }
                },
            ],
            'contacto' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:255',
        ]);

        Proveedor::create([
            'nombre' => $request->nombre,
            'tipo_documento' => $request->tipo_documento,
            'numero_documento' => $request->numero_documento,
            'contacto' => $request->contacto,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'direccion' => $request->direccion,
            'estado' => 1
        ]);

        return redirect()->route('proveedores.index')
            ->with('success', 'Proveedor registrado correctamente');
    }

    public function update(Request $request, $id)
{
    $proveedor = Proveedor::findOrFail($id);

    $data = $request->validate([
        'nombre' => 'required|string|max:255',
        'tipo_documento' => 'required|string|max:10',
        'numero_documento' => [
            'required',
            'string',
            'max:30',
            Rule::unique('proveedores', 'numero_documento')->ignore($proveedor->id),
            function ($attribute, $value, $fail) use ($request) {
                $longitud = $request->tipo_documento === 'DNI' ? 8 : ($request->tipo_documento === 'RUC' ? 11 : null);
                if ($longitud && (! ctype_digit($value) || strlen($value) !== $longitud)) {
                    $fail("El documento debe contener {$longitud} dígitos.");
                }
            },
        ],
        'contacto' => 'nullable|string|max:255',
        'telefono' => 'nullable|string|max:30',
        'email' => 'nullable|email|max:255',
        'direccion' => 'nullable|string|max:255',
        'estado' => 'required|in:0,1',
    ]);

    $proveedor->update($data);

    return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado correctamente.');
}
}
