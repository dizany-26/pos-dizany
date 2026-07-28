<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Permite acceso total al Administrador y exige al Empleado al menos
     * uno de los permisos indicados en la ruta.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Debes iniciar sesión.');
        }

        if ($user->esAdmin() || optional($user->rol)->nombre === 'Administrador') {
            return $next($request);
        }

        if (collect($permissions)->contains(
            fn (string $permission) => $user->tienePermiso($permission)
        )) {
            return $next($request);
        }

        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}
