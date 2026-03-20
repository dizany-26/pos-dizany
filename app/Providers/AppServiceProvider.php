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

        if (request()->isSecure() || str_contains(request()->getHost(), 'ngrok-free.dev')) {
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

            $totalAlertas = $alertaStockBajo + $alertaPorVencer;

            $view->with(compact(
                'alertaStockBajo',
                'alertaPorVencer',
                'totalAlertas'
            ));
        });
    }
}