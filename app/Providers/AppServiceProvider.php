<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

use App\Models\Lote;
use App\Services\PedidoCatalogoNotificationService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Paginator::useBootstrapFive();

        if (! $this->app->runningInConsole() && $this->shouldForceHttps()) {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {
            $puedeVerInventario = auth()->check()
                && (auth()->user()->esAdmin() || auth()->user()->tienePermiso('inventario.resumen'));

            $alertaStockBajo = $puedeVerInventario
                ? DB::table('productos')
                    ->leftJoin('lotes', function ($join) {
                        $join->on('productos.id', '=', 'lotes.producto_id')
                            ->where('lotes.activo', 1);
                    })
                    ->where('productos.activo', 1)
                    ->select('productos.id')
                    ->groupBy('productos.id')
                    ->havingRaw('COALESCE(SUM(lotes.stock_actual), 0) <= MAX(COALESCE(productos.stock_minimo, 10))')
                    ->count()
                : 0;

            $alertaPorVencer = $puedeVerInventario
                ? Lote::whereNotNull('fecha_vencimiento')
                    ->whereDate('fecha_vencimiento', '<=', now()->addDays(30))
                    ->where('stock_actual', '>', 0)
                    ->where('activo', 1)
                    ->count()
                : 0;

            $notificacionesNoLeidas = auth()->check()
                ? app(PedidoCatalogoNotificationService::class)->unreadFor(auth()->user())
                : collect();
            $notificacionesCaja = $notificacionesNoLeidas->take(5);
            $notificacionesCajaTotal = $notificacionesNoLeidas->count();
            $totalAlertas = $alertaStockBajo + $alertaPorVencer + $notificacionesCajaTotal;

            $view->with(compact(
                'alertaStockBajo',
                'alertaPorVencer',
                'notificacionesCaja',
                'notificacionesCajaTotal',
                'totalAlertas'
            ));
        });
    }

    private function shouldForceHttps(): bool
    {
        $host = request()->getHost();

        return request()->isSecure()
            || str_contains($host, 'ngrok-free.dev')
            || str_contains($host, 'ngrok.io');
    }
}
