<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #18324d; font-size: 10px; }
        .header { width: 100%; border-bottom: 3px solid #123c69; padding-bottom: 10px; }
        .logo { width: 105px; max-height: 58px; object-fit: contain; }
        .company { font-size: 20px; font-weight: bold; color: #0f2a4a; }
        .title { font-size: 14px; font-weight: bold; color: #123c69; text-align: right; }
        .meta { color: #5d7186; line-height: 1.5; }
        .summary { width: 100%; margin: 14px 0; border-collapse: separate; border-spacing: 6px 0; }
        .summary td { padding: 9px; border: 1px solid #d8e4ef; background: #f3f8fc; border-radius: 6px; }
        .summary span { display: block; color: #667c91; font-size: 8px; text-transform: uppercase; }
        .summary strong { font-size: 13px; color: #123c69; }
        table.detail { width: 100%; border-collapse: collapse; }
        .detail th { padding: 7px 6px; background: #123c69; color: white; text-align: left; font-size: 9px; }
        .detail td { padding: 6px; border-bottom: 1px solid #dce5ee; }
        .detail tr:nth-child(even) td { background: #f7fafc; }
        .amount { text-align: right; white-space: nowrap; }
        .footer { margin-top: 12px; text-align: center; color: #718297; font-size: 8px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width:18%">@if($logoBase64)<img class="logo" src="{{ $logoBase64 }}">@endif</td>
            <td style="width:47%">
                <div class="company">{{ $config?->nombre_empresa ?: 'DIZANY' }}</div>
                <div class="meta">{{ $config?->direccion }}@if($config?->ruc)<br>RUC: {{ $config->ruc }}@endif</div>
            </td>
            <td style="width:35%">
                <div class="title">REPORTE DE MOVIMIENTOS</div>
                <div class="meta" style="text-align:right">{{ $filtroDescripcion }}<br>Emitido: {{ now()->format('d/m/Y h:i A') }}</div>
            </td>
        </tr>
    </table>

    <table class="summary"><tr>
        <td><span>Registros</span><strong>{{ $movimientos->count() }}</strong></td>
        <td><span>Ingresos</span><strong>S/ {{ number_format($ingresos, 2) }}</strong></td>
        <td><span>Egresos</span><strong>S/ {{ number_format($egresos, 2) }}</strong></td>
        <td><span>Balance</span><strong>S/ {{ number_format($balance, 2) }}</strong></td>
    </tr></table>

    <div class="meta" style="margin-bottom:8px">Vista: {{ ucfirst(str_replace('_', ' ', $tab)) }}@if($metodo) · Método: {{ ucfirst($metodo) }}@endif @if($buscar) · Búsqueda: {{ $buscar }}@endif</div>
    <table class="detail">
        <thead><tr><th>Fecha y hora</th><th>Concepto</th><th>Método</th><th>Estado</th><th>Responsable</th><th class="amount">Monto</th></tr></thead>
        <tbody>
        @forelse($movimientos as $movimiento)
            <tr>
                <td>{{ $movimiento->fecha?->format('d/m/Y') }} {{ $movimiento->created_at?->format('h:i A') }}</td>
                <td>{{ $movimiento->concepto }}</td>
                <td>{{ ucfirst($movimiento->metodo_pago ?: '-') }}@if($movimiento->metodo_pago === 'mixto' && $movimiento->venta?->pagos)<br><small>{{ $movimiento->venta->pagos->map(fn($p) => ucfirst($p->metodo_pago) . ' S/ ' . number_format($p->monto, 2))->join(' + ') }}</small>@endif</td>
                <td>{{ ucfirst($movimiento->estado) }}</td>
                <td>{{ $movimiento->usuario?->nombre ?? '-' }}</td>
                <td class="amount">{{ $movimiento->tipo === 'egreso' ? '-' : '+' }} S/ {{ number_format($movimiento->monto, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;padding:20px">No hay movimientos para los filtros seleccionados.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="footer">Documento generado por el sistema {{ $config?->nombre_empresa ?: 'DIZANY' }}.</div>
</body>
</html>
