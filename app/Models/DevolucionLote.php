<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevolucionLote extends Model
{
    protected $table = 'devolucion_lotes';

    protected $fillable = ['devolucion_detalle_id', 'lote_id', 'cantidad', 'precio_lote'];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_lote' => 'decimal:6',
    ];

    public function detalle() { return $this->belongsTo(DevolucionDetalle::class, 'devolucion_detalle_id'); }
    public function lote() { return $this->belongsTo(Lote::class); }
}
