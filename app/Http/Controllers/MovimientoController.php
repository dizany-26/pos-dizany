<?php

namespace App\Http\Controllers;

use App\Exports\MovimientosExport;
use Illuminate\Http\Request;
use App\Models\Movimiento;
use App\Models\Venta;
use App\Models\Caja;
use App\Models\Gasto;
use App\Models\User;
use App\Models\Lote;
use App\Models\CompraPago;
use App\Models\Configuracion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;

class MovimientoController extends Controller
{
    public function index(Request $request)
    {
        $usuarioActual = $request->user();
        $esAdmin = $usuarioActual->esAdmin();

        /* ==========================
        PARÁMETROS
        ========================== */
        $tab   = $request->get('tab', 'ingresos');
        if (! in_array($tab, ['ingresos', 'egresos', 'por_cobrar'], true)) {
            $tab = 'ingresos';
        }
        if (! $esAdmin && $tab === 'egresos' && ! $usuarioActual->tienePermiso('gastos')) {
            $tab = 'ingresos';
        }
        $tipo  = $request->get('tipo', 'transacciones');
        $rango = $request->get('rango', 'diario');
        $fecha = trim((string) $request->get('fecha', ''));
        $metodo = strtolower(trim((string) $request->get('metodo', '')));
        $metodosPermitidos = ['efectivo', 'tarjeta', 'transferencia', 'plin', 'yape', 'otro', 'mixto', 'fiado', 'credito'];
        if (! in_array($metodo, $metodosPermitidos, true)) {
            $metodo = '';
        }
        $buscarEn = strtolower(trim((string) $request->get('buscar_en', 'todos')));
        if (! in_array($buscarEn, ['todos', 'documento', 'comprobante', 'concepto'], true)) {
            $buscarEn = 'todos';
        }

        $cajasFiltro = Caja::with('usuario')
            ->when(! $esAdmin, fn ($q) => $q->where('usuario_id', $usuarioActual->id))
            ->orderByDesc('abierta_en')
            ->limit(100)
            ->get();
        $cajaId = $request->integer('caja_id');
        $filtroModo = strtolower(trim((string) $request->get('filtro_modo', '')));
        if (! in_array($filtroModo, ['caja', 'fecha'], true)) {
            $filtroModo = $cajaId > 0 ? 'caja' : 'fecha';
        }
        $cajaSeleccionada = $cajaId > 0 && $filtroModo === 'caja'
            ? $cajasFiltro->firstWhere('id', $cajaId)
            : null;
        if (! $cajaSeleccionada) {
            $cajaId = null;
            $filtroModo = 'fecha';
        }

        // Normalizar separadores
        $fecha = str_replace([' to ', ' | ', ' → '], ' a ', $fecha);

        /* ==========================
        QUERY BASE
        ========================== */
        $query = Movimiento::query()->with(['usuario', 'venta.pagos', 'venta.cliente']);
        if (! $esAdmin) {
            $query->where('usuario_id', $usuarioActual->id);
        }

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
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                    $fecha = now()->format('Y-m-d');
                }

                $inicio = Carbon::parse($fecha)->startOfDay();
                $fin    = Carbon::parse($fecha)->endOfDay();

            } elseif ($rango === 'semanal') {
                $partes = explode(' a ', $fecha);
                $tieneRangoValido = count($partes) === 2
                    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $partes[0])
                    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $partes[1]);

                if ($tieneRangoValido) {
                    $inicio = Carbon::parse($partes[0])->startOfDay();
                    $fin    = Carbon::parse($partes[1])->endOfDay();
                } else {
                    $base = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)
                        ? Carbon::parse($fecha)
                        : now();

                    $inicio = $base->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                    $fin    = $base->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                }

                // La vista siempre recibe el rango semanal completo y consistente.
                $fecha = $inicio->format('Y-m-d') . ' a ' . $fin->format('Y-m-d');

            } elseif ($rango === 'mensual') {
                if (! preg_match('/^\d{4}-\d{2}$/', $fecha)) {
                    $fecha = now()->format('Y-m');
                }

                $carbon = preg_match('/^\d{4}-\d{2}$/', $fecha)
                    ? Carbon::createFromFormat('Y-m', $fecha)
                    : Carbon::createFromLocaleFormat('M Y', 'es', $fecha);

                // Carbon es mutable: usar copias evita que endOfMonth también
                // convierta el inicio en el último día del mes.
                $inicio = $carbon->copy()->startOfMonth()->startOfDay();
                $fin    = $carbon->copy()->endOfMonth()->endOfDay();

            } elseif ($rango === 'anual') {
                $year   = preg_match('/^\d{4}$/', $fecha) ? $fecha : now()->year;
                $fecha  = (string) $year;
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

        if ($cajaSeleccionada) {
            $query->where('caja_id', $cajaSeleccionada->id);
        } elseif ($inicio && $fin) {
            $query->whereBetween('fecha', [$inicio, $fin]);
        }

        if ($metodo !== '') {
            $query->where(function ($porMetodo) use ($metodo) {
                $porMetodo->whereRaw('LOWER(metodo_pago) = ?', [$metodo]);
                if (! in_array($metodo, ['mixto', 'fiado', 'credito'], true)) {
                    $porMetodo->orWhere(function ($mixto) use ($metodo) {
                        $mixto->whereRaw('LOWER(metodo_pago) = ?', ['mixto'])
                            ->whereHas('venta.pagos', fn ($pago) => $pago->whereRaw('LOWER(metodo_pago) = ?', [$metodo]));
                    });
                }
            });
        }

        /* ==========================
        BUSCADOR
        ========================== */
        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->buscar);
            $correlativoNumerico = ctype_digit($buscar) ? (int) ltrim($buscar, '0') : null;

            $query->where(function ($busqueda) use ($buscar, $correlativoNumerico, $buscarEn) {
                if (in_array($buscarEn, ['todos', 'concepto'], true)) {
                    $busqueda->where('concepto', 'like', '%' . $buscar . '%');
                }

                if (in_array($buscarEn, ['todos', 'comprobante'], true)) {
                    $metodoComprobante = $buscarEn === 'todos' ? 'orWhere' : 'where';
                    $busqueda->{$metodoComprobante}(function ($porComprobante) use ($buscar, $correlativoNumerico) {
                        $porComprobante->where('referencia_tipo', 'venta')
                            ->whereHas('venta', function ($venta) use ($buscar, $correlativoNumerico) {
                                $venta->where('serie', 'like', '%' . $buscar . '%')
                                    ->orWhereRaw("CONCAT(serie, '-', LPAD(correlativo, 6, '0')) LIKE ?", ['%' . $buscar . '%']);

                                if ($correlativoNumerico !== null) {
                                    $venta->orWhere('correlativo', $correlativoNumerico);
                                }

                            });
                    });
                }

                if (in_array($buscarEn, ['todos', 'documento'], true)) {
                    $metodoDocumento = $buscarEn === 'todos' ? 'orWhereHas' : 'whereHas';
                    $busqueda->{$metodoDocumento}('venta.cliente', function ($cliente) use ($buscar) {
                        $cliente->where('dni', 'like', '%' . $buscar . '%')
                            ->orWhere('ruc', 'like', '%' . $buscar . '%');
                    });
                }
            });
        }

        /* ==========================
        LISTADO
        ========================== */
        $movimientos = $query
            ->orderByDesc('fecha')
            ->paginate(15);

        $movimientos->getCollection()->each(function (Movimiento $movimiento) use ($metodo) {
            $movimiento->monto_mostrado = (float) $movimiento->monto;
            $movimiento->monto_total_venta = null;
            if ($metodo !== '' && $metodo !== 'mixto' && strtolower((string) $movimiento->metodo_pago) === 'mixto') {
                $movimiento->monto_total_venta = (float) $movimiento->monto;
                $pagosVenta = $movimiento->venta?->pagos ?? collect();
                $movimiento->monto_mostrado = (float) $pagosVenta
                    ->where('metodo_pago', $metodo)->sum('monto');
            }
        });

        $cajas = Caja::with('usuario')
            ->when(! auth()->user()->esAdmin(), fn ($q) => $q->where('usuario_id', auth()->id()))
            ->when($cajaSeleccionada, fn ($q) => $q->whereKey($cajaSeleccionada->id))
            ->when(! $cajaSeleccionada && $inicio && $fin, fn ($q) => $q->whereBetween('abierta_en', [$inicio, $fin]))
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
        // Un administrador tambien puede operar una caja. Solo mostramos
        // usuarios que realmente pueden trabajar en ventas, ademas de los
        // administradores, para evitar asignar caja a perfiles sin acceso.
        $usuariosCaja = auth()->user()->esAdmin()
            ? User::with(['rol', 'permisos'])
                ->orderBy('nombre')
                ->get()
                ->filter(fn (User $usuario) => $usuario->esAdmin() || $usuario->tienePermiso('ventas'))
                ->sortByDesc(fn (User $usuario) => (int) $usuario->id === (int) auth()->id())
                ->values()
            : collect();

        /* ==========================
        KPIs (MISMO RANGO)
        ========================== */

        $ventasQuery = Movimiento::ingresos()
            ->pagados()
            ->activos()
            ->where('movimientos.subtipo', 'venta')
            ->when(! $esAdmin, fn ($q) => $q->where('movimientos.usuario_id', $usuarioActual->id))
            ->when($cajaSeleccionada, fn ($q) => $q->where('movimientos.caja_id', $cajaSeleccionada->id))
            ->when(! $cajaSeleccionada && $inicio && $fin, fn ($q) =>
                $q->whereBetween('movimientos.fecha', [$inicio, $fin])
            );

        if ($metodo === '') {
            $ventas = (float) $ventasQuery->sum('movimientos.monto');
        } elseif ($metodo === 'mixto') {
            $ventas = (float) $ventasQuery
                ->whereRaw('LOWER(movimientos.metodo_pago) = ?', ['mixto'])
                ->sum('movimientos.monto');
        } else {
            $ventasDirectas = (float) (clone $ventasQuery)
                ->whereRaw('LOWER(movimientos.metodo_pago) = ?', [$metodo])
                ->sum('movimientos.monto');
            $ventasMixtas = (float) (clone $ventasQuery)
                ->whereRaw('LOWER(movimientos.metodo_pago) = ?', ['mixto'])
                ->join('pagos_venta as pv', 'pv.venta_id', '=', 'movimientos.referencia_id')
                ->whereRaw('LOWER(pv.metodo_pago) = ?', [$metodo])
                ->sum('pv.monto');
            $ventas = round($ventasDirectas + $ventasMixtas, 2);
        }

        // Los cobros posteriores de ventas fiadas o a credito son ingresos
        // reales de la jornada, pero no son una venta nueva. Por eso no deben
        // inflar "Ventas totales", aunque si deben formar parte del balance.
        $cobrosPendientes = (float) Movimiento::ingresos()
            ->pagados()
            ->activos()
            ->whereIn('subtipo', ['cobro_fiado', 'cobro_credito'])
            ->when(! $esAdmin, fn ($q) => $q->where('usuario_id', $usuarioActual->id))
            ->when($cajaSeleccionada, fn ($q) => $q->where('caja_id', $cajaSeleccionada->id))
            ->when(! $cajaSeleccionada && $inicio && $fin, fn ($q) =>
                $q->whereBetween('fecha', [$inicio, $fin])
            )
            ->when($metodo !== '', fn ($q) => $q->whereRaw('LOWER(metodo_pago) = ?', [$metodo]))
            ->sum('monto');

        $gastos = Movimiento::egresos()
            ->pagados()
            ->activos()
            ->where('subtipo', 'gasto')
            ->when(! $esAdmin, fn ($q) => $q->where('usuario_id', $usuarioActual->id))
            ->when($cajaSeleccionada, fn ($q) => $q->where('caja_id', $cajaSeleccionada->id))
            ->when(! $cajaSeleccionada && $inicio && $fin, fn ($q) =>
                $q->whereBetween('fecha', [$inicio, $fin])
            )
            ->when($metodo !== '', fn ($q) => $q->whereRaw('LOWER(metodo_pago) = ?', [$metodo]))
            ->sum('monto');

        $egresos = Movimiento::egresos()
            ->pagados()
            ->activos()
            ->where('subtipo', 'gasto')
            ->where('referencia_tipo', 'gasto')
            ->when(! $esAdmin, fn ($q) => $q->where('usuario_id', $usuarioActual->id))
            ->when($cajaSeleccionada, fn ($q) => $q->where('caja_id', $cajaSeleccionada->id))
            ->when(! $cajaSeleccionada && $inicio && $fin, fn ($q) =>
                $q->whereBetween('fecha', [$inicio, $fin])
            )
            ->when($metodo !== '', fn ($q) => $q->whereRaw('LOWER(metodo_pago) = ?', [$metodo]))
            ->sum('monto');

        $balance = $ventas + $cobrosPendientes - $egresos;

        // La utilidad se reconoce conforme ingresa el dinero. Esto distribuye
        // proporcionalmente la ganancia entre adelantos y cobros posteriores,
        // y evita contabilizar dos veces una venta fiada o a credito.
        $gananciasQuery = Movimiento::query()
            ->join('ventas as v_ganancia', 'v_ganancia.id', '=', 'movimientos.referencia_id')
            ->join('detalle_ventas as dv_ganancia', 'dv_ganancia.venta_id', '=', 'v_ganancia.id')
            ->where('movimientos.tipo', 'ingreso')
            ->where('movimientos.estado', 'pagado')
            ->whereIn('movimientos.subtipo', ['venta', 'cobro_fiado', 'cobro_credito'])
            ->when(! $esAdmin, fn ($q) => $q->where('movimientos.usuario_id', $usuarioActual->id))
            ->when($cajaSeleccionada, fn ($q) => $q->where('movimientos.caja_id', $cajaSeleccionada->id))
            ->when(! $cajaSeleccionada && $inicio && $fin, fn ($q) =>
                $q->whereBetween('movimientos.fecha', [$inicio, $fin])
            );

        $gananciaPorMonto = 'SUM(CASE WHEN v_ganancia.total > 0 '
            . 'THEN dv_ganancia.ganancia * movimientos.monto / v_ganancia.total ELSE 0 END)';

        if ($metodo === '') {
            $ganancias = (float) $gananciasQuery->selectRaw($gananciaPorMonto . ' as total')->value('total');
        } elseif ($metodo === 'mixto') {
            $ganancias = (float) $gananciasQuery
                ->whereRaw('LOWER(movimientos.metodo_pago) = ?', ['mixto'])
                ->selectRaw($gananciaPorMonto . ' as total')
                ->value('total');
        } else {
            $gananciaDirecta = (float) (clone $gananciasQuery)
                ->whereRaw('LOWER(movimientos.metodo_pago) = ?', [$metodo])
                ->selectRaw($gananciaPorMonto . ' as total')
                ->value('total');

            $gananciaMixta = 0.0;
            if (! in_array($metodo, ['fiado', 'credito'], true)) {
                $gananciaMixta = (float) (clone $gananciasQuery)
                    ->whereRaw('LOWER(movimientos.metodo_pago) = ?', ['mixto'])
                    ->join('pagos_venta as pv_ganancia', 'pv_ganancia.venta_id', '=', 'v_ganancia.id')
                    ->whereRaw('LOWER(pv_ganancia.metodo_pago) = ?', [$metodo])
                    ->selectRaw('SUM(CASE WHEN v_ganancia.total > 0 '
                        . 'THEN dv_ganancia.ganancia * pv_ganancia.monto / v_ganancia.total ELSE 0 END) as total')
                    ->value('total');
            }

            $ganancias = round($gananciaDirecta + $gananciaMixta, 2);
        }

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
            , 'cajasFiltro'
            , 'cajaSeleccionada'
            , 'cajaId'
            , 'filtroModo'
            , 'cajaAbierta'
            , 'resumenCaja'
            , 'usuariosCaja'
            , 'metodo'
            , 'buscarEn'
        ));
    }

    /* ==========================
    REPORTES PDF / EXCEL
    ========================== */
    public function reporte(Request $request)
    {
        abort_unless($request->user()->esAdmin() || $request->user()->tienePermiso('reportes'), 403);

        $tab = in_array($request->get('tab'), ['ingresos', 'egresos', 'por_cobrar'], true)
            ? $request->get('tab')
            : 'ingresos';
        $rango = $request->get('rango', 'diario');
        $fecha = str_replace([' to ', ' | ', ' → '], ' a ', trim((string) $request->get('fecha', '')));
        $metodo = strtolower(trim((string) $request->get('metodo', '')));
        $buscar = trim((string) $request->get('buscar', ''));
        $buscarEn = strtolower(trim((string) $request->get('buscar_en', 'todos')));
        if (! in_array($buscarEn, ['todos', 'documento', 'comprobante', 'concepto'], true)) {
            $buscarEn = 'todos';
        }
        $cajaId = $request->integer('caja_id');
        $cajaSeleccionada = null;
        $filtroModo = $request->get('filtro_modo', $cajaId > 0 ? 'caja' : 'fecha');

        if ($cajaId > 0 && $filtroModo === 'caja') {
            $cajaSeleccionada = Caja::query()
                ->with('usuario')
                ->when(! $request->user()->esAdmin(), fn ($query) => $query->where('usuario_id', $request->user()->id))
                ->findOrFail($cajaId);
        }

        [$inicio, $fin, $periodo] = $this->resolverPeriodoReporte($rango, $fecha);

        $query = Movimiento::query()
            ->with(['usuario', 'venta.pagos', 'venta.cliente'])
            ->activos()
            ->when($cajaSeleccionada, fn ($query) => $query->where('caja_id', $cajaSeleccionada->id))
            ->when(! $cajaSeleccionada && $inicio && $fin, fn ($query) => $query->whereBetween('fecha', [$inicio, $fin]));

        match ($tab) {
            'egresos' => $query->egresos()->pagados()->where('subtipo', 'gasto')->where('referencia_tipo', 'gasto'),
            'por_cobrar' => $query->ingresos()->pendientes()->where('referencia_tipo', 'venta')->whereIn('metodo_pago', ['fiado', 'credito']),
            default => $query->ingresos()->pagados(),
        };

        if ($metodo !== '') {
            $query->where(function ($porMetodo) use ($metodo) {
                $porMetodo->whereRaw('LOWER(metodo_pago) = ?', [$metodo]);
                if (! in_array($metodo, ['mixto', 'fiado', 'credito'], true)) {
                    $porMetodo->orWhere(function ($mixto) use ($metodo) {
                        $mixto->whereRaw('LOWER(metodo_pago) = ?', ['mixto'])
                            ->whereHas('venta.pagos', fn ($pago) => $pago->whereRaw('LOWER(metodo_pago) = ?', [$metodo]));
                    });
                }
            });
        }

        if ($buscar !== '') {
            $correlativo = ctype_digit($buscar) ? (int) ltrim($buscar, '0') : null;
            $query->where(function ($busqueda) use ($buscar, $correlativo, $buscarEn) {
                if (in_array($buscarEn, ['todos', 'concepto'], true)) {
                    $busqueda->where('concepto', 'like', "%{$buscar}%");
                }
                if (in_array($buscarEn, ['todos', 'comprobante'], true)) {
                    $metodoComprobante = $buscarEn === 'todos' ? 'orWhereHas' : 'whereHas';
                    $busqueda->{$metodoComprobante}('venta', function ($venta) use ($buscar, $correlativo) {
                        $venta->where('serie', 'like', "%{$buscar}%")
                            ->orWhereRaw("CONCAT(serie, '-', LPAD(correlativo, 6, '0')) LIKE ?", ["%{$buscar}%"]);
                        if ($correlativo !== null) {
                            $venta->orWhere('correlativo', $correlativo);
                        }
                    });
                }
                if (in_array($buscarEn, ['todos', 'documento'], true)) {
                    $metodoDocumento = $buscarEn === 'todos' ? 'orWhereHas' : 'whereHas';
                    $busqueda->{$metodoDocumento}('venta.cliente', function ($cliente) use ($buscar) {
                        $cliente->where('dni', 'like', "%{$buscar}%")
                            ->orWhere('ruc', 'like', "%{$buscar}%");
                    });
                }
            });
        }

        $movimientos = $query->orderByDesc('fecha')->orderByDesc('created_at')->get();
        $ingresos = (float) $movimientos->where('tipo', 'ingreso')->sum('monto');
        $egresos = (float) $movimientos->where('tipo', 'egreso')->sum('monto');
        $balance = $ingresos - $egresos;
        $config = Configuracion::first();
        $logoPath = $config?->logo && file_exists(public_path($config->logo))
            ? public_path($config->logo)
            : (file_exists(public_path('images/logo.png')) ? public_path('images/logo.png') : null);
        $logoBase64 = $logoPath
            ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath))
            : null;
        $filtroDescripcion = $cajaSeleccionada
            ? "Caja #{$cajaSeleccionada->id} · " . ($cajaSeleccionada->usuario?->nombre ?? 'Sin cajero')
            : $periodo;
        $datos = compact('movimientos', 'ingresos', 'egresos', 'balance', 'filtroDescripcion', 'tab', 'metodo', 'buscar', 'config', 'logoPath', 'logoBase64');
        $nombreBase = 'movimientos_' . now()->format('Ymd_His');

        if (strtolower((string) $request->get('formato')) === 'excel') {
            return Excel::download(new MovimientosExport($datos), $nombreBase . '.xlsx');
        }

        $pdf = PDF::loadView('movimientos.reporte_pdf', $datos)->setPaper('a4', 'landscape');

        return $pdf->download($nombreBase . '.pdf');
    }

    private function resolverPeriodoReporte(string $rango, string $fecha): array
    {
        try {
            if ($rango === 'diario') {
                $dia = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : now()->format('Y-m-d');
                return [Carbon::parse($dia)->startOfDay(), Carbon::parse($dia)->endOfDay(), Carbon::parse($dia)->format('d/m/Y')];
            }
            if ($rango === 'semanal') {
                [$desde, $hasta] = array_pad(explode(' a ', $fecha), 2, null);
                $inicio = $desde ? Carbon::parse($desde)->startOfDay() : now()->startOfWeek()->startOfDay();
                $fin = $hasta ? Carbon::parse($hasta)->endOfDay() : $inicio->copy()->endOfWeek()->endOfDay();
                return [$inicio, $fin, $inicio->format('d/m/Y') . ' - ' . $fin->format('d/m/Y')];
            }
            if ($rango === 'mensual') {
                $mes = preg_match('/^\d{4}-\d{2}$/', $fecha) ? Carbon::createFromFormat('Y-m', $fecha) : now();
                return [$mes->copy()->startOfMonth(), $mes->copy()->endOfMonth(), ucfirst($mes->locale('es')->translatedFormat('F Y'))];
            }
            if ($rango === 'anual') {
                $year = preg_match('/^\d{4}$/', $fecha) ? (int) $fecha : now()->year;
                return [Carbon::create($year, 1, 1)->startOfDay(), Carbon::create($year, 12, 31)->endOfDay(), (string) $year];
            }
            if ($rango === 'personalizado') {
                [$desde, $hasta] = array_pad(explode(' a ', $fecha), 2, null);
                if ($desde && $hasta) {
                    $inicio = Carbon::parse($desde)->startOfDay();
                    $fin = Carbon::parse($hasta)->endOfDay();
                    return [$inicio, $fin, $inicio->format('d/m/Y') . ' - ' . $fin->format('d/m/Y')];
                }
            }
        } catch (\Throwable) {
        }

        return [null, null, 'Todos los movimientos'];
    }

    /* ==========================
    DETALLE DE VENTA (AJAX)
    ========================== */
    public function detalleVenta($id)
    {
        $venta = Venta::with('detalles.producto', 'cliente')
            ->findOrFail($id);

        abort_if(! auth()->user()->esAdmin() && (int) $venta->usuario_id !== (int) auth()->id(), 403);

        return response()->json($venta);
    }

    public function detalleGasto($id)
    {
        $gasto = Gasto::with('usuario')->findOrFail($id);

        $puedeVer = auth()->user()->esAdmin()
            || (auth()->user()->tienePermiso('gastos') && (int) $gasto->usuario_id === (int) auth()->id());
        abort_unless($puedeVer, 403);

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
        abort_unless(auth()->user()->esAdmin(), 403);

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
