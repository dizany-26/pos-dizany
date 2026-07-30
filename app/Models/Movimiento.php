<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $table = 'movimientos';

    protected $fillable = [
        'caja_id',
        'usuario_id',
        'fecha',
        'hora',
        'tipo',
        'subtipo',
        'concepto',
        'monto',
        'metodo_pago',
        'estado',
        'referencia_id',
        'referencia_tipo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora'  => 'datetime:H:i:s',
        'monto' => 'decimal:2',
    ];

    /* =========================
       SCOPES
    ========================= */

    public function scopeIngresos($query)
    {
        return $query->where('tipo', 'ingreso');
    }

    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'egreso');
    }

    public function scopePagados($query)
    {
        return $query->where('estado', 'pagado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    // ✅ ESTE ES EL NUEVO (BIEN PUESTO)
    public function scopeActivos($query)
    {
        return $query->where('estado', '!=', 'anulado');
    }

    /* =========================
       RELACIONES
    ========================= */

    public function venta()
    {
        return $this->belongsTo(\App\Models\Venta::class, 'referencia_id');
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'caja_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    protected static function booted()
    {
        static::creating(function (Movimiento $movimiento) {
            if (! auth()->check()) {
                return;
            }

            $movimiento->usuario_id ??= auth()->id();
            $movimiento->caja_id ??= Caja::where('usuario_id', auth()->id())
                ->where('estado', 'abierta')
                ->value('id');
        });
    }
}
