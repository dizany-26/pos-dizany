<?php

namespace App\Http\Controllers;

use App\Models\Devolucion;
use App\Models\DevolucionDetalle;
use App\Models\Venta;
use App\Services\SaleReturnService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DevolucionController extends Controller
{
    public function searchSale(Request $request)
    {
        abort_unless($request->user()->esAdmin(), 403);
        $data = $request->validate([
            'venta_id' => ['nullable', 'integer', 'exists:ventas,id', 'required_without:q'],
            'q' => ['nullable', 'string', 'max:80', 'required_without:venta_id'],
        ]);

        $query = Venta::query()->with(['cliente', 'pagos', 'detalleVentas.producto', 'electronicDocument', 'manualTaxDocument']);
        if (! empty($data['venta_id'])) {
            $query->whereKey($data['venta_id']);
        } else {
            $search = trim($data['q']);
            $numeric = ctype_digit($search) ? (int) ltrim($search, '0') : null;
            $query->where(function ($q) use ($search, $numeric) {
                $q->whereRaw("CONCAT(serie, '-', LPAD(correlativo, 6, '0')) LIKE ?", ["%{$search}%"])
                    ->orWhere('serie', 'like', "%{$search}%");
                if ($numeric !== null) $q->orWhere('correlativo', $numeric);
            });
        }

        $sale = $query->latest('fecha')->firstOrFail();
        abort_if(! $sale->activo || $sale->estado === 'anulada', 422, 'La venta está anulada o inactiva.');
        $saleBase = (float) $sale->detalleVentas->sum('subtotal');
        $grossFactor = $saleBase > 0 ? (float) $sale->total / $saleBase : 1.0;
        $lines = $sale->detalleVentas->map(function ($detail) use ($grossFactor) {
            $returned = (int) DevolucionDetalle::query()
                ->where('detalle_venta_id', $detail->id)
                ->whereHas('devolucion', fn ($q) => $q->where('estado', 'registrada'))
                ->sum('cantidad');
            return [
                'id' => $detail->id,
                'producto' => $detail->producto?->nombre ?? 'Producto',
                'presentacion' => $detail->presentacion,
                'vendida' => (int) $detail->cantidad,
                'devuelta' => $returned,
                'disponible' => max(0, (int) $detail->cantidad - $returned),
                'precio_devolucion' => round(
                    (float) $detail->subtotal * $grossFactor / max(1, (int) $detail->cantidad),
                    2
                ),
                'importe_linea_devolucion' => round((float) $detail->subtotal * $grossFactor, 4),
            ];
        })->values();

        return response()->json([
            'id' => $sale->id,
            'comprobante' => $sale->serie.'-'.str_pad((string) $sale->correlativo, 6, '0', STR_PAD_LEFT),
            'fecha' => optional($sale->fecha)->format('d/m/Y H:i'),
            'cliente' => $sale->cliente?->nombre ?? 'Público general',
            'total' => (float) $sale->total,
            'pagado' => (float) $sale->pagos->sum('monto'),
            'saldo' => (float) $sale->saldo,
            'requiere_nota_credito' => in_array($sale->electronicDocument?->status, ['accepted', 'observed'], true)
                || $sale->manualTaxDocument !== null,
            'detalles' => $lines,
            'puede_devolver' => $lines->sum('disponible') > 0,
        ]);
    }

    public function store(Request $request, SaleReturnService $service)
    {
        abort_unless($request->user()->esAdmin(), 403);
        $data = $request->validate([
            'venta_id' => ['required', 'integer', 'exists:ventas,id'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.detalle_venta_id' => ['required', 'integer', 'distinct', 'exists:detalle_ventas,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.reintegra_stock' => ['required', 'boolean'],
            'metodo_reembolso' => ['nullable', Rule::in(array_keys(\App\Models\Caja::mediosConciliables()))],
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $return = $service->register(Venta::findOrFail($data['venta_id']), $data, $request->user()->id);

        return response()->json([
            'message' => "Devolución {$return->codigo} registrada correctamente.",
            'devolucion' => ['id' => $return->id, 'codigo' => $return->codigo],
        ], 201);
    }

    public function show(Request $request, Devolucion $devolucion)
    {
        abort_unless($request->user()->esAdmin(), 403);
        return response()->json($devolucion->load(['venta.cliente', 'usuario', 'detalles.detalleVenta.producto']));
    }
}
