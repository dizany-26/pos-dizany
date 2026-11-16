<div class="return-summary-grid">
    <div class="return-summary-card"><span>Devoluciones</span><strong>{{ $resumenDevoluciones['operaciones'] }}</strong></div>
    <div class="return-summary-card"><span>Productos devueltos</span><strong>{{ $resumenDevoluciones['productos'] }}</strong></div>
    <div class="return-summary-card"><span>Importe devuelto</span><strong>S/ {{ number_format($resumenDevoluciones['importe'], 2) }}</strong></div>
    <div class="return-summary-card is-refund"><span>Dinero reembolsado</span><strong>S/ {{ number_format($resumenDevoluciones['reembolsado'], 2) }}</strong></div>
</div>
<div class="return-toolbar">
    <div><h4 class="mb-1">Historial de devoluciones</h4><small class="text-muted">Operaciones vinculadas a su venta original.</small></div>
    <button type="button" class="btn-soft btn-soft-primary js-open-return"><i class="fas fa-rotate-left"></i> Nueva devolución</button>
</div>
<div class="table-responsive ui-scroll">
    <table class="table ui-table align-middle mb-0">
        <thead><tr><th>Fecha</th><th>Devolución</th><th>Venta original</th><th>Productos</th><th>Responsable</th><th class="text-end">Importe</th><th class="text-end">Reembolso</th><th>Estado fiscal</th></tr></thead>
        <tbody>
        @forelse($devoluciones as $devolucion)
            <tr>
                <td>{{ $devolucion->fecha->format('d/m/Y') }}<small class="d-block text-muted">{{ $devolucion->fecha->format('h:i A') }}</small></td>
                <td><strong>{{ $devolucion->codigo }}</strong><small class="d-block text-muted">{{ $devolucion->motivo }}</small></td>
                <td>{{ $devolucion->venta->serie }}-{{ str_pad((string)$devolucion->venta->correlativo, 6, '0', STR_PAD_LEFT) }}<small class="d-block text-muted">{{ $devolucion->venta->cliente?->nombre ?? 'Público general' }}</small></td>
                <td>@foreach($devolucion->detalles as $detalle)<span class="d-block">{{ $detalle->cantidad }} × {{ $detalle->detalleVenta?->producto?->nombre ?? 'Producto' }}</span>@endforeach</td>
                <td>{{ $devolucion->usuario?->nombre ?? '—' }}</td>
                <td class="text-end fw-bold">S/ {{ number_format($devolucion->importe_total, 2) }}</td>
                <td class="text-end text-danger fw-bold">S/ {{ number_format($devolucion->monto_reembolsado, 2) }}
                    @if($devolucion->metodo_reembolso)<small class="d-block text-muted fw-normal">{{ ucfirst($devolucion->metodo_reembolso) }}</small>@endif
                    @if($devolucion->reduccion_saldo > 0)<small class="d-block text-warning fw-normal">Saldo reducido: S/ {{ number_format($devolucion->reduccion_saldo, 2) }}</small>@endif
                </td>
                <td>@if($devolucion->requiere_nota_credito)<span class="ui-badge ui-badge-warning">Requiere nota de crédito</span>@else<span class="ui-badge ui-badge-success">Registrada</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-5">Todavía no hay devoluciones en este periodo.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if($devoluciones instanceof \Illuminate\Pagination\LengthAwarePaginator)<div class="card-footer d-flex justify-content-end">{{ $devoluciones->appends(request()->query())->links() }}</div>@endif
