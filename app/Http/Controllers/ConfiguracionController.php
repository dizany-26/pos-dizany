<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Configuracion;
use App\Services\Tax\TaxProfileService;

class ConfiguracionController extends Controller
{
    /**
     * Mostrar el formulario de configuración.
     */
    public function index()
    {
        $config = Configuracion::first(); // Solo hay un registro
        $taxProfile = app(TaxProfileService::class)->current();
        return view('configuracion.index', compact('config', 'taxProfile'));
    }

    public function appearance()
    {
        $config = Configuracion::first();
        return view('configuracion.appearance', compact('config'));
    }

    /**
     * Guardar los cambios en la configuración.
     */
    public function update(Request $request)
    {
        $rules = [
            'nombre_empresa' => 'required|string|max:100',
            'ruc'            => 'required|string|max:20',
            'moneda'         => 'required|string|max:10',
            'direccion'      => 'nullable|string',
            'telefono'       => 'nullable|string|max:20',
            'correo'         => 'nullable|email|max:100',
            'lema'           => 'nullable|string|max:120',
            'logo'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ];
        $request->validate($rules);

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
        // El IGV se administra exclusivamente desde el perfil tributario activo.
        $config->direccion      = $request->direccion;
        $config->telefono       = $request->telefono;
        $config->correo         = $request->correo;
        $config->lema           = $request->lema;
        $config->save();

        return redirect()->back()->with('success', 'Configuración general actualizada correctamente.');
    }

    public function updateAppearance(Request $request)
    {
        $colorKeys = ['accent', 'header_from', 'header_to', 'sidebar_from', 'sidebar_to', 'footer_from', 'footer_to', 'table_from', 'table_to', 'modal_from', 'modal_to'];
        $rules = ['light_theme' => 'required|array'];
        foreach ($colorKeys as $colorKey) {
            $rules["light_theme.$colorKey"] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
        }
        $request->validate($rules);

        $config = Configuracion::first();
        $theme = array_merge(Configuracion::lightThemeDefaults(), $request->input('light_theme', []));
        foreach ($colorKeys as $color) {
            $theme[$color] = strtolower($theme[$color]);
        }
        foreach (['header_gradient', 'sidebar_gradient', 'footer_gradient', 'table_gradient', 'modal_gradient'] as $gradient) {
            $theme[$gradient] = $request->boolean("light_theme.$gradient");
        }
        $theme['enabled'] = $request->boolean('light_theme.enabled');
        $config->light_theme = $theme;

        $config->save();

        return redirect()->back()->with('success', 'Apariencia actualizada correctamente.');
    }
}
