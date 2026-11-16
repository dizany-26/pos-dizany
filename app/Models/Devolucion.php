<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Devolucion extends Model
{
    protected $table = 'devoluciones';

    protected $fillable = [
        'venta_id', 'usuario_id', 'caja_id', 'fecha', 'importe_total',
        'reduccion_saldo', 'monto_reembolsado', 'metodo_reembolso',
        'estado', 'motivo', 'requiere_nota_credito',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'importe_total' => 'decimal:2',
        'reduccion_saldo' => 'decimal:2',
        'monto_reembolsado' => 'decimal:2',
        'requiere_nota_credito' => 'boolean',
    ];

    public function venta() { return $this->belongsTo(Venta::class); }
    public function usuario() { return $this->belongsTo(User::class, 'usuario_id'); }
    public function caja() { return $this->belongsTo(Caja::class); }
    public function detalles() { return $this->hasMany(DevolucionDetalle::class); }

    public function getCodigoAttribute(): string
    {
        return 'DEV-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
