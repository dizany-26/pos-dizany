<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        $config = Configuracion::first();

        return view('auth.passwords.email', compact('config'));
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ]);

        try {
            Password::broker()->sendResetLink($request->only('email'));
        } catch (Throwable $exception) {
            Log::error('No se pudo enviar un correo de recuperación de contraseña.', [
                'exception' => $exception,
            ]);
        }

        return back()->with(
            'success',
            'Si el correo pertenece a una cuenta registrada, recibirás un enlace de recuperación en unos minutos.'
        );
    }
}
