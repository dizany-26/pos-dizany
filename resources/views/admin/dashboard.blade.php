@extends('layouts.app')

{{-- BOTÓN ATRÁS (opcional) --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-chevron-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Dashboard
@endsection

{{-- BOTONES DERECHA --}}
@section('header-buttons')
{{-- vacio --}}
@endsection

@section('content')
<div class="container-fluid">

    {{-- ================= KPIs ================= --}}
    <div class="row mb-4">

        <div class="col-md-3">
            <div class="kpi-card kpi-primary">
                <div class="kpi-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <small class="kpi-label">Balance operativo</small>
                <div class="kpi-value counter" data-value="{{ $balance }}">
                    S/ 0.00
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card kpi-success">
                <div class="kpi-icon">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <small class="kpi-label">Ingresos hoy</small>
                <div class="kpi-value success counter" data-value="{{ $ingresosHoy }}">
                    S/ 0.00
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card kpi-danger">
                <div class="kpi-icon">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <small class="kpi-label">Gastos hoy</small>
                <div class="kpi-value danger counter" data-value="{{ $egresosHoy }}">
                    S/ 0.00
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="kpi-card kpi-warning">
                <div class="kpi-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <small class="kpi-label">Pendientes</small>
                <div class="kpi-sub warning">
                    Cobrar: S/ {{ number_format($porCobrar,2) }} <br>
                    Pagar: S/ {{ number_format($porPagar,2) }}
                </div>
            </div>
        </div>

    </div>

         {{-- ================= CONTENIDO ================= --}}
    <div class="row">

        {{-- ================= FLUJO ================= --}}
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="section-title">Flujo de caja (7 días)</h6>
                    <div style="height:280px; position:relative;">
                        <div class="chart-wrapper">
                            <canvas id="flujoCajaChart"></canvas>
                        </div>
                    </div> 
                </div>
            </div>
        </div>

        {{-- ================= ÚLTIMOS MOVIMIENTOS ================= --}}
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Últimos movimientos</h6>

                    @forelse($ultimosMovimientos as $m)
                        <div class="ult-mov-item">

                            <div class="d-flex justify-content-between align-items-center">

                                {{-- LADO IZQUIERDO --}}
                                <div class="d-flex align-items-center gap-3">

                                    <div class="mov-icon {{ $m->tipo === 'ingreso' ? 'mov-in' : 'mov-out' }}">
                                        <i class="fas {{ $m->tipo === 'ingreso' ? 'fa-arrow-down' : 'fa-arrow-up' }}"></i>
                                    </div>

                                    <div>
                                        <div class="fw-semibold mov-title">
                                            {{ $m->concepto }}
                                        </div>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($m->fecha)->format('d/m/Y') }}
                                        </small>
                                    </div>

                                </div>

                                {{-- LADO DERECHO --}}
                                <div class="text-end">
                                    <div class="fw-bold mov-amount {{ $m->tipo === 'ingreso' ? 'text-success' : 'text-danger' }}">
                                        {{ $m->tipo === 'ingreso' ? '+' : '-' }}
                                        S/ {{ number_format($m->monto,2) }}
                                    </div>

                                    <span class="badge {{ $m->estado === 'pagado' ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ ucfirst($m->estado) }}
                                    </span>
                                </div>

                            </div>

                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            No hay movimientos recientes
                        </div>
                    @endforelse

                </div>
            </div>
        </div>

    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm dashboard-status-card">
                <div class="card-body d-flex align-items-center gap-3">

                    <div class="status-icon
                        @if($balance > 0) status-positive
                        @elseif($balance < 0) status-negative
                        @else status-neutral
                        @endif
                    ">
                        <i class="fas
                            @if($balance > 0) fa-chart-line
                            @elseif($balance < 0) fa-exclamation-triangle
                            @else fa-minus
                            @endif
                        "></i>
                    </div>

                    <div>
                        <div class="fw-semibold mb-1">
                            Estado del negocio
                        </div>

                        <div class="text-muted">
                            @if($balance > 0)
                                El flujo operativo acumulado es positivo. Buen ritmo de ventas.
                            @elseif($balance < 0)
                                El flujo operativo acumulado es negativo. Revisa las ventas y gastos pequeños.
                            @else
                                No hay movimientos suficientes para analizar el flujo.
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</div>


@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
window.dashboardLabels = @json($labels);
window.dashboardIngresos = @json($ingresosData);
window.dashboardEgresos = @json($egresosData);
</script>
<script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
