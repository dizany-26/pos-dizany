<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Configuracion;

class ConfiguracionController extends Controller
{
    /**
     * Mostrar el formulario de configuración.
     */
    public function index()
    {
        $config = Configuracion::first(); // Solo hay un registro
        return view('configuracion.index', compact('config'));
    }

    /**
     * Guardar los cambios en la configuración.
     */
    public function update(Request $request)
    {
        $request->validate([
            'nombre_empresa' => 'required|string|max:100',
            'ruc'            => 'required|string|max:20',
            'moneda'         => 'required|string|max:10',
            'igv'            => 'required|numeric',
            'direccion'      => 'nullable|string',
            'telefono'       => 'nullable|string|max:20',
            'correo'         => 'nullable|email|max:100',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240'
        ]);

        $config = Configuracion::first();

        // Procesar logo si se sube uno nuevo
        if ($request->hasFile('logo')) {
            if ($config->logo && file_exists(public_path($config->logo))) {
                unlink(public_path($config->logo));
            }
            $nombreArchivo = time() . '_' . $request->file('logo')->getClientOriginalName();
            $request->file('logo')->move(public_path('uploads/logos'), $nombreArchivo);
            $config->logo = 'uploads/logos/' . $nombreArchivo;
        }

        // Actualizar los demás campos
        $config->nombre_empresa = $request->nombre_empresa;
        $config->ruc            = $request->ruc;
        $config->moneda         = $request->moneda;
        $config->igv            = $request->igv;
        $config->direccion      = $request->direccion;
        $config->telefono       = $request->telefono;
        $config->correo         = $request->correo;

        $config->save();

        return redirect()->back()->with('success', 'Configuración actualizada correctamente.');
    }
}
