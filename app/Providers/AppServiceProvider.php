<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

use App\Models\Producto;
use App\Models\Lote;

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
            $alertaStockBajo = Producto::withSum('lotes as stock_total', 'stock_actual')
                ->having('stock_total', '<=', 10)
                ->count();

            $alertaPorVencer = Lote::whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '<=', now()->addDays(30))
                ->where('stock_actual', '>', 0)
                ->count();

            $notificacionesCaja = auth()->check()
                ? auth()->user()->unreadNotifications()->latest()->limit(5)->get()
                : collect();
            $notificacionesCajaTotal = auth()->check()
                ? auth()->user()->unreadNotifications()->count()
                : 0;
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
