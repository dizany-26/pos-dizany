<?php

namespace App\Http\Controllers;

use App\Services\PedidoCatalogoNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class NotificacionController extends Controller
{
    public function abrir(string $id): RedirectResponse
    {
        $notificacion = auth()->user()->notifications()->findOrFail($id);
        $notificacion->markAsRead();

        $url = $this->rutaInterna($notificacion->data['url'] ?? null);

        return redirect()->to($url);
    }

    /**
     * Convierte enlaces absolutos antiguos (localhost, IP o ngrok) en rutas
     * internas. Asi la notificacion siempre abre en el mismo host desde el
     * que el usuario esta utilizando DIZANY y no permite redirecciones externas.
     */
    private function rutaInterna(?string $url): string
    {
        $fallback = route('movimientos.index', [], false);

        if (! is_string($url) || trim($url) === '') {
            return $fallback;
        }

        $partes = parse_url(trim($url));
        if ($partes === false) {
            return $fallback;
        }

        $ruta = $partes['path'] ?? '';
        if ($ruta === '' || ! str_starts_with($ruta, '/')) {
            return $fallback;
        }

        $destino = $ruta;
        if (! empty($partes['query'])) {
            $destino .= '?'.$partes['query'];
        }
        if (! empty($partes['fragment'])) {
            $destino .= '#'.$partes['fragment'];
        }

        return $destino;
    }

    public function caja(PedidoCatalogoNotificationService $catalogNotifications): JsonResponse
    {
        $unread = $catalogNotifications->unreadFor(auth()->user());
        $notificaciones = $unread
            ->take(10)
            ->map(fn ($notificacion) => [
                'id' => $notificacion->id,
                'titulo' => $notificacion->data['titulo'] ?? 'Alerta de caja',
                'mensaje' => $notificacion->data['mensaje'] ?? '',
                'tipo' => $notificacion->data['color'] ?? 'info',
                'url' => route('notificaciones.abrir', $notificacion->id),
            ]);

        return response()->json([
            'total' => $unread->count(),
            'notificaciones' => $notificaciones,
        ]);
    }

    public function inventario(): JsonResponse
    {
        $stockBajo = DB::table('productos')
            ->leftJoin('lotes', function ($join) {
                $join->on('productos.id', '=', 'lotes.producto_id')
                    ->where('lotes.activo', 1);
            })
            ->where('productos.activo', 1)
            ->select('productos.id')
            ->groupBy('productos.id')
            ->havingRaw('COALESCE(SUM(lotes.stock_actual), 0) <= MAX(COALESCE(productos.stock_minimo, 10))')
            ->count();

        $porVencer = DB::table('lotes')
            ->where('activo', 1)
            ->where('stock_actual', '>', 0)
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<=', now()->addDays(30))
            ->count();

        return response()->json([
            'stock_bajo' => $stockBajo,
            'por_vencer' => $porVencer,
        ]);
    }
}
