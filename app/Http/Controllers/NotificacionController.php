<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotificacionController extends Controller
{
    public function abrir(string $id): RedirectResponse
    {
        $notificacion = auth()->user()->notifications()->findOrFail($id);
        $notificacion->markAsRead();

        $url = $notificacion->data['url'] ?? route('movimientos.index');

        return redirect()->to($url);
    }

    public function caja(): JsonResponse
    {
        $notificaciones = auth()->user()
            ->unreadNotifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($notificacion) => [
                'id' => $notificacion->id,
                'titulo' => $notificacion->data['titulo'] ?? 'Alerta de caja',
                'mensaje' => $notificacion->data['mensaje'] ?? '',
                'tipo' => $notificacion->data['color'] ?? 'info',
                'url' => route('notificaciones.abrir', $notificacion->id),
            ]);

        return response()->json([
            'total' => auth()->user()->unreadNotifications()->count(),
            'notificaciones' => $notificaciones,
        ]);
    }

    public function inventario()
{
    $productos_bajos = DB::table('productos')
        ->join('lotes', 'productos.id', '=', 'lotes.producto_id')
        ->where('productos.activo', 1)
        ->where('lotes.activo', 1)
        ->groupBy('productos.id')
        ->havingRaw('SUM(lotes.stock_actual) <= MAX(COALESCE(productos.stock_minimo, 10))')
        ->pluck('productos.id'); // 👈 SOLO trae los IDs

    $stock_bajo = $productos_bajos->count();

    $por_vencer = DB::table('lotes')
        ->where('activo', 1)
        ->where('stock_actual', '>', 0)
        ->whereNotNull('fecha_vencimiento')
        ->whereDate('fecha_vencimiento', '<=', now()->addDays(7))
        ->count();

    return response()->json([
        'stock_bajo' => $stock_bajo,
        'por_vencer' => $por_vencer
    ]);
}


}
