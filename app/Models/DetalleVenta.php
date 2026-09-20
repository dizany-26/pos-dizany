<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DetalleLoteVenta;


class DetalleVenta extends Model
{
    protected $table = 'detalle_ventas';
    public $timestamps = false;

    protected $fillable = [
        'venta_id',
        'producto_id',
        'presentacion',        // ✅ CORRECTO
        'cantidad',
        'unidades_afectadas',  // ✅ CORRECTO
        'precio_presentacion', // ✅ CORRECTO
        'precio_original_presentacion',
        'descuento_tipo',
        'descuento_valor',
        'descuento_monto',
        'descuento_motivo',
        'precio_unitario',
        'subtotal',
        'ganancia',
        'activo'
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function lotes()
{
    return $this->hasMany(DetalleLoteVenta::class);
}

}
