<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaBorrador extends Model
{
    protected $table = 'ventas_borrador';

    protected $fillable = [
        'usuario_id',
        'uuid',
        'datos',
        'eliminado_en',
    ];

    protected $casts = [
        'datos' => 'array',
        'eliminado_en' => 'datetime',
    ];
}
