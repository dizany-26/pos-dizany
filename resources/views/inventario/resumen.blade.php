@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/resumen.css') }}" rel="stylesheet" />
@endpush

{{-- BOTÓN ATRÁS --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Resumen de Inventario
@endsection

@section('content')

<div class="container py-4">

    {{-- DASHBOARD CARDS --}}
    <div class="row g-3 mb-4 align-items-stretch">

        {{-- Sin stock --}}
        <div class="col-12 col-md">
            <div class="card border-0 shadow-sm rounded-4 dashboard-card bg-gradient-danger text-white h-100 position-relative overflow-hidden">
                <div class="card-body py-3 px-4 d-flex flex-column justify-content-between">

                    <!-- Línea 1 -->
                    <div class="small opacity-75 fw-semibold">
                        Productos sin stock
                    </div>

                    <!-- Línea 2 -->
                    <div class="fs-2 fw-bold counter"
                        data-target="{{ $productosSinStock }}">
                        0
                    </div>

                </div>

                <!-- Línea 3 (icono decorativo) -->
                <i class="fa-solid fa-circle-xmark card-icon"></i>

            </div>
        </div>

        {{-- Stock bajo --}}
        <div class="col-12 col-md">
            <div class="card border-0 shadow-sm rounded-4 dashboard-card bg-gradient-warning text-dark h-100 position-relative overflow-hidden">
                <div class="card-body py-3 px-4 d-flex flex-column justify-content-between">

                    <!-- Línea 1 -->
                    <div class="small fw-semibold">
                        Stock bajo
                    </div>

                    <!-- Línea 2 -->
                    <div class="fs-2 fw-bold counter"
                        data-target="{{ $productosStockBajo->count() }}">
                        0
                    </div>

                </div>

                <!-- Icono decorativo -->
                <i class="fa-solid fa-triangle-exclamation card-icon text-dark"></i>

            </div>
        </div>

        {{-- Por vencer --}}
        <div class="col-12 col-md">
            <div class="card border-0 shadow-sm rounded-4 dashboard-card bg-gradient-info text-white h-100 position-relative overflow-hidden">
                <div class="card-body py-3 px-4 d-flex flex-column justify-content-between">

                    <!-- Línea 1 -->
                    <div class="small opacity-75 fw-semibold">
                        Lotes por vencer
                    </div>

                    <!-- Línea 2 -->
                    <div class="fs-2 fw-bold counter"
                        data-target="{{ $lotesPorVencer->count() }}">
                        0
                    </div>

                </div>

                <!-- Icono decorativo -->
                <i class="fa-solid fa-calendar-days card-icon"></i>

            </div>
        </div>

        {{-- Total unidades --}}
        <div class="col-12 col-md">
            <div class="card border-0 shadow-sm rounded-4 dashboard-card bg-gradient-success text-white h-100 position-relative overflow-hidden">
                <div class="card-body py-3 px-4 d-flex flex-column justify-content-between">

                    <!-- Línea 1 -->
                    <div class="small opacity-75 fw-semibold">
                        Total unidades
                    </div>

                    <!-- Línea 2 -->
                    <div class="fs-2 fw-bold counter"
                        data-target="{{ $totalUnidades }}">
                        0
                    </div>

                </div>

                <!-- Icono decorativo -->
                <i class="fa-solid fa-box-open card-icon"></i>

            </div>
        </div>

        {{-- Tarjeta financiera --}}
        <div class="col-12 col-md-5">
            <div class="card border-0 shadow-sm rounded-4 bg-dark text-white h-100">
                <div class="card-body py-3 px-4">

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small opacity-75 mb-1">
                                Inversión total
                            </div>
                            <div class="fs-4 fw-bold counter-money"
                                 data-target="{{ $inversion }}">
                                S/ 0
                            </div>
                        </div>

                        <div style="width:70px;height:70px;">
                            <canvas id="miniFinanceChart"></canvas>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-3 small">
                        <div>
                            <div class="opacity-75">Venta</div>
                            <div class="fw-semibold">
                                S/ {{ number_format($valorVenta, 2) }}
                            </div>
                        </div>

                        <div>
                            <div class="opacity-75">Margen</div>
                            <div class="fw-semibold 
                                {{ $margenPotencial >= 0 ? 'text-success' : 'text-danger' }}">
                                S/ {{ number_format($margenPotencial, 2) }}
                            </div>
                        </div>

                        <div>
                            <div class="opacity-75">Rentabilidad</div>
                            <div class="fw-semibold 
                                {{ $porcentajeRentabilidad >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($porcentajeRentabilidad, 1) }}%
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    {{-- TABLAS LADO A LADO --}}
    <div class="row g-4">

        <div class="col-md-6">
            <div class="card shadow-sm rounded-4 border-0 h-100">
                <div class="card-header bg-white fw-bold border-0">
                    Productos críticos
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle mb-0 ui-table text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productosStockBajo as $producto)
                                <tr>
                                    <td data-label="Producto">
                                        {{ $producto->nombre }}
                                    </td>

                                    <td data-label="Stock" class="fw-bold">
                                        {{ $producto->stock_total ?? 0 }}
                                    </td>

                                    <td data-label="Estado">
                                        @if(($producto->stock_total ?? 0) == 0)
                                            <span class="ui-badge ui-badge-danger">Sin stock</span>
                                        @else
                                            <span class="ui-badge ui-badge-warning">Stock bajo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm rounded-4 border-0 h-100">
                <div class="card-header bg-white fw-bold border-0">
                    Lotes próximos a vencer (30 días)
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle mb-0 ui-table text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>Lote</th>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Vencimiento</th>
                                <th>Días</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lotesPorVencer as $lote)
                                @php
                                    $dias = \Carbon\Carbon::now()->diffInDays($lote->fecha_vencimiento, false);
                                @endphp
                                <tr>
                                    <td data-label="Lote">
                                        LT-{{ $lote->numero_lote }}
                                    </td>

                                    <td data-label="Producto">
                                        {{ $lote->producto->nombre }}
                                    </td>

                                    <td data-label="Stock">
                                        {{ $lote->stock_actual }}
                                    </td>

                                    <td data-label="Vencimiento" class="fw-bold text-danger">
                                        {{ \Carbon\Carbon::parse($lote->fecha_vencimiento)->format('d/m/Y') }}
                                    </td>

                                    <td data-label="Días">
                                        <span class="ui-badge ui-badge-danger">
                                            {{ $dias }} días
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

        const ctx = document.getElementById('miniFinanceChart');

        if (ctx) {
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Inversión', 'Margen'],
                    datasets: [{
                        data: [
                            {{ $inversion }},
                            {{ max($margenPotencial, 0) }}
                        ],
                        backgroundColor: [
                            '#0dcaf0',
                            '#28a745'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '70%',
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

    });
</script>
<script>
document.addEventListener("DOMContentLoaded", () => {

    const counters = document.querySelectorAll('.counter');

    counters.forEach(counter => {
        const update = () => {
            const target = +counter.getAttribute('data-target');
            const current = +counter.innerText;
            const increment = target / 40;

            if (current < target) {
                counter.innerText = Math.ceil(current + increment);
                setTimeout(update, 20);
            } else {
                counter.innerText = target;
            }
        };
        update();
    });

    // Para dinero
    const moneyCounters = document.querySelectorAll('.counter-money');

    moneyCounters.forEach(counter => {
        const updateMoney = () => {
            const target = +counter.getAttribute('data-target');
            const current = parseFloat(counter.innerText.replace('S/','')) || 0;
            const increment = target / 40;

            if (current < target) {
                counter.innerText = "S/ " + (current + increment).toFixed(2);
                setTimeout(updateMoney, 20);
            } else {
                counter.innerText = "S/ " + target.toFixed(2);
            }
        };
        updateMoney();
    });

});
</script>
@endpush
