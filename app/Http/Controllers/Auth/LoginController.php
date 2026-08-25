<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Configuracion;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (auth()->check()) {
            $rol = auth()->user()->rol->nombre;

            if ($rol === 'Administrador') {
                return redirect()->route('admin.dashboard');
            } elseif ($rol === 'Empleado') {
                return redirect()->route('empleado.dashboard');
            }

            return redirect('/'); // Por si no tiene rol definido
        }

        // Obtener configuración y pasarla a la vista
        $config = Configuracion::first();
        return view('auth.login', compact('config'));
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt(['email' => mb_strtolower(trim($credentials['email'])), 'password' => $credentials['password']])) {
            $request->session()->regenerate();

            return redirect()->intended(route($request->user()->rutaInicio()));
        }

        return back()->withErrors([
            'email' => 'El correo o la contraseña no coinciden.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }

public function loginAjax(Request $request)
{
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (Auth::attempt(['email' => mb_strtolower(trim($credentials['email'])), 'password' => $credentials['password']])) {
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'redirect_to' => route($request->user()->rutaInicio()),
        ]);
    }

    return response()->json(['success' => false, 'message' => 'Correo o contraseña incorrectos.']);
}

}
