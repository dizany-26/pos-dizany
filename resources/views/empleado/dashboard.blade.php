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
<div class="container">
    <h4 class="mb-4"><i class="fas fa-user-circle"></i> Bienvenido, {{ Auth::user()->nombre }}</h4>

    <!-- Accesos rápidos -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-bolt"></i> Accesos Rápidos
        </div>
        <div class="card-body">
            <div class="row g-3">
                @if (Auth::user()->hasPermission('operaciones.ventas'))
                    <div class="col-md-3 col-6">
                        <a href="{{ route('ventas.index') }}" class="btn btn-outline-success w-100">
                            <i class="fas fa-cash-register fa-2x d-block mb-2"></i>
                            Registrar Venta
                        </a>
                    </div>

                    <div class="col-md-3 col-6">
                        <a href="{{ route('ventas.listar') }}" class="btn btn-outline-info w-100">
                            <i class="fas fa-list fa-2x d-block mb-2"></i>
                            Ver Ventas
                        </a>
                    </div>
                @endif

                @if (Auth::user()->hasPermission('analisis.reportes'))
                    <div class="col-md-3 col-6">
                        <a href="{{ route('reportes.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-boxes fa-2x d-block mb-2"></i>
                            Reportes
                        </a>
                    </div>
                @endif

                @if (Auth::user()->hasPermission('operaciones.gastos'))
                    <div class="col-md-3 col-6">
                        <a href="{{ route('gastos.index') }}" class="btn btn-outline-danger w-100">
                            <i class="fas fa-wallet fa-2x d-block mb-2"></i>
                            Registrar Gasto
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabla últimas ventas del día -->
    <div class="card shadow-sm">
        <div class="card-header bg-secondary text-white">
            <i class="fas fa-clock"></i> Tus últimas ventas de hoy
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($ultimasVentas ?? collect()) as $venta)
                            <tr>
                                <td>{{ $venta->id }}</td>
                                <td>{{ $venta->cliente->nombre ?? 'Sin cliente' }}</td>
                                <td>S/ {{ number_format($venta->total, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($venta->fecha)->format('H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No tienes ventas registradas hoy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

