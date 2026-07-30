<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CajaOperacion extends Model
{
    protected $table = 'caja_operaciones';

    protected $fillable = [
        'caja_id',
        'usuario_id',
        'tipo',
        'monto',
        'origen_destino',
        'motivo',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
