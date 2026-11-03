<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConciliacionMensual extends Model
{
    protected $table = 'conciliaciones_mensuales';

    protected $fillable = [
        'periodo',
        'usuario_id',
        'esperado',
        'declarado',
        'total_esperado',
        'total_declarado',
        'diferencia',
        'observaciones',
    ];

    protected $casts = [
        'periodo' => 'date',
        'esperado' => 'array',
        'declarado' => 'array',
        'total_esperado' => 'decimal:2',
        'total_declarado' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
