<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios'; // Nombre de tu tabla

    protected $primaryKey = 'id';

    public $timestamps = false; // Desactiva si tu tabla no tiene created_at/updated_at

    protected $fillable = [
        'nombre',
        'usuario',
        'email', // ✅ agregado
        'clave',
        'rol_id',
    ];

    protected $hidden = [
        'clave',
    ];

    // Laravel usará 'clave' como campo de contraseña
    public function getAuthPassword()
    {
        return $this->clave;
    }

    // Relación con roles
    public function rol()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(UsuarioPermiso::class, 'usuario_id');
    }

    public function tienePermiso(string $permiso): bool
    {
        return $this->permisos->contains('permiso', $permiso);
    }
  //  public function getAuthIdentifierName()
//{
  //  return 'usuario';
//}
public function getEmailForPasswordReset()
{
    return $this->email;
}

public function sendPasswordResetNotification($token): void
{
    $fallback = rtrim((string) config('password_reset.local_origin', config('app.url')), '/');
    $requestOrigin = app()->runningInConsole()
        ? null
        : rtrim(request()->getSchemeAndHttpHost(), '/');
    $allowedOrigins = config('password_reset.allowed_origins', []);

    $isLocalhost = $requestOrigin === null
        || in_array(parse_url($requestOrigin, PHP_URL_HOST), ['localhost', '127.0.0.1', '::1'], true);

    $origin = ! $isLocalhost && in_array($requestOrigin, $allowedOrigins, true)
        ? $requestOrigin
        : $fallback;

    if (! in_array($origin, $allowedOrigins, true)) {
        $origin = rtrim((string) config('app.url'), '/');
    }

    try {
        PasswordResetLinkAudit::create([
            'email' => mb_strtolower(trim((string) $this->getEmailForPasswordReset())),
            'token_hash' => PasswordResetLinkAudit::fingerprint($token),
            'expires_at' => now()->addMinutes(
                (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 30)
            ),
        ]);
    } catch (\Throwable $exception) {
        Log::warning('No se pudo registrar la auditoría del enlace de recuperación.', [
            'exception' => $exception,
        ]);
    }

    $this->notify(new ResetPasswordNotification($token, $origin));
}
public function esAdmin()
{
    return optional($this->rol)->nombre === 'Administrador';
}

public function cajas(): HasMany
{
    return $this->hasMany(Caja::class, 'usuario_id');
}

public function rutaInicio(): string
{
    if ($this->esAdmin()) {
        return 'admin.dashboard';
    }

    $rutasPorPermiso = [
        'dashboard.admin' => 'admin.dashboard',
        'dashboard.empleado' => 'empleado.dashboard',
        'ventas' => 'ventas.index',
        'productos' => 'productos.index',
        'productos.create' => 'productos.create',
        'clientes' => 'clientes.index',
        'proveedores' => 'proveedores.index',
        'inventario.resumen' => 'inventario.resumen',
        'inventario.lote' => 'inventario.lote',
        'movimientos' => 'movimientos.index',
        'gastos' => 'gastos.index',
        'reportes' => 'reportes.index',
        'configuracion' => 'configuracion.index',
        'backups' => 'backups.index',
        'catalogo.ver' => 'catalogo.admin.index',
        'catalogo.config' => 'catalogo.admin.config',
        'usuarios' => 'usuarios.index',
        'parametros.productos' => 'productos.parametros',
    ];

    foreach ($rutasPorPermiso as $permiso => $ruta) {
        if ($this->tienePermiso($permiso)) {
            return $ruta;
        }
    }

    return 'sin-permisos';
}

}
