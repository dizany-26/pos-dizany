<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use Carbon\Carbon;

class DashboardAdminController extends Controller
{
    public function index()
    {
        $hoy = Carbon::today();

        // ================= KPIs =================
        $ingresosHoy = Movimiento::whereDate('fecha', $hoy)
            ->where('tipo', 'ingreso')
            ->where('estado', 'pagado')
            ->activos()
            ->sum('monto');

        $egresosHoy = Movimiento::whereDate('fecha', $hoy)
            ->where('tipo', 'egreso')
            ->where('estado', 'pagado')
            ->where('subtipo', 'gasto')
            ->where('referencia_tipo', 'gasto')
            ->activos()
            ->sum('monto');

        $totalIngresos = Movimiento::where('tipo', 'ingreso')
            ->where('estado', 'pagado')
            ->activos()
            ->sum('monto');

        $totalEgresos = Movimiento::where('tipo', 'egreso')
            ->where('estado', 'pagado')
            ->where('subtipo', 'gasto')
            ->where('referencia_tipo', 'gasto')
            ->activos()
            ->sum('monto');

        $balance = $totalIngresos - $totalEgresos;

        $porCobrar = Movimiento::where('tipo', 'ingreso')
            ->where('estado', 'pendiente')
            ->sum('monto');

        $porPagar = Movimiento::where('tipo', 'egreso')
            ->where('estado', 'pendiente')
            ->sum('monto');

        // ================= ÚLTIMOS MOVIMIENTOS =================
        $ultimosMovimientos = Movimiento::orderByDesc('fecha')
            ->where(function ($query) {
                $query->whereNull('subtipo')
                    ->orWhere('subtipo', '!=', 'compra_mercaderia');
            })
            ->limit(5)
            ->get();
        
            // ================= FLUJO 7 DÍAS =================
        $flujo = Movimiento::selectRaw('DATE(fecha) as dia')
            ->selectRaw("SUM(CASE WHEN tipo='ingreso' AND estado='pagado' THEN monto ELSE 0 END) as ingresos")
            ->selectRaw("SUM(CASE WHEN tipo='egreso' AND subtipo='gasto' AND referencia_tipo='gasto' AND estado='pagado' THEN monto ELSE 0 END) as egresos")
            ->activos()
            ->whereDate('fecha', '>=', now()->subDays(6))
            ->groupBy('dia')
            ->orderBy('dia')
            ->get();

        $labels = $flujo->pluck('dia')->map(fn ($d) =>
            \Carbon\Carbon::parse($d)->format('d/m')
        );

        $ingresosData = $flujo->pluck('ingresos');
        $egresosData  = $flujo->pluck('egresos');


        return view('admin.dashboard', compact(
            'ingresosHoy',
            'egresosHoy',
            'balance',
            'porCobrar',
            'porPagar',
            'ultimosMovimientos',
            'labels',
            'ingresosData',
            'egresosData'
        ));

    }
}
