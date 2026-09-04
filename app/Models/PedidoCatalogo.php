<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoCatalogo extends Model
{
    protected $table = 'pedidos_catalogo';

    protected $fillable = [
        'codigo', 'cliente_nombre', 'cliente_telefono', 'tipo_entrega',
        'direccion', 'items', 'total', 'estado', 'venta_id',
        'enviado_en', 'atendido_en',
    ];

    protected $casts = [
        'items' => 'array',
        'total' => 'decimal:2',
        'enviado_en' => 'datetime',
        'atendido_en' => 'datetime',
    ];
}
