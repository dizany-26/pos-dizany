<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PDF;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        [$desde, $hasta] = $this->fechas($request);
        $filtros = $this->filtros($request, $desde, $hasta);

        return view('reportes.index', array_merge($this->construirReporte($filtros), [
            'filtros' => $filtros,
            'usuarios' => $filtros['es_admin']
                ? User::with('rol')->orderBy('nombre')->get()
                : collect(),
            'metodos' => Caja::mediosConciliables(),
        ]));
    }

    public function resumen(Request $request)
    {
        [$desde, $hasta] = $this->fechas($request);
        return response()->json($this->construirReporte($this->filtros($request, $desde, $hasta)));
    }

    public function ganancias(Request $request)
    {
        return $this->index($request);
    }

    public function exportar(Request $request, string $formato)
    {
        abort_unless(in_array($formato, ['csv', 'pdf'], true), 404);
        [$desde, $hasta] = $this->fechas($request);
        $filtros = $this->filtros($request, $desde, $hasta);
        $reporte = $this->construirReporte($filtros);
        $nombre = 'reporte-dizany-'.$desde->format('Ymd').'-'.$hasta->format('Ymd');

        if ($formato === 'pdf') {
            return PDF::loadView('reportes.pdf', compact('reporte', 'filtros'))
                ->setPaper('a4', 'landscape')->download($nombre.'.pdf');
        }

        return response()->streamDownload(function () use ($reporte, $filtros) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['REPORTE GENERAL DIZANY'], ';');
            fputcsv($out, ['Desde', $filtros['desde'], 'Hasta', $filtros['hasta']], ';');
            fputcsv($out, [], ';');
            fputcsv($out, ['RESUMEN FINANCIERO'], ';');
            $kpisExportables = $filtros['es_admin']
                ? $reporte['kpis']
                : collect($reporte['kpis'])->only(['ventasTotal', 'ticketPromedio', 'operaciones'])->all();
            foreach ($kpisExportables as $clave => $valor) {
                fputcsv($out, [str_replace('_', ' ', ucfirst($clave)), number_format((float) $valor, 2, '.', '')], ';');
            }
            fputcsv($out, [], ';');
            fputcsv($out, ['VENTAS'], ';');
            fputcsv($out, ['Fecha', 'Comprobante', 'Cliente', 'Responsable', 'Método', 'Estado', 'Total'], ';');
            foreach ($reporte['ventasDetalle'] as $venta) {
                fputcsv($out, [$venta->fecha, $venta->comprobante, $venta->cliente, $venta->responsable, $venta->metodo_pago, $venta->estado, $venta->total], ';');
            }
            if ($filtros['es_admin']) {
                fputcsv($out, [], ';');
                fputcsv($out, ['PRODUCTOS'], ';');
                fputcsv($out, ['Producto', 'Categoría', 'Unidades', 'Ventas', 'Utilidad'], ';');
                foreach ($reporte['productosVendidos'] as $item) {
                    fputcsv($out, [$item->producto, $item->categoria, $item->unidades, $item->ventas, $item->utilidad], ';');
                }
            }
            fclose($out);
        }, $nombre.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function fechas(Request $request): array
    {
        try {
            $desde = Carbon::createFromFormat('Y-m-d', (string) $request->input('desde', now()->startOfMonth()->format('Y-m-d')))->startOfDay();
            $hasta = Carbon::createFromFormat('Y-m-d', (string) $request->input('hasta', now()->format('Y-m-d')))->endOfDay();
        } catch (\Throwable) {
            $desde = now()->startOfMonth();
            $hasta = now()->endOfDay();
        }
        if ($desde->gt($hasta)) [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        return [$desde, $hasta];
    }

    private function filtros(Request $request, Carbon $desde, Carbon $hasta): array
    {
        $usuario = $request->user();
        $esAdmin = (bool) $usuario?->esAdmin();
        $metodo = strtolower(trim((string) $request->input('metodo', '')));
        $estado = strtolower(trim((string) $request->input('estado', '')));
        return [
            'desde' => $desde->format('Y-m-d'), 'hasta' => $hasta->format('Y-m-d'),
            'desde_obj' => $desde, 'hasta_obj' => $hasta,
            'usuario_id' => $esAdmin
                ? ($request->filled('usuario_id') ? (int) $request->input('usuario_id') : null)
                : (int) $usuario->getAuthIdentifier(),
            'metodo' => array_key_exists($metodo, Caja::mediosConciliables()) ? $metodo : '',
            'estado' => in_array($estado, ['pagado', 'pendiente', 'anulado'], true) ? $estado : '',
            'es_admin' => $esAdmin,
        ];
    }

    private function aplicarVentaFiltros(Builder $query, array $f): Builder
    {
        return $query->whereBetween('v.fecha', [$f['desde_obj'], $f['hasta_obj']])
            ->when($f['usuario_id'], fn ($q) => $q->where('v.usuario_id', $f['usuario_id']))
            ->when($f['metodo'], fn ($q) => $q->whereRaw('LOWER(v.metodo_pago) = ?', [$f['metodo']]))
            ->when($f['estado'], fn ($q) => $q->whereRaw('LOWER(v.estado) = ?', [$f['estado']]))
            ->when(! $f['estado'], fn ($q) => $q->whereRaw("LOWER(COALESCE(v.estado, '')) <> 'anulado'"));
    }

    private function construirReporte(array $f): array
    {
        $ventasBase = $this->aplicarVentaFiltros(DB::table('ventas as v'), $f);
        $ventasTotal = (float) (clone $ventasBase)->sum('v.total');
        $operaciones = (int) (clone $ventasBase)->count();
        $porCobrar = (float) (clone $ventasBase)->where('v.saldo', '>', 0)->sum('v.saldo');

        $detalleBase = DB::table('detalle_ventas as dv')->join('ventas as v', 'v.id', '=', 'dv.venta_id');
        $this->aplicarVentaFiltros($detalleBase, $f);
        $utilidadBruta = (float) (clone $detalleBase)->sum('dv.ganancia');
        $costoVentas = max(0, $ventasTotal - $utilidadBruta);

        $gastosBase = DB::table('gastos as g')->whereBetween('g.fecha', [$f['desde_obj'], $f['hasta_obj']])
            ->whereRaw("LOWER(COALESCE(g.estado, '')) <> 'anulado'")
            ->when($f['usuario_id'], fn ($q) => $q->where('g.usuario_id', $f['usuario_id']))
            ->when($f['metodo'], fn ($q) => $q->whereRaw('LOWER(g.metodo_pago) = ?', [$f['metodo']]));
        $gastos = (float) (clone $gastosBase)->sum('g.monto');
        $utilidadNeta = $utilidadBruta - $gastos;
        $ticketPromedio = $operaciones ? $ventasTotal / $operaciones : 0;

        $ventasDetalle = (clone $ventasBase)->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->leftJoin('usuarios as u', 'u.id', '=', 'v.usuario_id')
            ->selectRaw("v.id, DATE_FORMAT(v.fecha, '%d/%m/%Y %H:%i') fecha, CONCAT(COALESCE(v.serie, 'S/S'), '-', LPAD(COALESCE(v.correlativo, 0), 6, '0')) comprobante, COALESCE(c.nombre, 'Público general') cliente, COALESCE(u.nombre, '—') responsable, COALESCE(v.metodo_pago, '—') metodo_pago, COALESCE(v.estado, '—') estado, v.total")
            ->orderByDesc('v.fecha')->limit(200)->get();

        $productosVendidos = (clone $detalleBase)->join('productos as p', 'p.id', '=', 'dv.producto_id')
            ->leftJoin('categorias as cat', 'cat.id', '=', 'p.categoria_id')
            ->selectRaw("p.nombre producto, COALESCE(cat.nombre, 'Sin categoría') categoria, SUM(COALESCE(dv.unidades_afectadas, dv.cantidad)) unidades, SUM(dv.subtotal) ventas, SUM(dv.ganancia) utilidad")
            ->groupBy('p.id', 'p.nombre', 'cat.nombre')->orderByDesc('unidades')->limit(100)->get();

        $metodosPago = (clone $ventasBase)->selectRaw("LOWER(COALESCE(v.metodo_pago, 'otro')) metodo, SUM(v.total) total, COUNT(*) operaciones")
            ->groupByRaw("LOWER(COALESCE(v.metodo_pago, 'otro'))")->orderByDesc('total')->get();

        $clientes = (clone $ventasBase)->leftJoin('clientes as c', 'c.id', '=', 'v.cliente_id')
            ->selectRaw("COALESCE(c.nombre, 'Público general') cliente, COUNT(*) compras, SUM(v.total) consumo, MAX(v.fecha) ultima_compra, SUM(COALESCE(v.saldo, 0)) deuda")
            ->groupBy('v.cliente_id', 'c.nombre')->orderByDesc('consumo')->limit(100)->get();

        $gastosDetalle = (clone $gastosBase)->leftJoin('usuarios as u', 'u.id', '=', 'g.usuario_id')
            ->selectRaw("DATE_FORMAT(g.fecha, '%d/%m/%Y') fecha, g.descripcion, COALESCE(g.metodo_pago, '—') metodo, COALESCE(u.nombre, '—') responsable, g.monto")
            ->orderByDesc('g.fecha')->limit(100)->get();

        $inventario = DB::table('productos as p')->leftJoin('categorias as cat', 'cat.id', '=', 'p.categoria_id')
            ->leftJoinSub(DB::table('lotes')->where('activo', 1)->selectRaw('producto_id, SUM(stock_actual) stock, SUM(stock_actual * precio_compra) valorizacion')->groupBy('producto_id'), 'inv', 'inv.producto_id', '=', 'p.id')
            ->where('p.activo', 1)->selectRaw("p.nombre producto, COALESCE(cat.nombre, 'Sin categoría') categoria, COALESCE(inv.stock, 0) stock, COALESCE(p.stock_minimo, 0) minimo, COALESCE(inv.valorizacion, 0) valorizacion")
            ->orderBy('stock')->get();
        $stockBajo = $inventario->filter(fn ($p) => (float) $p->stock <= (float) $p->minimo)->values();

        $vencimientos = DB::table('lotes as l')->join('productos as p', 'p.id', '=', 'l.producto_id')
            ->where('l.activo', 1)->where('l.stock_actual', '>', 0)->whereNotNull('l.fecha_vencimiento')
            ->whereBetween('l.fecha_vencimiento', [now()->startOfDay(), now()->addDays(60)->endOfDay()])
            ->selectRaw("l.numero_lote lote, p.nombre producto, l.stock_actual stock, DATE_FORMAT(l.fecha_vencimiento, '%d/%m/%Y') vencimiento, DATEDIFF(l.fecha_vencimiento, CURDATE()) dias")
            ->orderBy('l.fecha_vencimiento')->get();

        $pagosPorLote = DB::table('compra_pagos')->selectRaw('lote_id, SUM(monto) pagado')->groupBy('lote_id');
        $compras = DB::table('lotes as l')->leftJoin('proveedores as pr', 'pr.id', '=', 'l.proveedor_id')
            ->leftJoinSub($pagosPorLote, 'pago', 'pago.lote_id', '=', 'l.id')
            ->whereBetween('l.fecha_ingreso', [$f['desde'], $f['hasta']])
            ->selectRaw("DATE_FORMAT(l.fecha_ingreso, '%d/%m/%Y') fecha, COALESCE(l.codigo_comprobante, CONCAT('Lote ', l.numero_lote)) comprobante, COALESCE(pr.nombre, 'Sin proveedor') proveedor, l.condicion_pago, SUM(l.stock_inicial * l.precio_compra) total, SUM(CASE WHEN l.condicion_pago = 'contado' THEN l.stock_inicial * l.precio_compra ELSE COALESCE(pago.pagado, 0) END) pagado, COUNT(*) productos")
            ->groupBy('l.fecha_ingreso', 'l.proveedor_id', 'pr.nombre', 'l.codigo_comprobante', 'l.numero_lote', 'l.condicion_pago')
            ->orderByDesc('l.fecha_ingreso')->limit(100)->get();
        $compras->each(function ($compra) {
            $compra->saldo = max(0, round((float) $compra->total - (float) $compra->pagado, 2));
            $compra->estado_pago = $compra->saldo <= 0 ? 'pagado' : ((float) $compra->pagado > 0 ? 'parcial' : 'pendiente');
        });
        $comprasTotal = (float) $compras->sum('total');

        $cajas = DB::table('cajas as ca')->leftJoin('usuarios as u', 'u.id', '=', 'ca.usuario_id')
            ->whereBetween('ca.abierta_en', [$f['desde_obj'], $f['hasta_obj']])
            ->when($f['usuario_id'], fn ($q) => $q->where('ca.usuario_id', $f['usuario_id']))
            ->selectRaw("ca.id, COALESCE(u.nombre, '—') cajero, DATE_FORMAT(ca.abierta_en, '%d/%m/%Y %H:%i') apertura, DATE_FORMAT(ca.cerrada_en, '%d/%m/%Y %H:%i') cierre, ca.monto_inicial, ca.monto_esperado, ca.monto_contado, ca.diferencia, ca.estado")
            ->orderByDesc('ca.abierta_en')->limit(100)->get();

        return [
            'kpis' => compact('ventasTotal', 'costoVentas', 'utilidadBruta', 'gastos', 'utilidadNeta', 'ticketPromedio', 'porCobrar', 'comprasTotal', 'operaciones'),
            'ventasDetalle' => $ventasDetalle, 'productosVendidos' => $productosVendidos,
            'metodosPago' => $metodosPago, 'clientes' => $clientes, 'gastosDetalle' => $gastosDetalle,
            'inventario' => $inventario, 'stockBajo' => $stockBajo, 'vencimientos' => $vencimientos,
            'compras' => $compras, 'cajas' => $cajas, 'flujo' => $this->flujoDiario($f, $gastosBase),
        ];
    }

    private function flujoDiario(array $f, Builder $gastosBase): Collection
    {
        $ventas = $this->aplicarVentaFiltros(DB::table('ventas as v'), $f)
            ->selectRaw('DATE(v.fecha) dia, SUM(v.total) total')->groupByRaw('DATE(v.fecha)')->pluck('total', 'dia');
        $gastos = (clone $gastosBase)->selectRaw('DATE(g.fecha) dia, SUM(g.monto) total')->groupByRaw('DATE(g.fecha)')->pluck('total', 'dia');
        $dias = collect();
        $cursor = $f['desde_obj']->copy();
        while ($cursor->lte($f['hasta_obj']) && $dias->count() < 62) {
            $key = $cursor->format('Y-m-d');
            $dias->push(['dia' => $cursor->format('d/m'), 'ventas' => (float) ($ventas[$key] ?? 0), 'gastos' => (float) ($gastos[$key] ?? 0)]);
            $cursor->addDay();
        }
        return $dias;
    }
}
