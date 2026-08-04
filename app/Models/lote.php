<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\DetalleLoteVenta;
use App\Models\LoteMovimiento;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'numero_lote',
        'codigo_comprobante',
        'tipo_comprobante',
        'condicion_pago',
        'metodo_pago',
        'fecha_vencimiento_pago',
        'observaciones_compra',
        'fecha_ingreso',
        'fecha_vencimiento',
        'stock_inicial',
        'stock_actual',
        'precio_compra',
        'precio_unidad',
        'precio_paquete',
        'precio_caja',
        'activo',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_vencimiento_pago' => 'date',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

public function proveedor()
{
    return $this->belongsTo(Proveedor::class);
}

public function ventas()
{
    return $this->hasMany(DetalleLoteVenta::class);
}
public function movimientos()
    {
        return $this->hasMany(LoteMovimiento::class, 'lote_id')
                    ->orderBy('creado_en', 'desc');
    }

    public function pagosCompra()
    {
        return $this->hasMany(CompraPago::class, 'lote_id');
    }

    public function compraMovimiento()
    {
        return $this->hasOne(Movimiento::class, 'referencia_id')
            ->where('referencia_tipo', 'lote')
            ->where('subtipo', 'compra_mercaderia');
    }

}
