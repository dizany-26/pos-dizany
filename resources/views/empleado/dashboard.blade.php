@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/empleado-dashboard.css') }}?v={{ filemtime(public_path('css/empleado-dashboard.css')) }}" rel="stylesheet">
@endpush

@section('header-back')
<button class="btn-header-back" onclick="history.back()"><i class="fas fa-chevron-left"></i></button>
@endsection

@section('header-title', 'Dashboard')
@section('header-buttons')@endsection

@section('content')
<div class="employee-dashboard">
    <section class="employee-hero">
        <div>
            <span class="employee-eyebrow">PANEL DE TURNO</span>
            <h1>Hola, {{ Auth::user()->nombre }}</h1>
            <p>{{ now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}</p>
        </div>
        <div class="employee-shift {{ $caja?->estado === 'abierta' ? 'is-open' : ($caja ? 'is-pending' : 'is-closed') }}">
            <span class="employee-shift-dot"></span>
            <div>
                <strong>{{ $caja?->estado === 'abierta' ? 'Caja abierta' : ($caja ? 'Cierre pendiente' : 'Sin caja asignada') }}</strong>
                <small>{{ $caja?->abierta_en ? 'Desde '.$caja->abierta_en->format('H:i') : 'Solicita la apertura al administrador' }}</small>
            </div>
        </div>
    </section>

    @if(Auth::user()->tienePermiso('ventas'))
    <section class="employee-stats">
        <article class="employee-stat employee-stat-blue"><span class="employee-stat-icon"><i class="fas fa-receipt"></i></span><div><small>Ventas realizadas hoy</small><strong>{{ $resumen['cantidad'] }}</strong></div></article>
        <article class="employee-stat employee-stat-green"><span class="employee-stat-icon"><i class="fas fa-coins"></i></span><div><small>Total vendido por ti</small><strong>S/ {{ number_format($resumen['total'], 2) }}</strong></div></article>
        <article class="employee-stat employee-stat-amber"><span class="employee-stat-icon"><i class="fas fa-clock"></i></span><div><small>Ventas pendientes</small><strong>{{ $resumen['pendientes'] }}</strong></div></article>
    </section>
    @endif

    <section class="employee-panel">
        <div class="employee-panel-title"><div><span>ACCESOS RÁPIDOS</span><h2>¿Qué deseas hacer?</h2></div><i class="fas fa-bolt"></i></div>
        <div class="employee-actions">
            @if(Auth::user()->tienePermiso('ventas'))
                <a href="{{ route('ventas.index') }}" class="employee-action employee-action-primary"><i class="fas fa-cash-register"></i><span><strong>Nueva venta</strong><small>Registrar una operación</small></span><i class="fas fa-arrow-right"></i></a>
            @endif
            @if(Auth::user()->tienePermiso('clientes'))
                <a href="{{ route('clientes.index') }}" class="employee-action"><i class="fas fa-users"></i><span><strong>Clientes</strong><small>Consultar y registrar</small></span><i class="fas fa-arrow-right"></i></a>
            @endif
            @if(Auth::user()->tienePermiso('productos'))
                <a href="{{ route('productos.index') }}" class="employee-action"><i class="fas fa-box-open"></i><span><strong>Productos</strong><small>Consultar stock y precios</small></span><i class="fas fa-arrow-right"></i></a>
            @endif
            @if(Auth::user()->tienePermiso('movimientos'))
                <a href="{{ route('movimientos.index') }}" class="employee-action"><i class="fas fa-layer-group"></i><span><strong>Mi turno</strong><small>Revisar mis movimientos</small></span><i class="fas fa-arrow-right"></i></a>
            @endif
            @if(Auth::user()->tienePermiso('gastos'))
                <a href="{{ route('gastos.index') }}" class="employee-action"><i class="fas fa-wallet"></i><span><strong>Registrar gasto</strong><small>Gastos autorizados del turno</small></span><i class="fas fa-arrow-right"></i></a>
            @endif
        </div>
    </section>

    @if(Auth::user()->tienePermiso('ventas'))
    <section class="employee-panel">
        <div class="employee-panel-title"><div><span>ACTIVIDAD RECIENTE</span><h2>Tus últimas ventas de hoy</h2></div><span class="employee-count">{{ $ultimasVentas->count() }}</span></div>
        <div class="employee-sales-scroll">
            <table class="employee-sales-table">
                <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Hora</th></tr></thead>
                <tbody>
                    @forelse($ultimasVentas as $venta)
                        <tr><td><span class="employee-sale-number">#{{ $venta->id }}</span></td><td>{{ $venta->cliente->nombre ?? 'Sin cliente' }}</td><td><strong>S/ {{ number_format($venta->total, 2) }}</strong></td><td>{{ $venta->fecha->format('H:i') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="employee-empty"><i class="fas fa-receipt"></i><span>Aún no registraste ventas hoy.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>
@endsection
