<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevolucionDetalle extends Model
{
    protected $table = 'devolucion_detalles';

    protected $fillable = [
        'devolucion_id', 'detalle_venta_id', 'cantidad', 'unidades_afectadas',
        'importe', 'costo', 'ganancia_revertida', 'reintegra_stock',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'unidades_afectadas' => 'integer',
        'importe' => 'decimal:2',
        'costo' => 'decimal:4',
        'ganancia_revertida' => 'decimal:4',
        'reintegra_stock' => 'boolean',
    ];

    public function devolucion() { return $this->belongsTo(Devolucion::class); }
    public function detalleVenta() { return $this->belongsTo(DetalleVenta::class); }
    public function lotes() { return $this->hasMany(DevolucionLote::class); }
}
