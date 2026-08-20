<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'cajas';

    protected $fillable = [
        'usuario_id',
        'abierta_por',
        'abierta_en',
        'monto_inicial',
        'cierre_solicitado_en',
        'monto_declarado',
        'cerrada_en',
        'cerrada_por',
        'aprobada_por',
        'ingresos_efectivo',
        'egresos_efectivo',
        'monto_esperado',
        'monto_contado',
        'diferencia',
        'metodos_esperados',
        'metodos_declarados',
        'metodos_diferencias',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'abierta_en' => 'datetime',
        'cerrada_en' => 'datetime',
        'cierre_solicitado_en' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'monto_declarado' => 'decimal:2',
        'ingresos_efectivo' => 'decimal:2',
        'egresos_efectivo' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'monto_contado' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'metodos_esperados' => 'array',
        'metodos_declarados' => 'array',
        'metodos_diferencias' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'caja_id');
    }

    public function operaciones()
    {
        return $this->hasMany(CajaOperacion::class);
    }

    public function calcularEfectivo(): array
    {
        $base = $this->movimientos()
            ->where('estado', 'pagado')
            ->whereRaw('LOWER(metodo_pago) = ?', ['efectivo']);

        $ingresos = (clone $base)->where('tipo', 'ingreso')->sum('monto');
        $egresos = (clone $base)->where('tipo', 'egreso')->sum('monto');
        $refuerzos = $this->operaciones()->where('tipo', 'refuerzo')->sum('monto');
        $retiros = $this->operaciones()->where('tipo', 'retiro')->sum('monto');

        return [
            'ingresos' => round((float) $ingresos, 2),
            'egresos' => round((float) $egresos, 2),
            'refuerzos' => round((float) $refuerzos, 2),
            'retiros' => round((float) $retiros, 2),
            'esperado' => round((float) $this->monto_inicial + $ingresos + $refuerzos - $egresos - $retiros, 2),
        ];
    }

    public static function mediosConciliables(): array
    {
        return [
            'efectivo' => 'Efectivo', 'yape' => 'Yape', 'plin' => 'Plin',
            'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'otro' => 'Otro',
        ];
    }

    public static function normalizarMetodo(?string $metodo): string
    {
        $metodo = mb_strtolower(trim((string) $metodo));

        return match ($metodo) {
            'efectivo', 'cash' => 'efectivo',
            'yape' => 'yape',
            'plin' => 'plin',
            'tarjeta', 'tarjeta de credito', 'tarjeta de debito', 'tarjeta de débito' => 'tarjeta',
            'transferencia', 'transf.', 'transfer' => 'transferencia',
            'fiado', 'credito', 'crédito', 'pendiente' => 'pendiente',
            default => 'otro',
        };
    }

    public function calcularConciliacion(): array
    {
        $resultado = array_fill_keys(
            array_keys(static::mediosConciliables()),
            ['ingresos' => 0.0, 'egresos' => 0.0, 'esperado' => 0.0]
        );

        $this->movimientos()->where('estado', 'pagado')->get(['tipo', 'monto', 'metodo_pago'])
            ->each(function (Movimiento $movimiento) use (&$resultado) {
                $medio = static::normalizarMetodo($movimiento->metodo_pago);
                if ($medio === 'pendiente') return;
                $tipo = $movimiento->tipo === 'egreso' ? 'egresos' : 'ingresos';
                $resultado[$medio][$tipo] += (float) $movimiento->monto;
            });

        foreach ($resultado as &$valores) {
            $valores['ingresos'] = round($valores['ingresos'], 2);
            $valores['egresos'] = round($valores['egresos'], 2);
            $valores['esperado'] = round($valores['ingresos'] - $valores['egresos'], 2);
        }
        unset($valores);
        $resultado['efectivo'] = $this->calcularEfectivo();

        return $resultado;
    }
}
