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
    $productosSinStock = Producto::whereDoesntHave('lotes', function ($q) {
        $q->where('stock_actual', '>', 0);
    })->count();


    // 🟡 Productos con stock bajo (sumando todos los lotes)
    $productosStockBajo = Producto::withSum('lotes as stock_total', 'stock_actual')
        ->get()
        ->filter(function ($producto) {
            return ($producto->stock_total ?? 0) <= 10;
        });


    // ⚠️ Lotes próximos a vencer (30 días)
    $lotesPorVencer = Lote::whereNotNull('fecha_vencimiento')
        ->whereDate('fecha_vencimiento', '<=', now()->addDays(30))
        ->where('stock_actual', '>', 0)
        ->with('producto')
        ->get();


    // 📦 Total unidades en almacén
    $totalUnidades = Lote::sum('stock_actual');


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

    return view('inventario.lote', compact('productos', 'proveedores'));
}


public function storeLote(Request $request)
{
    $request->validate([
        'producto_id'       => 'required|exists:productos,id',
        'proveedor_id'      => 'nullable|exists:proveedores,id',
        'codigo_comprobante'       => 'nullable|string|max:100', // 👈 AÑADIDO
        'stock_inicial'     => 'required|integer|min:1',
        'precio_compra'     => 'required|numeric|min:0',
        'precio_unidad'     => 'required|numeric|min:0',
        'precio_paquete'    => 'nullable|numeric|min:0',
        'precio_caja'       => 'nullable|numeric|min:0',
        'actualizar_precio_producto' => 'nullable|boolean',
        'fecha_ingreso'     => 'required|date',
        'fecha_vencimiento' => 'nullable|date|after_or_equal:fecha_ingreso',
    ]);

        DB::transaction(function () use ($request) {

        $producto = Producto::whereKey($request->producto_id)
            ->lockForUpdate()
            ->firstOrFail();

        $debeActualizarPrecio = $request->boolean('actualizar_precio_producto')
            || $producto->precio_venta === null;

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

        Lote::create([
            'producto_id'       => $request->producto_id,
            'proveedor_id'      => $request->proveedor_id,
            'numero_lote'       => $numeroLote, // 👈 AQUÍ
            'codigo_comprobante'=> $request->codigo_comprobante,
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
    });
        return redirect()
            ->route('inventario.lote')
            ->with('success', 'Lote registrado correctamente');
    
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
