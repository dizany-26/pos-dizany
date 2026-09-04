@extends('layouts.app')

@section('header-back')
<button class="btn-header-back" onclick="history.back()"><i class="fas fa-arrow-left"></i></button>
@endsection
@section('header-title', 'Historial de compras')

@section('header-actions')
<a href="{{ route('inventario.lotes') }}" class="btn-soft btn-soft-info">
    <i class="fas fa-layer-group"></i><span>Lotes registrados</span>
</a>
<a href="{{ route('inventario.lote') }}" class="btn-soft btn-soft-success">
    <i class="fas fa-plus"></i><span>Nuevo ingreso</span>
</a>
@endsection

@section('content')
<div class="card ui-card container-card my-4">
    <div class="card-header text-center pt-4">
        <h4 class="mb-1 fw-semibold"><i class="fas fa-receipt me-2 text-primary"></i>Historial de compras</h4>
        <p class="text-muted mb-0 small">Consulta compras pagadas, pendientes y con pagos parciales.</p>
    </div>
    <div class="card-body p-3 p-md-4">
        <form method="GET" id="filtrosHistorialCompras" class="row g-2 mb-4 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small">Estado de pago</label>
                <select name="estado" class="form-select ui-input">
                    <option value="">Todos los estados</option>
                    <option value="pagado" @selected(request('estado') === 'pagado')>Pagados</option>
                    <option value="pendiente" @selected(request('estado') === 'pendiente')>Pendientes</option>
                    <option value="parcial" @selected(request('estado') === 'parcial')>Pago parcial</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small">Proveedor</label>
                <select name="proveedor" class="form-select ui-input">
                    <option value="">Todos los proveedores</option>
                    @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" @selected((string) request('proveedor') === (string) $proveedor->id)>{{ $proveedor->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}" class="form-control ui-input">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-control ui-input">
            </div>
            <div class="col-10 col-md">
                <label class="form-label small">Buscar</label>
                <input name="buscar" value="{{ request('buscar') }}" class="form-control ui-input" placeholder="Producto o comprobante">
            </div>
            <div class="col-2 col-md-auto d-flex gap-1">
                <a href="{{ route('inventario.compras') }}" class="btn-soft btn-soft-info btn-soft-icon" title="Limpiar"><i class="fas fa-times"></i></a>
            </div>
        </form>

        <div class="table-responsive ui-scroll">
            <table class="table ui-table align-middle mb-0">
                <thead><tr>
                    <th>Fecha</th><th>Comprobante</th><th>N.º de lote</th><th>Proveedor</th>
                    <th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th>
                    <th class="text-center">Estado</th><th class="text-center">Detalle</th>
                </tr></thead>
                <tbody>
                @forelse($compras as $compra)
                    @php
                        $lote = $compra['principal'];
                        $total = $compra['total'];
                        $pagado = $compra['pagado'];
                        $saldo = $compra['saldo'];
                        $estado = $compra['estado'];
                    @endphp
                    <tr class="mov-row compra-history-row" style="cursor:pointer"
                        data-ref-tipo="lote" data-ref-id="{{ $lote->id }}"
                        data-mov-id=""
                        data-search="{{ strtolower(($lote->codigo_comprobante ?? '').' '.($lote->proveedor->nombre ?? '').' '.$compra['lotes']->pluck('producto.nombre')->filter()->implode(' ')) }}"
                        data-detail-url="{{ route('inventario.compras.detalle', $lote) }}">
                        <td data-label="Fecha">{{ optional($lote->fecha_ingreso)->format('d/m/Y') }}</td>
                        <td data-label="Comprobante"><strong>{{ $lote->codigo_comprobante ?: 'Sin comprobante' }}</strong><small class="d-block text-muted">{{ ucfirst(str_replace('_', ' ', $lote->tipo_comprobante ?? '')) }}</small></td>
                        <td data-label="N.º de lote"><strong>LT-{{ str_pad($lote->numero_lote, 5, '0', STR_PAD_LEFT) }}</strong><small class="d-block text-muted">{{ $compra['lotes']->count() }} productos</small></td>
                        <td data-label="Proveedor">{{ $lote->proveedor->nombre ?? 'Sin proveedor' }}</td>
                        <td data-label="Total" class="text-end fw-bold">S/ {{ number_format($total, 2) }}</td>
                        <td data-label="Pagado" class="text-end text-success">S/ {{ number_format($pagado, 2) }}</td>
                        <td data-label="Saldo" class="text-end {{ $saldo > 0 ? 'text-danger fw-bold' : '' }}">S/ {{ number_format($saldo, 2) }}</td>
                        <td data-label="Estado" class="text-center">
                            @if($estado === 'pagado')<span class="ui-badge ui-badge-success">Pagado</span>
                            @elseif($estado === 'parcial')<span class="ui-badge ui-badge-warning">Pago parcial</span>
                            @else<span class="ui-badge ui-badge-danger">Pendiente</span>@endif
                        </td>
                        <td data-label="Detalle" class="text-center"><button type="button" class="btn-soft btn-soft-primary btn-soft-icon btn-sm"><i class="fas fa-eye"></i></button></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5"><i class="fas fa-receipt d-block fs-2 mb-2"></i>No hay compras para los filtros seleccionados.</td></tr>
                @endforelse
                    <tr id="sinResultadosLocales" class="d-none"><td colspan="9" class="text-center text-muted py-4">No se encontraron coincidencias en la tabla.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end mt-3">{{ $compras->links() }}</div>
    </div>
</div>

<div class="offcanvas offcanvas-end detalle-venta-panel" tabindex="-1" id="offcanvasDetalle">
    <div class="offcanvas-header pb-2"><h5 class="offcanvas-title mb-0" id="detalleMovimientoTitulo">Detalle de la compra</h5><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
    <div class="divider-green"></div>
    <div class="offcanvas-body" id="detalleContenido"></div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/movimientos.css') }}?v={{ filemtime(public_path('css/movimientos.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/movimientos.js') }}?v={{ filemtime(public_path('js/movimientos.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('filtrosHistorialCompras');
    if (!form) return;

    form.querySelectorAll('select, input[type="date"]').forEach(control => {
        control.addEventListener('change', () => form.requestSubmit());
    });

    const search = form.querySelector('input[name="buscar"]');
    const initialSearch = search?.value ?? '';
    search?.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        form.requestSubmit();
    });
    search?.addEventListener('blur', () => {
        if (search.value !== initialSearch) form.requestSubmit();
    });
});
</script>
@endpush
