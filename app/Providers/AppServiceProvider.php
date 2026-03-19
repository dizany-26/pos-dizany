<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
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

    View::composer('*', function ($view) {

        // =========================
        // ALERTAS INVENTARIO
        // =========================

        // Productos con stock bajo (<=10)
        $alertaStockBajo = Producto::withSum('lotes as stock_total', 'stock_actual')
            ->having('stock_total', '<=', 10)
            ->count();

        // Lotes próximos a vencer (30 días)
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
