<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Producto;
use Carbon\Carbon;
use App\Models\Categoria;
use App\Models\Proveedor;
use App\Models\Movimiento;
use App\Models\Lote;
use App\Models\LoteMovimiento;
use App\Models\CompraPago;
use Illuminate\Pagination\LengthAwarePaginator;

class InventarioController extends Controller
{
    public function lotes()
{
    $lotes = Lote::with(['producto', 'proveedor'])
        ->withCount('movimientos')
        ->where('activo', 1)
        ->orderByRaw('fecha_vencimiento IS NULL') // los sin vencimiento al final
        ->orderBy('fecha_vencimiento', 'asc')    // FEFO REAL
        ->orderBy('fecha_ingreso', 'asc')        // desempate
        ->get();

        $productos = Producto::where('activo', 1)->orderBy('nombre')->get();

    return view('inventario.lotes_index', compact('lotes', 'productos'));
}

public function actualizarStock(Request $request, $id)
{
    $producto = Producto::findOrFail($id);
    $producto->stock = $request->input('stock');
    $producto->save();

    return response()->json(['success' => true]);
}

public function resumen()
{
    // 🔴 Productos sin stock total (ningún lote con stock > 0)
    $productosConStockCalculado = Producto::where('activo', 1)
        ->withSum(['lotes as stock_total' => fn ($q) => $q->where('activo', 1)], 'stock_actual')
        ->get();

    $productosSinStockColeccion = $productosConStockCalculado->filter(
        fn ($producto) => (int) ($producto->stock_total ?? 0) <= 0
    );
    $productosSinStock = $productosSinStockColeccion->count();


    // 🟡 Productos con stock bajo (sumando todos los lotes)
    $productosStockBajo = $productosConStockCalculado->filter(function ($producto) {
        $stock = (int) ($producto->stock_total ?? 0);
        return $stock > 0 && $stock <= (int) ($producto->stock_minimo ?? 10);
    });
    $productosCriticos = $productosSinStockColeccion
        ->concat($productosStockBajo)
        ->sortBy('nombre')
        ->values();


    // ⚠️ Lotes próximos a vencer (30 días)
    $lotesPorVencer = Lote::whereNotNull('fecha_vencimiento')
        ->whereDate('fecha_vencimiento', '<=', now()->addDays(30))
        ->where('stock_actual', '>', 0)
        ->with('producto')
        ->get();


    // 📦 Total unidades en almacén
    $totalUnidades = Lote::where('activo', 1)->sum('stock_actual');


    // 💰 Inversión real (lo que te costó)
    $inversion = Lote::where('activo', 1)
        ->where('stock_actual', '>', 0)
        ->sum(DB::raw('stock_actual * precio_compra'));

    // 💵 Valor comercial actual
    $valorVenta = Lote::where('activo', 1)
        ->where('stock_actual', '>', 0)
        ->sum(DB::raw('stock_actual * precio_unidad'));

    // 📈 Margen potencial
    $margenPotencial = $valorVenta - $inversion;

    // 📊 Porcentaje de rentabilidad
    $porcentajeRentabilidad = $inversion > 0 
        ? ($margenPotencial / $inversion) * 100 
        : 0;

        return view('inventario.resumen', compact(
        'productosSinStock',
        'productosStockBajo',
        'productosCriticos',
        'lotesPorVencer',
        'totalUnidades',
        'inversion',
        'valorVenta',
        'margenPotencial',
        'porcentajeRentabilidad'
    ));

}


public function lote()
{
    $productos = Producto::where('activo', 1)
        ->orderBy('nombre')
        ->get();

    $proveedores = Proveedor::orderBy('nombre')->get();
    $compraEnCurso = session('inventario_compra_en_curso', []);

    return view('inventario.lote', compact('productos', 'proveedores', 'compraEnCurso'));
}

public function limpiarCompraEnCurso()
{
    session()->forget('inventario_compra_en_curso');

    return redirect()->route('inventario.lote')->with('success', 'Listo para registrar una nueva compra.');
}


public function historialCompras(Request $request)
{
    $query = Lote::with(['producto', 'proveedor', 'compraMovimiento.pagosCompra'])
        ->where('activo', 1);

    $query->when($request->filled('proveedor'), fn ($q) => $q->where('proveedor_id', $request->proveedor))
        ->when($request->filled('desde'), fn ($q) => $q->whereDate('fecha_ingreso', '>=', $request->desde))
        ->when($request->filled('hasta'), fn ($q) => $q->whereDate('fecha_ingreso', '<=', $request->hasta));

    if ($request->filled('buscar')) {
        $buscar = $request->buscar;
        $query->where(function ($q) use ($buscar) {
            $q->where('codigo_comprobante', 'like', "%{$buscar}%")
                ->orWhereHas('producto', fn ($p) => $p->where('nombre', 'like', "%{$buscar}%"))
                ->orWhereHas('proveedor', fn ($p) => $p->where('nombre', 'like', "%{$buscar}%"));
        });
    }

    $agrupadas = $query->orderByDesc('fecha_ingreso')->orderByDesc('id')->get()
        ->groupBy(fn (Lote $lote) => $this->claveCompra($lote))
        ->map(function ($lotes) {
            $total = $lotes->sum(fn ($lote) => (float) $lote->stock_inicial * (float) $lote->precio_compra);
            $pagado = $lotes->sum(function ($lote) {
                if ($lote->condicion_pago !== 'credito') return (float) $lote->stock_inicial * (float) $lote->precio_compra;
                $movimiento = $lote->compraMovimiento;
                if (! $movimiento) return 0;
                $abonos = (float) $movimiento->pagosCompra->sum('monto');
                return $movimiento->estado === 'pagado' && $abonos <= 0 ? (float) $movimiento->monto : $abonos;
            });
            $saldo = max(0, round($total - $pagado, 2));
            $estado = $saldo <= 0 ? 'pagado' : ($pagado > 0 ? 'parcial' : 'pendiente');

            return compact('lotes', 'total', 'pagado', 'saldo', 'estado') + ['principal' => $lotes->first()];
        })->values();

    if (in_array($request->estado, ['pagado', 'pendiente', 'parcial'], true)) {
        $agrupadas = $agrupadas->where('estado', $request->estado)->values();
    }

    $pagina = LengthAwarePaginator::resolveCurrentPage();
    $porPagina = 15;
    $compras = new LengthAwarePaginator(
        $agrupadas->slice(($pagina - 1) * $porPagina, $porPagina)->values(),
        $agrupadas->count(), $porPagina, $pagina,
        ['path' => $request->url(), 'query' => $request->query()]
    );
    $proveedores = Proveedor::orderBy('nombre')->get(['id', 'nombre']);

    return view('inventario.historial_compras', compact('compras', 'proveedores'));
}

public function detalleCompraLote(Lote $lote)
{
    $lotes = $this->lotesDeCompra($lote);
    $total = $lotes->sum(fn ($item) => (float) $item->stock_inicial * (float) $item->precio_compra);
    $pagos = $lotes->flatMap(fn ($item) => $item->compraMovimiento?->pagosCompra ?? collect())
        ->sortByDesc('fecha')->values();
    $pagado = $lotes->sum(function ($item) {
        if ($item->condicion_pago !== 'credito') return (float) $item->stock_inicial * (float) $item->precio_compra;
        $movimiento = $item->compraMovimiento;
        if (! $movimiento) return 0;
        $abonos = (float) $movimiento->pagosCompra->sum('monto');
        return $movimiento->estado === 'pagado' && $abonos <= 0 ? (float) $movimiento->monto : $abonos;
    });
    $saldo = max(0, round($total - $pagado, 2));
    $primerMovimiento = $lotes->map(fn ($item) => $item->compraMovimiento)->filter()->first();

    return response()->json([
        'movimiento_id' => $primerMovimiento?->id,
        'lote_id' => $lote->id,
        'producto' => $lotes->count().' producto(s)',
        'proveedor' => $lote->proveedor->nombre ?? 'Sin proveedor',
        'comprobante' => trim(ucfirst($lote->tipo_comprobante ?? 'Comprobante').' '.($lote->codigo_comprobante ?? '')),
        'numero_lote' => $lote->numero_lote ?: '—',
        'fecha_compra' => optional($lote->fecha_ingreso)->format('d/m/Y'),
        'fecha_vencimiento' => optional($lote->fecha_vencimiento_pago)->format('d/m/Y'),
        'total' => $total,
        'pagado' => $pagado,
        'saldo' => $saldo,
        'estado' => $saldo <= 0 ? 'pagado' : ($pagado > 0 ? 'parcial' : 'pendiente'),
        'puede_pagar' => auth()->user()->esAdmin() && $saldo > 0,
        'pago_url' => route('inventario.compras.pagos', $lote),
        'productos' => $lotes->map(fn ($item) => [
            'nombre' => $item->producto->nombre ?? '—',
            'descripcion' => $item->producto->descripcion ?? null,
            'cantidad' => (int) $item->stock_inicial,
            'costo' => (float) $item->precio_compra,
            'subtotal' => round((float) $item->stock_inicial * (float) $item->precio_compra, 2),
        ])->values(),
        'pagos' => $pagos->map(fn ($p) => [
            'fecha' => $p->fecha->format('d/m/Y'),
            'monto' => (float) $p->monto,
            'metodo' => ucfirst($p->metodo_pago),
            'operacion' => $p->numero_operacion,
            'responsable' => $p->usuario->nombre ?? '—',
        ])->values(),
    ]);
}

public function registrarPagoCompra(Request $request, Lote $lote)
{
    abort_unless(auth()->user()->esAdmin(), 403);
    $datos = $request->validate([
        'monto' => ['required', 'numeric', 'gt:0'], 'fecha' => ['required', 'date'],
        'metodo_pago' => ['required', 'in:efectivo_externo,transferencia,yape,plin,tarjeta,otro'],
        'numero_operacion' => ['nullable', 'string', 'max:80'], 'observacion' => ['nullable', 'string', 'max:500'],
    ]);

    DB::transaction(function () use ($lote, $datos) {
        $lotes = $this->lotesDeCompra($lote);
        $movimientos = Movimiento::whereIn('referencia_id', $lotes->pluck('id'))
            ->where('referencia_tipo', 'lote')->where('subtipo', 'compra_mercaderia')
            ->lockForUpdate()->orderBy('id')->get();
        $saldoTotal = $movimientos->sum(fn ($m) => max(0, (float) $m->monto - (float) CompraPago::where('movimiento_id', $m->id)->sum('monto')));
        abort_if((float) $datos['monto'] > round($saldoTotal, 2), 422, 'El pago no puede superar el saldo de la compra.');

        $restante = (float) $datos['monto'];
        foreach ($movimientos as $movimiento) {
            if ($restante <= 0) break;
            $pagado = (float) CompraPago::where('movimiento_id', $movimiento->id)->sum('monto');
            $saldo = max(0, (float) $movimiento->monto - $pagado);
            if ($saldo <= 0) continue;
            $abono = min($restante, $saldo);
            CompraPago::create(array_merge($datos, [
                'movimiento_id' => $movimiento->id, 'lote_id' => $movimiento->referencia_id,
                'usuario_id' => auth()->id(), 'monto' => $abono,
            ]));
            if (round($saldo - $abono, 2) <= 0) $movimiento->update(['estado' => 'pagado']);
            $restante = round($restante - $abono, 2);
        }
        abort_if($restante > 0, 422, 'No se encontraron deudas pendientes para esta compra.');
    });

    return response()->json(['message' => 'Pago de la compra registrado correctamente.']);
}

private function claveCompra(Lote $lote): string
{
    if (blank($lote->codigo_comprobante)) return 'lote-'.$lote->id;
    return implode('|', [$lote->proveedor_id, strtolower($lote->tipo_comprobante ?? ''), strtoupper(trim($lote->codigo_comprobante)), optional($lote->fecha_ingreso)->format('Y-m-d')]);
}

private function lotesDeCompra(Lote $lote)
{
    $query = Lote::with(['producto', 'proveedor', 'compraMovimiento.pagosCompra.usuario']);
    if (blank($lote->codigo_comprobante)) return $query->whereKey($lote->id)->get();
    return $query->where('proveedor_id', $lote->proveedor_id)
        ->where('tipo_comprobante', $lote->tipo_comprobante)
        ->where('codigo_comprobante', $lote->codigo_comprobante)
        ->whereDate('fecha_ingreso', $lote->fecha_ingreso)->get();
}

public function storeLote(Request $request)
{
    $request->validate([
        'producto_id'       => 'required|exists:productos,id',
        'proveedor_id'      => 'nullable|exists:proveedores,id',
        'tipo_comprobante'  => 'nullable|in:factura,boleta,nota_venta,guia,otro',
        'codigo_comprobante'=> 'nullable|string|max:100',
        'condicion_pago'    => 'required|in:contado,credito',
        'metodo_pago'       => 'nullable|required_if:condicion_pago,contado|in:efectivo,yape,plin,transferencia,tarjeta,otro',
        'fecha_vencimiento_pago' => 'nullable|required_if:condicion_pago,credito|date|after_or_equal:fecha_ingreso',
        'observaciones_compra' => 'nullable|string|max:500',
        'stock_inicial'     => 'required|integer|min:1',
        'precio_compra'     => 'required|numeric|min:0',
        'precio_unidad'     => 'required|numeric|min:0',
        'precio_paquete'    => 'nullable|numeric|min:0',
        'precio_caja'       => 'nullable|numeric|min:0',
        'actualizar_precio_producto' => 'nullable|boolean',
        'stock_minimo'       => 'required|integer|min:0|max:999999',
        'fecha_ingreso'     => 'required|date',
        'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_ingreso',
    ]);

    if ($request->condicion_pago === 'credito' && ! $request->filled('proveedor_id')) {
        return back()
            ->withErrors(['proveedor_id' => 'Selecciona un proveedor para registrar una compra por pagar.'])
            ->withInput();
    }

    $productoSeleccionado = Producto::findOrFail($request->producto_id);
    if ($productoSeleccionado->maneja_vencimiento && ! $request->filled('fecha_vencimiento')) {
        return back()
            ->withErrors(['fecha_vencimiento' => 'Este producto controla vencimiento; selecciona la fecha correspondiente.'])
            ->withInput();
    }

        DB::transaction(function () use ($request) {

        $producto = Producto::whereKey($request->producto_id)
            ->lockForUpdate()
            ->firstOrFail();

        $debeActualizarPrecio = $request->boolean('actualizar_precio_producto')
            || $producto->precio_venta === null;

        $producto->update(['stock_minimo' => $request->stock_minimo]);

        if ($debeActualizarPrecio) {
            $producto->update([
                'precio_venta' => $request->precio_unidad,
                'precio_paquete' => $request->precio_paquete,
                'precio_caja' => $request->precio_caja,
            ]);
        }

        $ultimoNumero = Lote::where('producto_id', $request->producto_id)
            ->lockForUpdate()
            ->max('numero_lote');

        $numeroLote = ($ultimoNumero ?? 0) + 1;

        $lote = Lote::create([
            'producto_id'       => $request->producto_id,
            'proveedor_id'      => $request->proveedor_id,
            'numero_lote'       => $numeroLote, // 👈 AQUÍ
            'codigo_comprobante'=> $request->codigo_comprobante,
            'tipo_comprobante'  => $request->tipo_comprobante,
            'condicion_pago'    => $request->condicion_pago,
            'metodo_pago'       => $request->condicion_pago === 'contado' ? $request->metodo_pago : 'credito',
            'fecha_vencimiento_pago' => $request->condicion_pago === 'credito'
                ? $request->fecha_vencimiento_pago
                : null,
            'observaciones_compra' => $request->observaciones_compra,
            'fecha_ingreso'     => $request->fecha_ingreso,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'stock_inicial'     => $request->stock_inicial,
            'stock_actual'      => $request->stock_inicial,
            'precio_compra'     => $request->precio_compra,
            'precio_unidad'     => $debeActualizarPrecio
                ? $request->precio_unidad
                : $producto->precio_venta,
            'precio_paquete'    => $debeActualizarPrecio
                ? $request->precio_paquete
                : $producto->precio_paquete,
            'precio_caja'       => $debeActualizarPrecio
                ? $request->precio_caja
                : $producto->precio_caja,
            'activo'            => 1,
        ]);

        if ($request->condicion_pago === 'credito') {
            Movimiento::create([
                'caja_id' => null,
                'usuario_id' => auth()->id(),
                'fecha' => $request->fecha_ingreso,
                'hora' => now()->format('H:i:s'),
                'tipo' => 'egreso',
                'subtipo' => 'compra_mercaderia',
                'concepto' => 'Compra por pagar · '.$producto->nombre
                    .($request->codigo_comprobante ? ' · '.$request->codigo_comprobante : ''),
                'monto' => round((float) $request->stock_inicial * (float) $request->precio_compra, 2),
                'metodo_pago' => 'credito',
                'estado' => 'pendiente',
                'referencia_id' => $lote->id,
                'referencia_tipo' => 'lote',
            ]);
        }
    });

        session(['inventario_compra_en_curso' => [
            'proveedor_id' => $request->proveedor_id,
            'tipo_comprobante' => $request->tipo_comprobante,
            'codigo_comprobante' => $request->codigo_comprobante,
            'fecha_ingreso' => $request->fecha_ingreso,
            'condicion_pago' => $request->condicion_pago,
            'metodo_pago' => $request->condicion_pago === 'contado' ? $request->metodo_pago : null,
            'fecha_vencimiento_pago' => $request->condicion_pago === 'credito' ? $request->fecha_vencimiento_pago : null,
            'observaciones_compra' => $request->observaciones_compra,
        ]]);
        return redirect()
            ->route('inventario.lote')
            ->with(
                'success',
                $request->condicion_pago === 'credito'
                    ? 'Inventario registrado y compra agregada al Historial de compras.'
                    : 'Ingreso de inventario registrado correctamente.'
            );
    
}

    public function edit(Lote $lote)
    {
        return view('inventario.lote_edit', compact('lote'));
    }

public function update(Request $request, Lote $lote)
{
    $request->validate([
        'codigo_comprobante'        => 'nullable|string|max:100',
        'fecha_vencimiento' => 'nullable|date',
        'precio_unidad'     => 'nullable|numeric|min:0',
        'precio_paquete'    => 'nullable|numeric|min:0',
        'precio_caja'       => 'nullable|numeric|min:0',
        'actualizar_precio_producto' => 'nullable|boolean',
    ]);

    DB::transaction(function () use ($request, $lote) {
        $lote = Lote::whereKey($lote->id)->lockForUpdate()->firstOrFail();

        $lote->update([
            'codigo_comprobante' => $request->codigo_comprobante,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'precio_unidad' => $request->precio_unidad,
            'precio_paquete' => $request->precio_paquete,
            'precio_caja' => $request->precio_caja,
        ]);

        if ($request->boolean('actualizar_precio_producto')) {
            Producto::whereKey($lote->producto_id)
                ->lockForUpdate()
                ->firstOrFail()
                ->update([
                    'precio_venta' => $request->precio_unidad,
                    'precio_paquete' => $request->precio_paquete,
                    'precio_caja' => $request->precio_caja,
                ]);
        }
    });

    return redirect()
        ->route('inventario.lotes')
        ->with(
            'success',
            $request->boolean('actualizar_precio_producto')
                ? 'Lote y precio público del producto actualizados correctamente'
                : 'Lote actualizado; se conservó el precio público del producto'
        );
}
    

public function ajustarStock(Request $request, Lote $lote)
{
    $request->validate([
        'tipo'     => 'required|in:sumar,restar',
        'cantidad' => 'required|integer|min:1',
        'motivo'   => 'required|string|max:255',
    ]);

    $stockAntes = $lote->stock_actual;

    if ($request->tipo === 'restar') {
        if ($request->cantidad > $stockAntes) {
            return response()->json([
                'message' => 'No puedes restar más stock del disponible'
            ], 422);
        }
        $nuevoStock = $stockAntes - $request->cantidad;
    } else {
        $nuevoStock = $stockAntes + $request->cantidad;
    }

    // Actualizar stock
    $lote->update([
        'stock_actual' => $nuevoStock
    ]);

    // Registrar movimiento de AJUSTE
    LoteMovimiento::create([
        'lote_id'       => $lote->id,
        'usuario_id'    => auth()->id(),
        'tipo'          => 'ajuste',
        'cantidad'      => $request->cantidad,
        'stock_antes'   => $stockAntes,
        'stock_despues' => $nuevoStock,
        'motivo'        => $request->motivo,
        'creado_en'     => now(),
    ]);

    return response()->json([
        'message' => 'Ajuste aplicado correctamente',
        'stock'   => $nuevoStock
    ]);
}

public function movimientos(Lote $lote)
{
    $movimientos = $lote->movimientos()
        ->with('usuario')
        ->orderBy('creado_en', 'desc')
        ->paginate(10);

    return view('inventario.lote_movimientos', compact('lote', 'movimientos'));
}


}
