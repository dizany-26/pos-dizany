<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\UserPermission;

class User extends Authenticatable
{
    use Notifiable;

    protected ?array $resolvedPermissions = null;

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

    public function permisos()
    {
        return $this->hasMany(UserPermission::class, 'usuario_id');
    }

    public static function availablePermissions(): array
    {
        return config('user_permissions.groups', []);
    }

    public static function flattenedPermissions(): array
    {
        return collect(static::availablePermissions())
            ->flatMap(fn ($group) => array_keys($group))
            ->values()
            ->all();
    }

    public function explicitPermissionKeys(): array
    {
        if (! $this->relationLoaded('permisos')) {
            $this->load('permisos');
        }

        return $this->permisos->pluck('permiso')->values()->all();
    }

    public function permissionKeys(): array
    {
        if ($this->resolvedPermissions !== null) {
            return $this->resolvedPermissions;
        }

        if (! $this->relationLoaded('rol')) {
            $this->load('rol');
        }

        $defaults = config('user_permissions.role_defaults.' . ($this->rol->nombre ?? ''), []);

        if (in_array('*', $defaults, true)) {
            return $this->resolvedPermissions = static::flattenedPermissions();
        }

        return $this->resolvedPermissions = collect($defaults)
            ->merge($this->explicitPermissionKeys())
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissionKeys(), true);
    }

  //  public function getAuthIdentifierName()
//{
  //  return 'usuario';
//}
public function getEmailForPasswordReset()
{
    return $this->email;
}
public function esAdmin()
{
    return $this->rol_id == 1;
}

}
