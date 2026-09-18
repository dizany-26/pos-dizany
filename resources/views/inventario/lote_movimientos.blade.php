@extends('layouts.app')

{{-- BOTÓN ATRÁS --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Movimientos del lote
@endsection

{{-- ACCIONES HEADER --}}
@section('header-buttons')
<a href="{{ route('inventario.lotes') }}" class="btn-gasto">
    <i class="fas fa-layer-group"></i>
    <span class="btn-text">Volver a lotes</span>
</a>
@endsection

@section('content')
<div class="container-fluid px-3 mt-4">

    {{-- INFO DEL LOTE --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-4">
                    <strong>Producto</strong><br>
                    <span class="fw-semibold">
                        {{ $lote->producto->nombre }}
                    </span><br>
                    <small class="text-muted">
                        {{ $lote->producto->descripcion }}
                    </small>
                </div>

                <div class="col-md-4">
                    <strong>Cod. Lote</strong><br>
                    {{ $lote->codigo_comprobante ?? '—' }}
                </div>

                <div class="col-md-4">
                    <strong>Stock actual</strong><br>
                    <span class="fw-semibold">
                        {{ $lote->stock_actual }} / {{ $lote->stock_inicial }}
                    </span>
                </div>

            </div>
        </div>
    </div>

    {{-- TABLA DE MOVIMIENTOS --}}
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-history me-2"></i>
            Historial de movimientos
        </div>

        <div class="table-responsive lote-movimientos-scroll">
            <table class="table table-hover align-middle mb-0 lote-movimientos-table">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">Stock antes</th>
                        <th class="text-center">Stock después</th>
                        <th>Motivo</th>
                        <th>Usuario</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($movimientos as $m)

                        @php
                            $icon = match($m->tipo) {
                                'ingreso' => 'fa-arrow-down',
                                'venta'   => 'fa-shopping-cart',
                                'ajuste'  => 'fa-sliders-h',
                                'edicion' => 'fa-pen',
                                default   => 'fa-circle'
                            };

                            $badge = match($m->tipo) {
                                'ingreso' => 'success',
                                'venta'   => 'primary',
                                'ajuste'  => 'warning',
                                'edicion' => 'secondary',
                                default   => 'dark'
                            };
                        @endphp

                        <tr class="lote-movimiento-card">
                            <td data-label="Fecha">
                                {{ \Carbon\Carbon::parse($m->creado_en)->format('d/m/Y H:i') }}
                            </td>

                            <td data-label="Tipo">
                                <span class="badge bg-{{ $badge }}">
                                    <i class="fas {{ $icon }} me-1"></i>
                                    {{ ucfirst($m->tipo) }}
                                </span>
                            </td>

                            <td data-label="Cantidad" class="text-center fw-semibold">
                                @if($m->cantidad > 0)
                                    <span class="text-success">+{{ $m->cantidad }}</span>
                                @elseif($m->cantidad < 0)
                                    <span class="text-danger">{{ $m->cantidad }}</span>
                                @else
                                    —
                                @endif
                            </td>

                            <td data-label="Stock antes" class="text-center">
                                {{ $m->stock_antes }}
                            </td>

                            <td data-label="Stock después" class="text-center fw-bold">
                                {{ $m->stock_despues }}
                            </td>

                            <td data-label="Motivo">
                                {{ $m->motivo ?? '—' }}
                            </td>

                            <td data-label="Usuario">
                                {{ $m->usuario->nombre ?? 'Sistema' }}
                            </td>
                        </tr>

                    @empty
                        <tr class="lote-movimiento-empty">
                            <td colspan="7" class="text-center py-4 text-muted">
                                No hay movimientos registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        {{-- PAGINACIÓN --}}
        <div class="card-footer">
            {{ $movimientos->links() }}
        </div>

    </div>

</div>
@endsection

@push('styles')
<style>
@media (max-width: 767.98px) {
    .lote-movimientos-scroll {
        overflow: visible;
        padding: .75rem;
    }

    .lote-movimientos-table,
    .lote-movimientos-table tbody {
        display: block;
        width: 100%;
    }

    .lote-movimientos-table thead {
        display: none;
    }

    .lote-movimientos-table .lote-movimiento-card {
        display: block;
        margin-bottom: .85rem;
        padding: .75rem 1rem;
        border: 1px solid var(--border-color, #dbe5f0);
        border-radius: 14px;
        background: var(--card-bg, #fff);
        box-shadow: 0 6px 18px rgba(15, 42, 76, .07);
    }

    .lote-movimientos-table .lote-movimiento-card:last-child {
        margin-bottom: 0;
    }

    .lote-movimientos-table .lote-movimiento-card td {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        width: 100%;
        padding: .65rem 0;
        border: 0;
        border-bottom: 1px dashed var(--border-color, #e4ebf3);
        text-align: right !important;
        overflow-wrap: anywhere;
    }

    .lote-movimientos-table .lote-movimiento-card td:last-child {
        border-bottom: 0;
    }

    .lote-movimientos-table .lote-movimiento-card td::before {
        content: attr(data-label);
        flex: 0 0 42%;
        color: var(--muted-color, #64748b);
        font-size: .75rem;
        font-weight: 700;
        text-align: left;
    }

    .lote-movimientos-table .lote-movimiento-empty {
        display: block;
    }

    .lote-movimientos-table .lote-movimiento-empty td {
        display: block;
        width: 100%;
    }

    :root[data-theme='dark'] .lote-movimientos-table .lote-movimiento-card {
        border-color: #2f4c70;
        background: #102039;
        box-shadow: none;
    }

    :root[data-theme='dark'] .lote-movimientos-table .lote-movimiento-card td {
        border-bottom-color: #2b4362;
    }

    :root[data-theme='dark'] .lote-movimientos-table .lote-movimiento-card td::before {
        color: #a9bdd8;
    }
}
</style>
@endpush
