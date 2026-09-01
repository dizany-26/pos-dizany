<table>
    <tr>
        <td></td>
        <td colspan="7"><strong>{{ $config?->nombre_empresa ?: 'DIZANY' }} · REPORTE DE MOVIMIENTOS</strong></td>
    </tr>
    <tr>
        <td></td>
        <td colspan="7">{{ $filtroDescripcion }} · Generado {{ now()->format('d/m/Y h:i A') }}</td>
    </tr>
    <tr><td colspan="8"></td></tr>
    <tr>
        <td><strong>REGISTROS</strong></td><td>{{ $movimientos->count() }}</td>
        <td><strong>INGRESOS</strong></td><td>{{ $ingresos }}</td>
        <td><strong>EGRESOS</strong></td><td>{{ $egresos }}</td>
        <td><strong>BALANCE</strong></td><td>{{ $balance }}</td>
    </tr>
    <tr>
        <td colspan="8">Vista: {{ ucfirst(str_replace('_', ' ', $tab)) }}@if($metodo) · Método: {{ ucfirst($metodo) }}@endif @if($buscar) · Búsqueda: {{ $buscar }}@endif</td>
    </tr>
    <tr><td colspan="8"></td></tr>
    <thead>
        <tr>
            <th>Fecha</th><th>Hora</th><th>Concepto</th><th>Método</th><th>Ingreso</th><th>Egreso</th><th>Estado</th><th>Responsable</th>
        </tr>
    </thead>
    <tbody>
        @forelse($movimientos as $movimiento)
            <tr>
                <td>{{ $movimiento->fecha?->format('d/m/Y') }}</td>
                <td>{{ $movimiento->created_at?->format('h:i A') }}</td>
                <td>{{ $movimiento->concepto }}</td>
                <td>
                    {{ ucfirst($movimiento->metodo_pago ?: '-') }}
                    @if($movimiento->metodo_pago === 'mixto' && $movimiento->venta?->pagos)
                        ({{ $movimiento->venta->pagos->map(fn($p) => ucfirst($p->metodo_pago) . ' S/ ' . number_format($p->monto, 2))->join(' + ') }})
                    @endif
                </td>
                <td>{{ $movimiento->tipo === 'ingreso' ? (float)$movimiento->monto : null }}</td>
                <td>{{ $movimiento->tipo === 'egreso' ? (float)$movimiento->monto : null }}</td>
                <td>{{ ucfirst($movimiento->estado) }}</td>
                <td>{{ $movimiento->usuario?->nombre ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="8">No hay movimientos para los filtros seleccionados.</td></tr>
        @endforelse
    </tbody>
</table>
