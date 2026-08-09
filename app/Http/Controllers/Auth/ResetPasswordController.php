<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Configuracion;
use App\Models\PasswordResetLinkAudit;
use App\Support\SecurePassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token)
    {
        $email = mb_strtolower(trim((string) $request->query('email')));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $audit = PasswordResetLinkAudit::where('token_hash', PasswordResetLinkAudit::fingerprint($token))
            ->where('email', $email)
            ->first();

        if ($audit?->used_at) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Este enlace de recuperación ya fue utilizado. Solicita uno nuevo si necesitas cambiar nuevamente tu contraseña.',
            ]);
        }

        if ($audit && $audit->expires_at->isPast()) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Este enlace de recuperación expiró. Solicita uno nuevo.',
            ]);
        }

        if (! $user || ! Password::broker()->tokenExists($user, $token)) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Este enlace de recuperación no es válido o fue reemplazado por uno más reciente. Solicita uno nuevo.',
            ]);
        }

        $config = Configuracion::first();

        return view('auth.passwords.reset', compact('token', 'email', 'config'));
    }

    public function reset(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'confirmed', SecurePassword::rule()],
        ], SecurePassword::messages());

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request): void {
                $user->forceFill([
                    'clave' => Hash::make($password),
                ])->save();

                PasswordResetLinkAudit::where(
                    'token_hash',
                    PasswordResetLinkAudit::fingerprint((string) $request->input('token'))
                )->update(['used_at' => now()]);

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'El enlace de recuperación no es válido o ya expiró. Solicita uno nuevo.',
            ]);
        }

        return redirect()->route('login')->with(
            'success',
            'Tu contraseña fue actualizada. Ya puedes iniciar sesión con la nueva contraseña.'
        );
    }

}
