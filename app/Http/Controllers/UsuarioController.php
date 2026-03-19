<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role; // Asegúrate de que el modelo se llame Role.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Exports\UsuariosExport;
use Maatwebsite\Excel\Facades\Excel;

class UsuarioController extends Controller
{
    // Muestra la lista de usuarios y los roles para el formulario
    public function index()
    {
        $usuarios = User::with(['rol', 'permisos'])->get();
        $roles = Role::all();
        $permissionGroups = User::availablePermissions();

        return view('usuarios.index', compact('usuarios', 'roles', 'permissionGroups'));
    }

    // Guarda un nuevo usuario
    public function store(Request $request)
{
    $request->validate([
        'nombre' => 'required',
        'usuario' => 'required|unique:usuarios,usuario',
        'email' => 'required|email|unique:usuarios,email', // ✅ validación
        'password' => 'required',
        'rol_id' => 'required|exists:roles,id',
        'permissions' => 'nullable|array',
        'permissions.*' => 'in:' . implode(',', User::flattenedPermissions()),
    ]);

    $usuario = User::create([
        'nombre' => $request->nombre,
        'usuario' => $request->usuario,
        'email' => $request->email, // ✅ nuevo campo
        'clave' => Hash::make($request->password),
        'rol_id' => $request->rol_id,
    ]);

    $usuario->permisos()->delete();
    foreach ($request->input('permissions', []) as $permiso) {
        $usuario->permisos()->create(['permiso' => $permiso]);
    }

    return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
}


    public function update(Request $request, $id)
{
    $request->validate([
        'nombre' => 'required',
        'usuario' => 'required|unique:usuarios,usuario,' . $id,
        'email' => 'required|email|unique:usuarios,email,' . $id, // ✅ validación
        'rol_id' => 'required|exists:roles,id',
        'permissions' => 'nullable|array',
        'permissions.*' => 'in:' . implode(',', User::flattenedPermissions()),
    ]);

    $usuario = User::findOrFail($id);
    $usuario->update([
        'nombre' => $request->nombre,
        'usuario' => $request->usuario,
        'email' => $request->email, // ✅ nuevo campo
        'rol_id' => $request->rol_id,
    ]);

    $usuario->permisos()->delete();
    foreach ($request->input('permissions', []) as $permiso) {
        $usuario->permisos()->create(['permiso' => $permiso]);
    }

    return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
}

    public function destroy($id)
    {
        User::destroy($id);
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function cambiarClave(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
            'nueva_clave' => 'required|string|min:4'
        ]);

        $usuario = User::findOrFail($request->usuario_id);
        $usuario->clave = Hash::make($request->nueva_clave);
        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Contraseña actualizada correctamente.');
    }
    public function cambiarMiClave(Request $request)
    {
        $request->validate([
            'nueva_clave' => 'required|string|min:4'
        ]);

        $usuario = Auth::user();
        $usuario->clave = Hash::make($request->nueva_clave);
        $usuario->save();

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    public function exportarExcel()
    {
        return Excel::download(new UsuariosExport, 'usuarios.xlsx');
    }

}
