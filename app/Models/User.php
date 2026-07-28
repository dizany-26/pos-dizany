<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
public function esAdmin()
{
    return $this->rol_id == 1;
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
