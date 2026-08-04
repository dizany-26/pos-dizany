<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Caja;
use App\Models\Gasto;
use App\Models\User;
use App\Models\Lote;
use App\Models\CompraPago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PDF;

class MovimientoController extends Controller
{
    public function index(Request $request)
    {
        /* ==========================
        PARÁMETROS
        ========================== */
        $tab   = $request->get('tab', 'ingresos');
        if (! in_array($tab, ['ingresos', 'egresos', 'por_cobrar'], true)) {
            $tab = 'ingresos';
        }
        $tipo  = $request->get('tipo', 'transacciones');
        $rango = $request->get('rango', 'diario');
        $fecha = $request->get('fecha', now()->format('Y-m-d'));

        // Normalizar separadores
        $fecha = str_replace([' to ', ' | ', ' → '], ' a ', $fecha);

        /* ==========================
        QUERY BASE
        ========================== */
        $query = Movimiento::query()->with('usuario');

        // ---- FILTRO POR TAB ----
        switch ($tab) {
            case 'ingresos':
                $query->ingresos()->pagados();
                break;

            case 'egresos':
                // Gastos operativos pequeños ya pagados; no incluye mercadería.
                $query->egresos()
                      ->pagados()
                      ->where('subtipo', 'gasto')
                      ->where('referencia_tipo', 'gasto');
                break;

            case 'por_cobrar':
                // Deudas de clientes: únicamente ingresos de ventas pendientes.
                $query->ingresos()
                      ->pendientes()
                      ->where('referencia_tipo', 'venta')
                      ->whereIn('metodo_pago', ['fiado', 'credito']);
                break;

            case 'por_pagar':
                // Deudas de mercadería con proveedores.
                $query->egresos()
                      ->pendientes()
                      ->where('subtipo', 'compra_mercaderia')
                      ->where('referencia_tipo', 'lote');
                break;
        }

        /* ==========================
        RANGO DE FECHAS
        ========================== */
        $inicio = null;
        $fin    = null;

        try {
            if ($rango === 'diario') {
                $inicio = Carbon::parse($fecha)->startOfDay();
                $fin    = Carbon::parse($fecha)->endOfDay();

            } elseif ($rango === 'semanal') {
                [$f1, $f2] = array_pad(explode(' a ', $fecha), 2, $fecha);
                $inicio = Carbon::parse($f1)->startOfDay();
                $fin    = Carbon::parse($f2)->endOfDay();

            } elseif ($rango === 'mensual') {
                $carbon = preg_match('/^\d{4}-\d{2}$/', $fecha)
                    ? Carbon::createFromFormat('Y-m', $fecha)
                    : Carbon::createFromLocaleFormat('M Y', 'es', $fecha);

                $inicio = $carbon->startOfMonth();
                $fin    = $carbon->endOfMonth();

            } elseif ($rango === 'anual') {
                $year   = preg_match('/^\d{4}$/', $fecha) ? $fecha : now()->year;
                $inicio = Carbon::create($year, 1, 1)->startOfDay();
                $fin    = Carbon::create($year, 12, 31)->endOfDay();

            } elseif ($rango === 'personalizado') {
                [$f1, $f2] = array_pad(explode(' a ', $fecha), 2, null);
                if ($f1 && $f2) {
                    $inicio = Carbon::parse($f1)->startOfDay();
                    $fin    = Carbon::parse($f2)->endOfDay();
                }
            }
        } catch (\Exception $e) {
            $inicio = null;
            $fin    = null;
        }

        if ($inicio && $fin) {
            $query->whereBetween('fecha', [$inicio, $fin]);
        }

        /* ==========================
        BUSCADOR
        ========================== */
        if ($request->filled('buscar')) {
            $query->where('concepto', 'like', '%' . $request->buscar . '%');
        }

        /* ==========================
        LISTADO
        ========================== */
        $movimientos = $query
            ->orderByDesc('fecha')
            ->paginate(15);

        $cajas = Caja::with('usuario')
            ->when(! auth()->user()->esAdmin(), fn ($q) => $q->where('usuario_id', auth()->id()))
            ->when($inicio && $fin, fn ($q) => $q->whereBetween('abierta_en', [$inicio, $fin]))
            ->when($request->filled('buscar'), fn ($q) => $q->whereHas(
                'usuario',
                fn ($usuario) => $usuario->where('nombre', 'like', '%' . $request->buscar . '%')
            ))
            ->orderByDesc('abierta_en')
            ->paginate(15, ['*'], 'cajas_page');

        $cajaAbierta = Caja::where('usuario_id', auth()->id())
            ->whereIn('estado', ['abierta', 'pendiente_cierre'])
            ->latest('abierta_en')
            ->first();
        $resumenCaja = $cajaAbierta?->calcularEfectivo();
        $usuariosCaja = auth()->user()->esAdmin()
            ? User::orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        /* ==========================
        KPIs (MISMO RANGO)
        ========================== */

        $ventas = Movimiento::ingresos()
            ->pagados()
            ->activos()
            ->where('subtipo', 'venta')
            ->when($inicio && $fin, fn ($q) =>
                $q->whereBetween('fecha', [$inicio, $fin])
            )
            ->sum('monto');

        $gastos = Movimiento::egresos()
            ->pagados()
            ->activos()
            ->where('subtipo', 'gasto')
            ->when($inicio && $fin, fn ($q) =>
                $q->whereBetween('fecha', [$inicio, $fin])
            )
            ->sum('monto');

        $egresos = Movimiento::egresos()
            ->pagados()
            ->activos()
            ->where('subtipo', 'gasto')
            ->where('referencia_tipo', 'gasto')
            ->when($inicio && $fin, fn ($q) =>
                $q->whereBetween('fecha', [$inicio, $fin])
            )
            ->sum('monto');

        $balance = $ventas - $egresos;

        $ganancias = DetalleVenta::whereHas('venta', function ($q) use ($inicio, $fin) {
                $q->where('estado', 'pagado')
                  ->when($inicio && $fin, fn ($q2) =>
                      $q2->whereBetween('fecha', [$inicio, $fin])
                  );
            })
            ->sum('ganancia');

        /* ==========================
        VISTA
        ========================== */
        return view('movimientos.index', compact(
            'movimientos',
            'ventas',
            'gastos',
            'egresos',
            'balance',
            'ganancias',
            'tab',
            'rango',
            'fecha'
            , 'tipo'
            , 'cajas'
            , 'cajaAbierta'
            , 'resumenCaja'
            , 'usuariosCaja'
        ));
    }

    /* ==========================
    REPORTE PDF
    ========================== */
    public function reporte(Request $request)
    {
        $movimientos = Movimiento::activos()->orderByDesc('fecha')->get();

        $ventas = Movimiento::ingresos()->pagados()->activos()->sum('monto');
        $egresos = Movimiento::egresos()
            ->pagados()
            ->activos()
            ->where('subtipo', 'gasto')
            ->where('referencia_tipo', 'gasto')
            ->sum('monto');

        $balance = $ventas - $egresos;

        $pdf = PDF::loadView('movimientos.reporte_pdf', compact(
            'movimientos',
            'ventas',
            'egresos',
            'balance'
        ));

        return $pdf->stream('reporte_movimientos.pdf');
    }

    /* ==========================
    DETALLE DE VENTA (AJAX)
    ========================== */
    public function detalleVenta($id)
    {
        $venta = Venta::with('detalles.producto', 'cliente')
            ->findOrFail($id);

        return response()->json($venta);
    }

    public function detalleGasto($id)
    {
        $gasto = Gasto::with('usuario')->findOrFail($id);

        return response()->json([
            'id' => $gasto->id,
            'descripcion' => $gasto->descripcion,
            'monto' => (float) $gasto->monto,
            'fecha' => Carbon::parse($gasto->fecha)->format('d/m/Y H:i'),
            'metodo_pago' => ucfirst($gasto->metodo_pago ?? '—'),
            'estado' => $gasto->estado,
            'responsable' => $gasto->usuario->nombre ?? '—',
            'creado_en' => optional($gasto->created_at)->format('d/m/Y H:i'),
        ]);
    }

    public function detalleCompra(Movimiento $movimiento)
    {
        abort_unless(
            $movimiento->tipo === 'egreso'
            && $movimiento->subtipo === 'compra_mercaderia'
            && $movimiento->referencia_tipo === 'lote',
            404
        );

        $lote = Lote::with(['producto', 'proveedor'])->findOrFail($movimiento->referencia_id);
        $pagos = $movimiento->pagosCompra()->with('usuario')->orderByDesc('fecha')->orderByDesc('id')->get();
        $pagado = (float) $pagos->sum('monto');
        $total = (float) $movimiento->monto;

        return response()->json([
            'movimiento_id' => $movimiento->id,
            'lote_id' => $lote->id,
            'producto' => $lote->producto->nombre ?? '—',
            'proveedor' => $lote->proveedor->nombre ?? 'Sin proveedor',
            'comprobante' => trim(ucfirst($lote->tipo_comprobante ?? 'Comprobante').' '.($lote->codigo_comprobante ?? '')),
            'numero_lote' => $lote->numero_lote ?: '—',
            'fecha_compra' => optional($lote->fecha_ingreso)->format('d/m/Y'),
            'fecha_vencimiento' => optional($lote->fecha_vencimiento_pago)->format('d/m/Y'),
            'total' => $total,
            'pagado' => $pagado,
            'saldo' => max(0, $total - $pagado),
            'estado' => $movimiento->estado,
            'puede_pagar' => auth()->user()->esAdmin() && $movimiento->estado === 'pendiente' && $pagado < $total,
            'pagos' => $pagos->map(fn ($p) => [
                'fecha' => $p->fecha->format('d/m/Y'),
                'monto' => (float) $p->monto,
                'metodo' => ucfirst($p->metodo_pago),
                'operacion' => $p->numero_operacion,
                'responsable' => $p->usuario->nombre ?? '—',
            ]),
        ]);
    }

    public function registrarPagoCompra(Request $request, Movimiento $movimiento)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $datos = $request->validate([
            'monto' => ['required', 'numeric', 'gt:0'],
            'fecha' => ['required', 'date'],
            'metodo_pago' => ['required', 'in:efectivo_externo,transferencia,yape,plin,tarjeta,otro'],
            'numero_operacion' => ['nullable', 'string', 'max:80'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($movimiento, $datos) {
            $deuda = Movimiento::lockForUpdate()->findOrFail($movimiento->id);
            abort_unless($deuda->subtipo === 'compra_mercaderia' && $deuda->referencia_tipo === 'lote', 404);

            $pagado = (float) CompraPago::where('movimiento_id', $deuda->id)->sum('monto');
            $saldo = round((float) $deuda->monto - $pagado, 2);
            abort_if($deuda->estado !== 'pendiente' || $saldo <= 0, 422, 'Esta deuda ya fue cancelada.');
            abort_if((float) $datos['monto'] > $saldo, 422, 'El pago no puede superar el saldo pendiente.');

            CompraPago::create([
                ...$datos,
                'movimiento_id' => $deuda->id,
                'lote_id' => $deuda->referencia_id,
                'usuario_id' => auth()->id(),
            ]);

            if (round($saldo - (float) $datos['monto'], 2) <= 0) {
                $deuda->update(['estado' => 'pagado']);
            }
        });

        return response()->json(['message' => 'Pago de proveedor registrado correctamente.']);
    }
}
