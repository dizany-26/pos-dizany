<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuracion';
    public $timestamps = false;

    protected $fillable = [
        'nombre_empresa',
        'ruc',
        'logo',
        'moneda',
        'igv',
        'direccion',
        'telefono',
        'correo',
    ];
}
