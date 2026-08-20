<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Caja;
use App\Models\Venta;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EmpleadoController extends Controller
{
    public function index()
    {
        return view('empleado.dashboard');
    }
    public function dashboard()
    {
        $ventasHoyQuery = Venta::query()
            ->where('usuario_id', Auth::id())
            ->whereDate('fecha', Carbon::today());

        $ultimasVentas = (clone $ventasHoyQuery)->with('cliente')
            ->orderBy('fecha', 'desc')
            ->take(10)
            ->get();

        $resumen = [
            'cantidad' => (clone $ventasHoyQuery)->count(),
            'total' => (float) (clone $ventasHoyQuery)->sum('total'),
            'pendientes' => (clone $ventasHoyQuery)->where('estado', 'pendiente')->count(),
        ];

        $caja = Caja::where('usuario_id', Auth::id())
            ->whereIn('estado', ['abierta', 'pendiente_cierre'])
            ->latest('abierta_en')
            ->first();

        return view('empleado.dashboard', compact('ultimasVentas', 'resumen', 'caja'));
    }

}
