<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraPago extends Model
{
    protected $table = 'compra_pagos';

    protected $fillable = [
        'movimiento_id', 'lote_id', 'usuario_id', 'monto', 'fecha',
        'metodo_pago', 'numero_operacion', 'observacion',
    ];

    protected $casts = ['monto' => 'decimal:2', 'fecha' => 'date'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
