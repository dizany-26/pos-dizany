@extends('layouts.app')

@section('header-back')
<button class="btn-header-back" type="button" onclick="history.back()"><i class="fas fa-chevron-left"></i></button>
@endsection

@section('header-title', 'Reportes')

@section('header-buttons')
<a class="report-header-action btn-movimientos-outline" href="{{ route('reportes.exportar', array_merge(['formato' => 'pdf'], request()->query())) }}"><i class="fas fa-file-pdf"></i><span>Exportar PDF</span></a>
<a class="report-header-action btn-movimientos-outline" href="{{ route('reportes.exportar', array_merge(['formato' => 'csv'], request()->query())) }}"><i class="fas fa-file-excel"></i><span>Exportar Excel</span></a>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="{{ asset('css/calendar-theme.css') }}?v={{ filemtime(public_path('css/calendar-theme.css')) }}">
<link rel="stylesheet" href="{{ asset('css/reportes.css') }}?v={{ filemtime(public_path('css/reportes.css')) }}">
@endpush

@section('content')
@php
    $money = fn ($value) => 'S/ '.number_format((float) $value, 2);
    $methodClass = fn ($value) => 'method-'.strtolower(str_replace([' ', 'é'], ['-', 'e'], (string) $value));
    $maxFlujo = max(1, (float) $flujo->max(fn ($d) => max($d['ventas'], $d['gastos'])));
@endphp

<main class="reports-page">
    <section class="reports-hero">
        <div>
            <span class="reports-eyebrow">INTELIGENCIA DEL NEGOCIO</span>
            <h1>Centro de reportes</h1>
            <p>{{ $filtros['es_admin'] ? 'Ventas, rentabilidad, caja e inventario en una sola vista.' : 'Consulta tus ventas y el resumen de tu caja en una sola vista.' }}</p>
        </div>
        <div class="reports-period"><i class="far fa-calendar-alt"></i><span><small>Periodo analizado</small><strong>{{ \Carbon\Carbon::parse($filtros['desde'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}</strong></span></div>
    </section>

    <form class="reports-filters" method="GET" action="{{ route('reportes.index') }}" id="reportFilters">
        <label><span>Desde</span><input type="text" name="desde" value="{{ $filtros['desde'] }}" data-report-date></label>
        <label><span>Hasta</span><input type="text" name="hasta" value="{{ $filtros['hasta'] }}" data-report-date></label>
        @if($filtros['es_admin'])
            <label><span>Responsable</span><select name="usuario_id"><option value="">Todos</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected($filtros['usuario_id'] === $usuario->id)>{{ $usuario->nombre }}</option>@endforeach</select></label>
        @endif
        <label><span>Método</span><select name="metodo"><option value="">Todos</option>@foreach($metodos as $key => $nombre)<option value="{{ $key }}" @selected($filtros['metodo'] === $key)>{{ $nombre }}</option>@endforeach</select></label>
        <label><span>Estado</span><select name="estado"><option value="">Vigentes</option><option value="pagado" @selected($filtros['estado'] === 'pagado')>Pagadas</option><option value="pendiente" @selected($filtros['estado'] === 'pendiente')>Pendientes</option><option value="anulado" @selected($filtros['estado'] === 'anulado')>Anuladas</option></select></label>
        <button type="submit" class="reports-filter-btn"><i class="fas fa-filter"></i> Aplicar</button>
        <a href="{{ route('reportes.index') }}" class="reports-clear" title="Limpiar filtros"><i class="fas fa-rotate-left"></i></a>
    </form>

    <section class="reports-kpis">
        <article class="report-kpi kpi-sales"><span class="kpi-icon"><i class="fas fa-cash-register"></i></span><div><small>Ventas</small><strong>{{ $money($kpis['ventasTotal']) }}</strong><em>{{ $kpis['operaciones'] }} operaciones</em></div></article>
        @if($filtros['es_admin'])
            <article class="report-kpi kpi-cost"><span class="kpi-icon"><i class="fas fa-boxes-stacked"></i></span><div><small>Costo vendido</small><strong>{{ $money($kpis['costoVentas']) }}</strong><em>Mercadería despachada</em></div></article>
            <article class="report-kpi kpi-profit"><span class="kpi-icon"><i class="fas fa-chart-line"></i></span><div><small>Utilidad bruta</small><strong>{{ $money($kpis['utilidadBruta']) }}</strong><em>Antes de gastos</em></div></article>
            <article class="report-kpi kpi-expense"><span class="kpi-icon"><i class="fas fa-receipt"></i></span><div><small>Gastos</small><strong>{{ $money($kpis['gastos']) }}</strong><em>Gastos operativos</em></div></article>
            <article class="report-kpi kpi-net"><span class="kpi-icon"><i class="fas fa-sack-dollar"></i></span><div><small>Utilidad neta</small><strong>{{ $money($kpis['utilidadNeta']) }}</strong><em>Bruta menos gastos</em></div></article>
        @endif
        <article class="report-kpi kpi-ticket"><span class="kpi-icon"><i class="fas fa-ticket"></i></span><div><small>Ticket promedio</small><strong>{{ $money($kpis['ticketPromedio']) }}</strong><em>Promedio por venta</em></div></article>
    </section>

    <section class="reports-overview-grid">
        <article class="report-panel cashflow-panel">
            <div class="report-panel-heading"><div><span class="panel-kicker">TENDENCIA</span><h2>Flujo del periodo</h2></div><div class="chart-legend"><span><i class="legend-sales"></i>Ventas</span>@if($filtros['es_admin'])<span><i class="legend-expenses"></i>Gastos</span>@endif</div></div>
            <div class="flow-chart">
                @forelse($flujo as $day)
                    <div class="flow-day" title="{{ $day['dia'] }} · Ventas {{ $money($day['ventas']) }}{{ $filtros['es_admin'] ? ' · Gastos '.$money($day['gastos']) : '' }}">
                        <div class="flow-bars"><i class="flow-sale" style="height: {{ max(2, ($day['ventas'] / $maxFlujo) * 100) }}%"></i>@if($filtros['es_admin'])<i class="flow-expense" style="height: {{ max(2, ($day['gastos'] / $maxFlujo) * 100) }}%"></i>@endif</div><small>{{ $day['dia'] }}</small>
                    </div>
                @empty <div class="report-empty"><i class="fas fa-chart-column"></i><p>Sin datos en el periodo.</p></div>@endforelse
            </div>
        </article>
        <article class="report-panel payment-panel">
            <div class="report-panel-heading"><div><span class="panel-kicker">COBROS</span><h2>Métodos de pago</h2></div></div>
            <div class="payment-list">
                @forelse($metodosPago as $metodo)
                    @php($percent = $kpis['ventasTotal'] > 0 ? ($metodo->total / $kpis['ventasTotal']) * 100 : 0)
                    <div class="payment-row"><div><span class="payment-name {{ $methodClass($metodo->metodo) }}">{{ ucfirst($metodo->metodo) }}</span><strong>{{ $money($metodo->total) }}</strong></div><div class="payment-track"><i style="width: {{ min(100, $percent) }}%"></i></div><small>{{ $metodo->operaciones }} operaciones · {{ number_format($percent, 1) }}%</small></div>
                @empty <div class="report-empty compact"><i class="fas fa-wallet"></i><p>Sin cobros registrados.</p></div>@endforelse
            </div>
        </article>
    </section>

    <nav class="reports-tabs" aria-label="Módulos del reporte">
        @php($reportTabs = $filtros['es_admin']
            ? ['ventas' => ['fa-cart-shopping','Ventas'], 'productos' => ['fa-box-open','Productos'], 'caja' => ['fa-cash-register','Caja'], 'creditos' => ['fa-hand-holding-dollar','Créditos'], 'inventario' => ['fa-warehouse','Inventario'], 'compras' => ['fa-truck-ramp-box','Compras'], 'gastos' => ['fa-receipt','Gastos'], 'clientes' => ['fa-users','Clientes']]
            : ['ventas' => ['fa-cart-shopping','Mis ventas'], 'caja' => ['fa-cash-register','Mi caja']])
        @foreach($reportTabs as $tab => [$icon, $label])
            <button type="button" data-report-tab="{{ $tab }}" @class(['active' => $loop->first])><i class="fas {{ $icon }}"></i><span>{{ $label }}</span></button>
        @endforeach
    </nav>

    <section class="report-tab-pane active" data-report-pane="ventas">
        @include('reportes.partials.table', ['title' => 'Detalle de ventas', 'icon' => 'fa-cart-shopping', 'headers' => ['Fecha','Comprobante','Cliente','Responsable','Método','Estado','Total'], 'rows' => $ventasDetalle, 'type' => 'ventas'])
    </section>
    @if($filtros['es_admin'])
    <section class="report-tab-pane" data-report-pane="productos">
        @include('reportes.partials.table', ['title' => 'Rendimiento de productos', 'icon' => 'fa-box-open', 'headers' => ['Producto','Categoría','Unidades','Ventas','Utilidad','Margen'], 'rows' => $productosVendidos, 'type' => 'productos'])
    </section>
    @endif
    <section class="report-tab-pane" data-report-pane="caja">
        @include('reportes.partials.table', ['title' => $filtros['es_admin'] ? 'Historial de cierres de caja' : 'Mis cierres de caja', 'icon' => 'fa-cash-register', 'headers' => ['Cajero','Apertura','Cierre','Inicial','Esperado','Contado','Diferencia','Estado'], 'rows' => $cajas, 'type' => 'caja'])
    </section>
    @if($filtros['es_admin'])
    <section class="report-tab-pane" data-report-pane="creditos">
        <div class="tab-summary"><div><small>Saldo por cobrar</small><strong>{{ $money($kpis['porCobrar']) }}</strong></div><p>Ventas pendientes dentro del periodo seleccionado.</p></div>
        @include('reportes.partials.table', ['title' => 'Cuentas por cobrar', 'icon' => 'fa-hand-holding-dollar', 'headers' => ['Cliente','Compras','Consumo','Última compra','Deuda'], 'rows' => $clientes->where('deuda', '>', 0), 'type' => 'creditos'])
    </section>
    <section class="report-tab-pane" data-report-pane="inventario">
        <nav class="inventory-subtabs" aria-label="Secciones de inventario">
            <button type="button" class="active" data-inventory-tab="stock"><i class="fas fa-triangle-exclamation"></i> Stock crítico <span>{{ count($stockBajo) }}</span></button>
            <button type="button" data-inventory-tab="vencimientos"><i class="fas fa-calendar-days"></i> Próximos vencimientos <span>{{ count($vencimientos) }}</span></button>
        </nav>
        <div class="inventory-subpane active" data-inventory-pane="stock">
            @include('reportes.partials.table', ['title' => 'Stock crítico', 'icon' => 'fa-triangle-exclamation', 'headers' => ['Producto','Categoría','Stock','Mínimo','Estado'], 'rows' => $stockBajo, 'type' => 'inventario'])
        </div>
        <div class="inventory-subpane" data-inventory-pane="vencimientos">
            @include('reportes.partials.table', ['title' => 'Próximos vencimientos (60 días)', 'icon' => 'fa-calendar-days', 'headers' => ['Lote','Producto','Stock','Vencimiento','Días'], 'rows' => $vencimientos, 'type' => 'vencimientos'])
        </div>
    </section>
    <section class="report-tab-pane" data-report-pane="compras">
        <div class="tab-summary"><div><small>Compras registradas</small><strong>{{ $money($kpis['comprasTotal']) }}</strong></div><p>Ingreso de mercadería del periodo.</p></div>
        @include('reportes.partials.table', ['title' => 'Compras por comprobante', 'icon' => 'fa-truck-ramp-box', 'headers' => ['Fecha','Comprobante','Proveedor','Productos','Total','Pagado','Saldo','Estado'], 'rows' => $compras, 'type' => 'compras'])
    </section>
    <section class="report-tab-pane" data-report-pane="gastos">
        @include('reportes.partials.table', ['title' => 'Gastos operativos', 'icon' => 'fa-receipt', 'headers' => ['Fecha','Descripción','Método','Responsable','Monto'], 'rows' => $gastosDetalle, 'type' => 'gastos'])
    </section>
    <section class="report-tab-pane" data-report-pane="clientes">
        @include('reportes.partials.table', ['title' => 'Comportamiento de clientes', 'icon' => 'fa-users', 'headers' => ['Cliente','Compras','Consumo','Última compra','Deuda'], 'rows' => $clientes, 'type' => 'clientes'])
    </section>
    @endif
</main>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="{{ asset('js/reportes.js') }}?v={{ filemtime(public_path('js/reportes.js')) }}"></script>
@endpush
