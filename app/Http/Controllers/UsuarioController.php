<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role; // Asegúrate de que el modelo se llame Role.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\UsuarioPermiso;
use App\Exports\UsuariosExport;
use App\Support\SecurePassword;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    // Muestra la lista de usuarios y los roles para el formulario
    public function index()
    {
        $usuarios = User::with(['rol', 'permisos'])->get();
        $roles = Role::orderByRaw("CASE WHEN nombre = 'Administrador' THEN 0 WHEN nombre = 'Encargado' THEN 1 WHEN nombre = 'Cajero' THEN 2 WHEN nombre = 'Almacén' THEN 3 ELSE 4 END")
            ->orderBy('nombre')
            ->get();

        $plantillasRol = config('user_roles.templates', []);
        $descripcionesRol = config('user_roles.descriptions', []);

        return view('usuarios.index', compact('usuarios', 'roles', 'plantillasRol', 'descripcionesRol'));
    }

    // Guarda un nuevo usuario
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'dni' => 'required|digits:8|unique:usuarios,dni',
            'email' => 'required|email|unique:usuarios,email',
            'password' => ['required', SecurePassword::rule()],
            'rol_id' => 'required|exists:roles,id',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string',
        ], SecurePassword::messages());

        DB::transaction(function () use ($request) {
            $rol = Role::findOrFail($request->rol_id);

            if ($rol->nombre === 'Administrador' && ! Auth::user()->esAdmin()) {
                abort(403, 'Solo un administrador puede crear otra cuenta administradora.');
            }

            $usuario = User::create([
                'nombre' => $request->nombre,
                'dni' => $request->dni,
                'usuario' => $this->crearIdentificadorInterno($request->email),
                'email' => mb_strtolower(trim($request->email)),
                'clave' => Hash::make($request->password),
                'rol_id' => $request->rol_id,
            ]);

            $this->syncPermisos(
                $usuario->id,
                $rol->nombre === 'Administrador'
                    ? config('user_roles.permissions', [])
                    : $request->input('permisos', [])
            );
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required',
            'dni' => 'required|digits:8|unique:usuarios,dni,' . $id,
            'email' => 'required|email|unique:usuarios,email,' . $id,
            'rol_id' => 'required|exists:roles,id',
            'permisos' => 'nullable|array',
            'permisos.*' => 'string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $usuario = User::findOrFail($id);
            $rolNuevo = Role::findOrFail($request->rol_id);

            if (($usuario->esAdmin() || $rolNuevo->nombre === 'Administrador') && ! Auth::user()->esAdmin()) {
                abort(403, 'Solo un administrador puede asignar o modificar cuentas administradoras.');
            }

            if ($usuario->esAdmin() && $rolNuevo->nombre !== 'Administrador' && $this->cantidadAdministradores() <= 1) {
                throw ValidationException::withMessages([
                    'rol_id' => 'No puedes cambiar el rol del último administrador del sistema.',
                ]);
            }

            $usuario->update([
                'nombre' => $request->nombre,
                'dni' => $request->dni,
                'email' => mb_strtolower(trim($request->email)),
                'rol_id' => $request->rol_id,
            ]);

            $this->syncPermisos(
                $usuario->id,
                $rolNuevo->nombre === 'Administrador'
                    ? config('user_roles.permissions', [])
                    : $request->input('permisos', [])
            );
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id)
    {
        $usuario = User::findOrFail($id);

        if ($usuario->esAdmin() && ! Auth::user()->esAdmin()) {
            abort(403, 'Solo un administrador puede eliminar otra cuenta administradora.');
        }

        if ((int) Auth::id() === (int) $usuario->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta mientras tienes la sesión iniciada.');
        }

        if ($usuario->esAdmin() && $this->cantidadAdministradores() <= 1) {
            return back()->with('error', 'No puedes eliminar el último administrador del sistema.');
        }

        DB::transaction(function () use ($usuario) {
            UsuarioPermiso::where('usuario_id', $usuario->id)->delete();
            $usuario->delete();
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function cambiarClave(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
            'nueva_clave' => ['required', SecurePassword::rule()],
        ], SecurePassword::messages('nueva_clave'));

        $usuario = User::findOrFail($request->usuario_id);
        $usuario->clave = Hash::make($request->nueva_clave);
        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Contraseña actualizada correctamente.');
    }
    public function cambiarMiClave(Request $request)
    {
        $request->validate([
            'nueva_clave' => ['required', SecurePassword::rule()],
        ], SecurePassword::messages('nueva_clave'));

        $usuario = Auth::user();
        $usuario->clave = Hash::make($request->nueva_clave);
        $usuario->save();

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    public function exportarExcel()
    {
        return Excel::download(new UsuariosExport, 'usuarios.xlsx');
    }

    private function syncPermisos(int $usuarioId, array $permisos): void
    {
        $permitidos = config('user_roles.permissions', []);
        $permisosNormalizados = collect($permisos)
            ->filter()
            ->intersect($permitidos)
            ->unique()
            ->values();

        UsuarioPermiso::where('usuario_id', $usuarioId)->delete();

        if ($permisosNormalizados->isEmpty()) {
            return;
        }

        UsuarioPermiso::insert(
            $permisosNormalizados->map(fn ($permiso) => [
                'usuario_id' => $usuarioId,
                'permiso' => $permiso,
            ])->all()
        );
    }

    private function cantidadAdministradores(): int
    {
        return User::whereHas('rol', fn ($query) => $query->where('nombre', 'Administrador'))->count();
    }

    private function crearIdentificadorInterno(string $email): string
    {
        $base = Str::limit(Str::slug(Str::before($email, '@'), '_'), 38, '');
        $base = $base !== '' ? $base : 'usuario';
        $candidato = $base;
        $secuencia = 1;

        while (User::where('usuario', $candidato)->exists()) {
            $candidato = Str::limit($base, 44, '') . '_' . $secuencia++;
        }

        return $candidato;
    }
}
