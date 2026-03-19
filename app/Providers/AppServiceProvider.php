<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

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

    if (! Schema::hasTable('usuario_permisos')) {
        Schema::create('usuario_permisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('permiso', 100);
            $table->unique(['usuario_id', 'permiso']);
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

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
