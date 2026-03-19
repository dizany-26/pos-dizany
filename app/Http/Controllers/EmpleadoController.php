<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Venta;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EmpleadoController extends Controller
{
    public function index()
    {
        $ultimasVentas = Venta::with('cliente')
            ->where('usuario_id', Auth::id())
            ->whereDate('fecha', Carbon::today())
            ->orderBy('fecha', 'desc')
            ->take(10)
            ->get();

        return view('empleado.dashboard', compact('ultimasVentas'));
    }

    public function dashboard()
    {
        return $this->index();
    }
}
