<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoVenta extends Model
{
    // Nombre real de la tabla
    protected $table = 'pagos_venta';

    // Campos que se pueden insertar
    protected $fillable = [
        'venta_id',
        'usuario_id',
        'monto',
        'metodo_pago',
        'efectivo_recibido',
        'vuelto',
        'fecha_pago',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'efectivo_recibido' => 'decimal:2',
        'vuelto' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    // Laravel NO maneja created_at / updated_at automáticamente aquí
    // porque ya los controlas desde SQL
    public $timestamps = false;

    // ================= RELACIONES =================

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
