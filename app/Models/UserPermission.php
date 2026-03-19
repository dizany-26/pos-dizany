<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $table = 'usuario_permisos';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'permiso',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
