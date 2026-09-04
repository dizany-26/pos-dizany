<?php

namespace App\Http\Controllers;

use App\Models\PedidoCatalogo;
use App\Models\Producto;
use App\Models\User;
use App\Notifications\CajaNotification;
use App\Services\SaleLineCalculator;
use App\Services\Tax\TaxProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PedidoCatalogoController extends Controller
{
    public function store(Request $request, SaleLineCalculator $calculator)
    {
        $data = $request->validate([
            'cliente.nombre' => 'required|string|max:160',
            'cliente.telefono' => 'required|string|max:30',
            'cliente.entrega' => 'required|in:tienda,domicilio',
            'cliente.direccion' => 'nullable|required_if:cliente.entrega,domicilio|string|max:500',
            'items' => 'required|array|min:1|max:100',
            'items.*.producto_id' => 'required|integer|distinct|exists:productos,id',
            'items.*.presentacion' => 'required|in:unidad,paquete,caja',
            'items.*.cantidad' => 'required|integer|min:1|max:999',
        ]);

        $taxProfile = app(TaxProfileService::class)->current();
        $igv = $taxProfile?->default_tax_treatment === 'gravada'
            ? max(0, (float) $taxProfile->igv_rate)
            : 0;

        $savedItems = [];
        $total = 0;

        foreach ($data['items'] as $requestedItem) {
            $product = Producto::whereKey($requestedItem['producto_id'])
                ->where('activo', 1)
                ->where('visible_en_catalogo', 1)
                ->firstOrFail();
            $lots = $product->lotes()
                ->where('activo', 1)
                ->where('stock_actual', '>', 0)
                ->orderByRaw('fecha_vencimiento IS NULL')
                ->orderBy('fecha_vencimiento')
                ->orderBy('fecha_ingreso')
                ->orderBy('id')
                ->get();
            try {
                $calculation = $calculator->calculate(
                    $product,
                    $lots,
                    (int) $requestedItem['cantidad'],
                    $requestedItem['presentacion']
                );
            } catch (\Throwable $exception) {
                $message = str_starts_with($exception->getMessage(), 'STOCK|')
                    ? "No hay stock suficiente de {$product->nombre}."
                    : $exception->getMessage();
                throw ValidationException::withMessages(['items' => $message]);
            }
            $lineTotal = round($calculation['subtotal'] * (1 + $igv / 100), 2);
            $total += $lineTotal;
            $savedItems[] = [
                'producto_id' => $product->id,
                'nombre' => $product->nombre,
                'presentacion' => $requestedItem['presentacion'],
                'cantidad' => (int) $requestedItem['cantidad'],
                'factor' => $calculation['units_per_presentation'],
                'subtotal' => $lineTotal,
            ];
        }

        $order = DB::transaction(function () use ($data, $savedItems, $total) {
            do {
                $code = 'DIZ-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
            } while (PedidoCatalogo::where('codigo', $code)->exists());

            return PedidoCatalogo::create([
                'codigo' => $code,
                'cliente_nombre' => trim($data['cliente']['nombre']),
                'cliente_telefono' => trim($data['cliente']['telefono']),
                'tipo_entrega' => $data['cliente']['entrega'],
                'direccion' => $data['cliente']['entrega'] === 'domicilio'
                    ? trim($data['cliente']['direccion'])
                    : null,
                'items' => $savedItems,
                'total' => round($total, 2),
                'estado' => 'pendiente',
                'enviado_en' => now(),
            ]);
        });

        try {
            User::where('rol_id', 1)
                ->orWhereHas('permisos', fn ($query) => $query->where('permiso', 'ventas'))
                ->get()
                ->each
                ->notify(new CajaNotification([
                    'titulo' => 'Nuevo pedido del catálogo',
                    'mensaje' => "{$order->cliente_nombre} envió el pedido {$order->codigo}.",
                    'icono' => 'fa-shopping-cart',
                    'color' => 'primary',
                    'url' => route('ventas.index', [], false),
                ]));
        } catch (\Throwable $exception) {
            Log::warning('El pedido se guardó, pero no se pudo emitir su notificación.', [
                'pedido_catalogo_id' => $order->id,
                'exception' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'pedido' => ['id' => $order->id, 'codigo' => $order->codigo],
        ], 201);
    }

    public function pending()
    {
        return PedidoCatalogo::where('estado', 'pendiente')
            ->latest('enviado_en')
            ->get()
            ->map(fn (PedidoCatalogo $order) => [
                'id' => $order->id,
                'codigo' => $order->codigo,
                'cliente_nombre' => $order->cliente_nombre,
                'cliente_telefono' => $order->cliente_telefono,
                'tipo_entrega' => $order->tipo_entrega,
                'direccion' => $order->direccion,
                'items' => $order->items,
                'total' => (float) $order->total,
                'enviado_en' => optional($order->enviado_en)->toIso8601String(),
            ])->values();
    }

    public function cancel(PedidoCatalogo $pedido)
    {
        $cancelled = DB::transaction(function () use ($pedido) {
            $lockedOrder = PedidoCatalogo::whereKey($pedido->id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->estado !== 'pendiente') return false;

            $lockedOrder->update([
                'estado' => 'cancelado',
                'atendido_en' => now(),
            ]);
            return true;
        });

        if (! $cancelled) {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido ya fue atendido o cancelado.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pedido del catálogo cancelado.',
        ]);
    }
}
