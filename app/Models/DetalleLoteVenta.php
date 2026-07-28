<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DetalleVenta;
use App\Models\Lote;

class DetalleLoteVenta extends Model
{
    protected $table = 'detalle_lote_ventas';

    protected $fillable = [
        'detalle_venta_id',
        'lote_id',
        'cantidad',
        'fecha_vencimiento',
        'precio_lote',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'fecha_vencimiento' => 'date',
        'precio_lote' => 'decimal:2',
    ];

    /* =========================
       RELACIONES
    ========================= */

    // 🔗 Pertenece a un detalle de venta
    public function detalleVenta()
    {
        return $this->belongsTo(DetalleVenta::class);
    }

    // 🔗 Pertenece a un lote
    public function lote()
    {
        return $this->belongsTo(Lote::class);
    }
}
