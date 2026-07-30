@extends('layouts.app')

{{-- Activa el sistema de header-actions --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

@section('header-title')
Movimientos
@endsection

@section('header-buttons')

@if(auth()->user()->esAdmin())
    <button class="btn-gasto" data-bs-toggle="modal" data-bs-target="#modalAbrirCaja">
        <i class="fas fa-cash-register"></i>
        <span class="btn-text">Asignar caja</span>
    </button>
@endif

@if($cajaAbierta?->estado === 'abierta')
    <button class="btn-gasto" data-bs-toggle="modal" data-bs-target="#modalCerrarCaja">
        <i class="fas fa-lock"></i>
        <span class="btn-text">Solicitar cierre</span>
    </button>
@elseif($cajaAbierta?->estado === 'pendiente_cierre')
    @if(auth()->user()->esAdmin())
        <a class="btn-gasto" href="{{ route('movimientos.index', ['tipo' => 'cierres', 'rango' => request('rango', 'diario'), 'fecha' => request('fecha', now()->format('Y-m-d'))]) }}">
            <i class="fas fa-clipboard-check"></i>
            <span class="btn-text">Revisar cierre</span>
        </a>
    @else
        <button class="btn-gasto" type="button" disabled title="Esperando revisión del administrador">
            <i class="fas fa-hourglass-half"></i>
            <span class="btn-text">Cierre pendiente</span>
        </button>
    @endif
@elseif(!auth()->user()->esAdmin())
    <button class="btn-gasto" type="button" disabled title="El administrador debe asignarte una caja">
        <i class="fas fa-lock"></i>
        <span class="btn-text">Sin caja</span>
    </button>
@endif

<a href="{{ route('movimientos.reporte') }}"
   class="btn-gasto">
    <i class="fas fa-file-download"></i>
    <span class="btn-text">Reporte</span>
</a>

@endsection

@section('content')

<div class="card ui-card container-card my-4">
    <div class="card-body">

        {{-- ================= TABS PRINCIPALES ================= --}}
        <div class="d-flex gap-2 mb-4">
            <a href="{{ route('movimientos.index', array_merge(request()->query(), ['tipo' => 'transacciones'])) }}"
               class="btn-soft {{ request('tipo','transacciones') === 'transacciones' ? 'btn-soft-primary' : 'btn-soft-info' }} flex-fill text-center">
                Transacciones
            </a>

            <a href="{{ route('movimientos.index', array_merge(request()->query(), ['tipo' => 'cierres'])) }}"
               class="btn-soft {{ request('tipo') === 'cierres' ? 'btn-soft-primary' : 'btn-soft-info' }} flex-fill text-center">
                Cierres de caja
            </a>
        </div>

        @if($cajaAbierta && $tipo === 'transacciones')
            <div class="cash-session-banner mb-4">
                <div>
                    <span class="cash-session-dot"></span>
                    <div>
                        <strong>{{ $cajaAbierta->estado === 'abierta' ? 'Caja abierta' : 'Cierre pendiente de aprobación' }}</strong>
                        <small>Desde {{ $cajaAbierta->abierta_en->format('d/m/Y H:i') }} · Fondo S/ {{ number_format($cajaAbierta->monto_inicial, 2) }}</small>
                    </div>
                </div>
                @if(auth()->user()->esAdmin())
                    <div>
                        <span>Efectivo esperado</span>
                        <strong>S/ {{ number_format($resumenCaja['esperado'], 2) }}</strong>
                    </div>
                @else
                    <div>
                        <span>Control de caja</span>
                        <strong>Conteo ciego habilitado</strong>
                    </div>
                @endif
            </div>
        @endif

        {{-- ================= FILTROS ================= --}}
        <form method="GET"
              action="{{ route('movimientos.index') }}"
              class="row g-3 mb-4">

            <input type="hidden" name="tipo" value="{{ request('tipo','transacciones') }}">
            <input type="hidden" name="tab" value="{{ $tab }}">

            <div class="col-md-2">
                <select name="rango"
                        class="form-select ui-input"
                        onchange="this.form.submit()">
                    <option value="diario" {{ $rango === 'diario' ? 'selected' : '' }}>Diario</option>
                    <option value="semanal" {{ $rango === 'semanal' ? 'selected' : '' }}>Semanal</option>
                    <option value="mensual" {{ $rango === 'mensual' ? 'selected' : '' }}>Mensual</option>
                    <option value="anual" {{ $rango === 'anual' ? 'selected' : '' }}>Anual</option>
                    <option value="personalizado" {{ $rango === 'personalizado' ? 'selected' : '' }}>Personalizado</option>
                </select>
            </div>

            <div class="col-md-2">
                {{-- Wrapper relativo (CLAVE) --}}
                <div class="position-relative" id="year-picker-wrapper">

                    {{-- Tu input-group original (NO se rompe) --}}
                    <div class="input-group" id="picker-wrapper">
                        <input
                            id="filter-date"
                            name="fecha"
                            class="form-control ui-input"
                            value="{{ $rango === 'anual' ? substr($fecha, 0, 4) : $fecha }}"
                            autocomplete="off"
                            readonly
                            data-input
                        >
                        <span class="input-group-text" data-toggle>
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>

                    @php
                        $yearActivo = $rango === 'anual'
                            ? substr($fecha, 0, 4)
                            : now()->year;
                    @endphp

                    <div id="year-picker" class="year-picker d-none">
                        @for ($y = now()->year - 10; $y <= now()->year + 10; $y++)
                            <button
                                type="button"
                                class="year-btn {{ (string)$yearActivo === (string)$y ? 'active' : '' }}"
                                data-year="{{ $y }}"
                            >
                                {{ $y }}
                            </button>
                        @endfor
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <input type="text"
                       name="buscar"
                       value="{{ request('buscar') }}"
                       class="form-control ui-input"
                       placeholder="{{ $tipo === 'cierres' ? 'Buscar cajero...' : 'Buscar concepto...' }}"
                       onkeydown="if(event.key==='Enter'){ this.form.submit(); }">
            </div>

        </form>

        @if($tipo === 'transacciones')
        {{-- ================= KPIs ================= --}}
        <div class="row mb-4 g-3">

            <div class="col-md-3">
                <div class="card ui-card dashboard-card rounded-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="icon-soft icon-soft-primary">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <small class="text-muted">Balance</small>
                            <h5 class="fw-bold mb-0">
                                S/ {{ number_format($balance ?? 0, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card ui-card dashboard-card rounded-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="icon-soft icon-soft-success">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <div>
                            <small class="text-muted">Ventas totales</small>
                            <h5 class="fw-bold text-success mb-0">
                                S/ {{ number_format($ventas ?? 0, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card ui-card dashboard-card rounded-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="icon-soft icon-soft-danger">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div>
                            <small class="text-muted">Gastos totales</small>
                            <h5 class="fw-bold text-danger mb-0">
                                S/ {{ number_format($gastos ?? 0, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card ui-card dashboard-card rounded-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="icon-soft icon-soft-warning">
                            <i class="fas fa-sack-dollar"></i>
                        </div>
                        <div>
                            <small class="text-muted">Ganancia</small>
                            <h5 class="fw-bold {{ $ganancias >= 0 ? 'text-success' : 'text-danger' }} mb-0">
                                S/ {{ number_format($ganancias ?? 0, 2) }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        @endif

        @if($tipo === 'transacciones')
        {{-- ================= SUBTABS ================= --}}
        @php
            $tabs = [
                'ingresos'   => 'Ingresos',
                'egresos'    => 'Egresos',
                'por_cobrar' => 'Por cobrar',
                'por_pagar'  => 'Por pagar',
            ];
        @endphp

        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach($tabs as $key => $label)
                <a class="btn-soft {{ $tab === $key ? 'btn-soft-primary' : 'btn-soft-info' }}"
                   href="{{ route('movimientos.index', array_merge(request()->query(), ['tab' => $key])) }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        @endif

        {{-- ================= TABLA ================= --}}
        <div class="card ui-card rounded-4">
            @if($tipo === 'transacciones')
            <div class="table-responsive ui-scroll">
                <table class="table ui-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Método</th>
                            <th>Estado</th>
                            <th class="text-end">Monto</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>

                    @forelse ($movimientos as $movimiento)
                        <tr class="mov-row"
                            style="cursor:pointer"
                            data-ref-id="{{ $movimiento->referencia_id }}"
                            data-ref-tipo="{{ $movimiento->referencia_tipo }}"
                            data-mov-id="{{ $movimiento->id }}">

                            <td data-label="Fecha">{{ \Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y') }}</td>
                            <td data-label="Concepto">{{ $movimiento->concepto }}</td>
                            <td data-label="Método">{{ ucfirst($movimiento->metodo_pago) }}</td>

                            <td data-label="Estado">
                                @if ($movimiento->estado === 'pagado')
                                    <span class="ui-badge ui-badge-success">Pagado</span>
                                @elseif ($movimiento->estado === 'pendiente')
                                    <span class="ui-badge ui-badge-warning">Pendiente</span>
                                @elseif ($movimiento->estado === 'anulado')
                                    <span class="ui-badge ui-badge-danger">Anulado</span>
                                @endif
                            </td>

                            <td data-label="Monto"
                                class="text-end fw-bold {{ $movimiento->tipo === 'ingreso' ? 'text-success' : 'text-danger' }}">
                                {{ $movimiento->tipo === 'ingreso' ? '+' : '-' }}
                                S/ {{ number_format($movimiento->monto, 2) }}
                            </td>

                            <td data-label="Acciones" class="text-center">
                                <button class="btn-soft btn-soft-primary btn-soft-icon btn-sm">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No hay movimientos para mostrar
                            </td>
                        </tr>
                    @endforelse

                    </tbody>
                </table>
            </div>

            {{-- ================= PAGINACIÓN ================= --}}
            @if($movimientos instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="card-footer d-flex justify-content-end">
                    {{ $movimientos->appends(request()->query())->links() }}
                </div>
            @endif
            @else
            <div class="cash-table-scroll-hint">
                <i class="fas fa-arrows-left-right"></i>
                Desliza para ver todos los datos del cierre
            </div>
            <div class="table-responsive caja-cierres-scroll" id="cajaCierresScroll">
                <table class="table ui-table align-middle mb-0 caja-cierres-table">
                    <thead>
                        <tr>
                            <th>Cajero</th>
                            <th>Apertura</th>
                            <th>Cierre</th>
                            <th class="text-end">Inicial</th>
                            <th class="text-end">Ingresos efectivo</th>
                            <th class="text-end">Egresos efectivo</th>
                            <th class="text-end">Refuerzos</th>
                            <th class="text-end">Retiros</th>
                            <th class="text-end">Esperado</th>
                            <th class="text-end">Contado</th>
                            <th class="text-end">Diferencia</th>
                            <th>Estado</th>
                            @if(auth()->user()->esAdmin())<th>Acciones</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cajas as $caja)
                            @php
                                $totalesCajaFila = $caja->estado !== 'cerrada'
                                    ? $caja->calcularEfectivo()
                                    : [
                                        'ingresos' => $caja->ingresos_efectivo ?? 0,
                                        'egresos' => $caja->egresos_efectivo ?? 0,
                                        'refuerzos' => $caja->operaciones()->where('tipo', 'refuerzo')->sum('monto'),
                                        'retiros' => $caja->operaciones()->where('tipo', 'retiro')->sum('monto'),
                                        'esperado' => $caja->monto_esperado ?? 0,
                                    ];
                                $puedeVerArqueo = auth()->user()->esAdmin() || $caja->estado === 'cerrada';
                                $contadoCajaFila = $caja->estado === 'pendiente_cierre'
                                    ? $caja->monto_declarado
                                    : $caja->monto_contado;
                                $diferenciaCajaFila = $contadoCajaFila !== null
                                    ? (float) $contadoCajaFila - (float) $totalesCajaFila['esperado']
                                    : null;
                            @endphp
                            <tr>
                                <td data-label="Cajero">{{ $caja->usuario->nombre ?? '—' }}</td>
                                <td data-label="Apertura">{{ $caja->abierta_en->format('d/m/Y H:i') }}</td>
                                <td data-label="Cierre">{{ $caja->cerrada_en?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td data-label="Inicial" class="text-end">S/ {{ number_format($caja->monto_inicial, 2) }}</td>
                                <td data-label="Ingresos" class="text-end text-success">S/ {{ number_format($totalesCajaFila['ingresos'], 2) }}</td>
                                <td data-label="Egresos" class="text-end text-danger">S/ {{ number_format($totalesCajaFila['egresos'], 2) }}</td>
                                <td data-label="Refuerzos" class="text-end text-success">S/ {{ number_format($totalesCajaFila['refuerzos'], 2) }}</td>
                                <td data-label="Retiros" class="text-end text-danger">S/ {{ number_format($totalesCajaFila['retiros'], 2) }}</td>
                                <td data-label="Esperado" class="text-end fw-bold">{{ $puedeVerArqueo ? 'S/ '.number_format($totalesCajaFila['esperado'], 2) : 'Oculto' }}</td>
                                <td data-label="Contado" class="text-end">{{ $puedeVerArqueo && $contadoCajaFila !== null ? 'S/ '.number_format($contadoCajaFila, 2) : '—' }}</td>
                                <td data-label="Diferencia" class="text-end fw-bold {{ ($diferenciaCajaFila ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $puedeVerArqueo && $diferenciaCajaFila !== null ? 'S/ '.number_format($diferenciaCajaFila, 2) : '—' }}
                                </td>
                                <td data-label="Estado">
                                    <span class="ui-badge {{ $caja->estado === 'cerrada' ? 'ui-badge-success' : 'ui-badge-warning' }}">
                                        {{ $caja->estado === 'pendiente_cierre' ? 'Por aprobar' : ucfirst($caja->estado) }}
                                    </span>
                                </td>
                                @if(auth()->user()->esAdmin())
                                <td data-label="Acciones">
                                    <div class="d-flex gap-1">
                                        @if($caja->estado === 'abierta')
                                            <button type="button" class="btn-soft btn-soft-success btn-sm caja-operacion-btn"
                                                data-caja="{{ $caja->id }}" data-tipo="refuerzo" data-bs-toggle="modal" data-bs-target="#modalOperacionCaja">
                                                <i class="fas fa-plus"></i> Refuerzo
                                            </button>
                                            <button type="button" class="btn-soft btn-soft-warning btn-sm caja-operacion-btn"
                                                data-caja="{{ $caja->id }}" data-tipo="retiro" data-bs-toggle="modal" data-bs-target="#modalOperacionCaja">
                                                <i class="fas fa-minus"></i> Retiro
                                            </button>
                                        @elseif($caja->estado === 'pendiente_cierre')
                                            <form method="POST" action="{{ route('cajas.aprobar', $caja) }}">
                                                @csrf
                                                <button class="btn-soft btn-soft-success btn-sm" type="submit">
                                                    <i class="fas fa-check"></i> Aprobar
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('cajas.reabrir', $caja) }}">
                                                @csrf
                                                <button class="btn-soft btn-soft-warning btn-sm" type="submit">
                                                    <i class="fas fa-rotate-left"></i> Devolver
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ auth()->user()->esAdmin() ? 13 : 12 }}" class="text-center text-muted py-4">No hay sesiones de caja en este periodo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($cajas->hasPages())
                <div class="card-footer d-flex justify-content-end">
                    {{ $cajas->appends(request()->query())->links() }}
                </div>
            @endif
            @endif
        </div>

    </div>
</div>

@if(auth()->user()->esAdmin())
<div class="modal fade" id="modalAbrirCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('cajas.abrir') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cash-register"></i> Abrir caja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="cash-explanation">
                    Asigna el responsable y registra el efectivo físico entregado al iniciar el turno.
                </div>
                <label class="form-label">Empleado responsable</label>
                <select name="usuario_id" class="form-select ui-input mb-3" required>
                    <option value="">Selecciona un empleado</option>
                    @foreach($usuariosCaja as $usuarioCaja)
                        <option value="{{ $usuarioCaja->id }}">{{ $usuarioCaja->nombre }}</option>
                    @endforeach
                </select>
                <label class="form-label">Fondo inicial</label>
                <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <input type="number" name="monto_inicial" class="form-control ui-input"
                        min="0" step="0.01" value="0.00" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-soft btn-soft-success">
                    <i class="fas fa-lock-open"></i> Abrir caja
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@if($cajaAbierta?->estado === 'abierta')
<div class="modal fade" id="modalCerrarCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('cajas.solicitar-cierre', $cajaAbierta) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-lock"></i> Solicitar cierre de caja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if(auth()->user()->esAdmin())
                <div class="cash-closing-summary">
                    <div><span>Fondo inicial</span><strong>S/ {{ number_format($cajaAbierta->monto_inicial, 2) }}</strong></div>
                    <div><span>Ingresos en efectivo</span><strong class="text-success">+ S/ {{ number_format($resumenCaja['ingresos'], 2) }}</strong></div>
                    <div><span>Egresos en efectivo</span><strong class="text-danger">− S/ {{ number_format($resumenCaja['egresos'], 2) }}</strong></div>
                    <div class="cash-expected"><span>Efectivo esperado</span><strong>S/ {{ number_format($resumenCaja['esperado'], 2) }}</strong></div>
                </div>
                @else
                <div class="cash-explanation">
                    Realiza el conteo físico sin consultar el total esperado. Después de enviarlo no podrás registrar más ventas hasta que un administrador revise el cierre.
                </div>
                @endif
                <label class="form-label mt-3">Efectivo contado físicamente</label>
                <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <input type="number" name="monto_contado" id="montoContadoCaja"
                        class="form-control ui-input" min="0" step="0.01" required>
                </div>
                @if(auth()->user()->esAdmin())
                    <div id="diferenciaCaja" class="cash-difference mt-2"></div>
                @endif
                <label class="form-label mt-3">Observaciones</label>
                <textarea name="observaciones" class="form-control ui-input" rows="2"
                    placeholder="Opcional: explica sobrantes, faltantes u otra incidencia"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-soft btn-soft-warning">
                    <i class="fas fa-paper-plane"></i> Enviar conteo
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@if(auth()->user()->esAdmin())
<div class="modal fade" id="modalOperacionCaja" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formOperacionCaja" class="modal-content">
            @csrf
            <input type="hidden" name="tipo" id="operacionCajaTipo">
            <div class="modal-header">
                <h5 class="modal-title" id="operacionCajaTitulo">
                    <i class="fas fa-money-bill-transfer"></i> Movimiento de caja
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="cash-explanation" id="operacionCajaAyuda"></div>
                <label class="form-label">Monto</label>
                <div class="input-group mb-3">
                    <span class="input-group-text">S/</span>
                    <input type="number" name="monto" class="form-control ui-input" min="0.01" step="0.01" required>
                </div>
                <label class="form-label" id="operacionCajaOrigenLabel">Origen o destino</label>
                <input type="text" name="origen_destino" class="form-control ui-input mb-3"
                    placeholder="Ej. Caja general" required>
                <label class="form-label">Motivo</label>
                <textarea name="motivo" class="form-control ui-input" rows="2"
                    placeholder="Explica por qué se realiza esta operación" required></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-soft btn-soft-primary">Registrar operación</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ================= OFFCANVAS DETALLE ================= --}}
<div class="offcanvas offcanvas-end detalle-venta-panel"
     tabindex="-1"
     id="offcanvasDetalle">

    <div class="offcanvas-header pb-2">
        <h5 class="offcanvas-title mb-0" id="detalleMovimientoTitulo">
            Detalle de la venta
        </h5>
        <button type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas"></button>
    </div>

    <div class="divider-green"></div>

    <div class="offcanvas-body" id="detalleContenido">
        {{-- EL JS inyecta aquí --}}
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/movimientos.css') }}?v={{ filemtime(public_path('css/movimientos.css')) }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
<style>
.range-selected{
    background:#16a34a !important;
    color:white !important;
    border-radius:50% !important;
}
</style>

@endpush

@push('scripts')
<script src="{{ asset('js/movimientos.js') }}?v={{ filemtime(public_path('js/movimientos.js')) }}"></script>
@if($cajaAbierta)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('montoContadoCaja');
    const output = document.getElementById('diferenciaCaja');
    const esperado = @json((float) $resumenCaja['esperado']);
    if (!input || !output) return;

    const actualizar = () => {
        if (input.value === '') {
            output.textContent = 'Ingresa el efectivo contado para calcular la diferencia.';
            output.className = 'cash-difference mt-2';
            return;
        }
        const diferencia = Number(input.value) - esperado;
        const etiqueta = diferencia === 0 ? 'Caja exacta' : (diferencia > 0 ? 'Sobrante' : 'Faltante');
        output.textContent = `${etiqueta}: S/ ${Math.abs(diferencia).toFixed(2)}`;
        output.className = `cash-difference mt-2 ${diferencia === 0 ? 'is-exact' : (diferencia > 0 ? 'is-surplus' : 'is-shortage')}`;
    };
    input.addEventListener('input', actualizar);
    actualizar();
});
</script>
@endif
@if(auth()->user()->esAdmin())
<script>
document.addEventListener('click', event => {
    const button = event.target.closest('.caja-operacion-btn');
    if (!button) return;

    const tipo = button.dataset.tipo;
    const cajaId = button.dataset.caja;
    const form = document.getElementById('formOperacionCaja');
    document.getElementById('operacionCajaTipo').value = tipo;
    form.action = `/cajas/${cajaId}/operaciones`;

    document.getElementById('operacionCajaTitulo').innerHTML = tipo === 'refuerzo'
        ? '<i class="fas fa-plus-circle"></i> Reforzar caja'
        : '<i class="fas fa-minus-circle"></i> Retirar efectivo';
    document.getElementById('operacionCajaAyuda').textContent = tipo === 'refuerzo'
        ? 'El dinero agregado aumentará el efectivo esperado. Registra su procedencia.'
        : 'El dinero retirado disminuirá el efectivo esperado. Registra su destino.';
    document.getElementById('operacionCajaOrigenLabel').textContent = tipo === 'refuerzo'
        ? 'Procedencia del dinero'
        : 'Destino del dinero';
});
</script>
@endif
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>

<script>
flatpickr.localize(flatpickr.l10ns.es);

(function () {
    const rango = "{{ $rango }}";

    // ✅ importante: apunta al form correcto
    const form = document.querySelector('form[action="{{ route('movimientos.index') }}"]');

    // Si no encuentra el form, no hagas nada (evita errores raros)
    if (!form) return;

    // ✅ esta función EXISTE para todos los rangos
    function submitFormDelayed() {
        clearTimeout(window.__mov_submit_timer);
        window.__mov_submit_timer = setTimeout(() => form.submit(), 200);
    }

    // Helper: date válida YYYY-MM-DD
    function isYmd(str){
        return /^\d{4}-\d{2}-\d{2}$/.test(str);
    }

    // Helper: año válido YYYY
    function isYear(str){
        return /^\d{4}$/.test(str);
    }

    // Normaliza default según rango
    let defaultFecha = "{{ $fecha }}";
    if (rango === "diario" && !isYmd(defaultFecha)) {
        defaultFecha = "{{ now()->format('Y-m-d') }}";
    }
    if (rango === "mensual") {
        // mensual: trabajaremos con YYYY-MM
        if (!/^\d{4}-\d{2}$/.test(defaultFecha)) {
            defaultFecha = "{{ now()->format('Y-m') }}";
        } else {
            defaultFecha = defaultFecha.substring(0,7);
        }
    }
    if (rango === "anual") {
        // anual: YYYY
        const y = defaultFecha.substring(0,4);
        defaultFecha = isYear(y) ? y : "{{ now()->format('Y') }}";
    }

    // Destruir instancia anterior si existe (evita bugs al recargar con cache)
    if (window.__mov_fp) {
        try { window.__mov_fp.destroy(); } catch(e){}
        window.__mov_fp = null;
    }

    // ===================== DIARIO =====================
    if (rango === "diario") {
        window.__mov_fp = flatpickr("#picker-wrapper", {
            wrap: true,
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "j M Y",
            defaultDate: defaultFecha,
            allowInput: false,
            clickOpens: true,
            onChange: submitFormDelayed
        });
    }

    // ===================== SEMANAL (Lun-Dom) =====================
    if (rango === "semanal") {
    let initialized = false; // 👈 CLAVE para evitar loop

    window.__mov_fp = flatpickr("#picker-wrapper", {
        wrap: true,
        mode: "range",
        locale: "es",

        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j M",
        conjunction: " a ",

        defaultDate: "{{ $fecha ?: now()->format('Y-m-d') }}",
        allowInput: false,

        // 🔹 SOLO SELECCIÓN VISUAL (NO SUBMIT)
        onReady(selectedDates, str, fp) {

            const base = selectedDates[0] || new Date();

            const day = base.getDay(); // 0=Dom, 1=Lun
            const diffToMonday = day === 0 ? -6 : 1 - day;

            const start = new Date(base);
            start.setDate(base.getDate() + diffToMonday);

            const end = new Date(start);
            end.setDate(start.getDate() + 6);

            // Seleccionar semana completa (visual)
            fp.setDate([start, end], true);

            // Marcar que ya inicializó
            initialized = true;
        },

        onChange(dates, str, fp) {

            // Ignorar el primer cambio disparado por setDate del onReady
            if (!initialized) return;

            // Si elige un solo día → completar semana
            if (dates.length === 1) {

                const base = dates[0];
                const day = base.getDay();
                const diffToMonday = day === 0 ? -6 : 1 - day;

                const start = new Date(base);
                start.setDate(base.getDate() + diffToMonday);

                const end = new Date(start);
                end.setDate(start.getDate() + 6);

                fp.setDate([start, end], true);
                return;
            }

            // Cuando ya hay rango completo → submit
            if (dates.length === 2) {
                submitFormDelayed();
            }
        }
    });
}

    // ===================== MENSUAL =====================
    if (rango === "mensual") {
        window.__mov_fp = flatpickr("#picker-wrapper", {
            wrap: true,
            plugins: [
                new monthSelectPlugin({
                    shorthand: true,
                    dateFormat: "Y-m",   // 👈 enviamos YYYY-MM al backend
                    altFormat: "M Y"
                })
            ],
            altInput: true,
            defaultDate: defaultFecha,
            allowInput: false,
            clickOpens: true,
            onChange: submitFormDelayed
        });
    }

    // ===================== ANUAL (solo año) =====================
    // Sin plugin raro: usamos un input de año
    if (rango === "anual") {
        const input  = document.getElementById("filter-date");
        const picker = document.getElementById("year-picker");

        // Normalizar valor inicial (solo año)
        if (input.value.length > 4) {
            input.value = input.value.substring(0, 4);
        }

        // Abrir / cerrar selector
        input.addEventListener("click", (e) => {
            e.stopPropagation();
            picker.classList.toggle("d-none");
        });

        // Click en un año
        picker.querySelectorAll(".year-btn").forEach(btn => {
            btn.addEventListener("click", () => {

                const year = btn.dataset.year;

                // actualizar input
                input.value = year;

                // marcar activo
                picker.querySelectorAll(".year-btn")
                    .forEach(b => b.classList.remove("active"));
                btn.classList.add("active");

                // cerrar picker
                picker.classList.add("d-none");

                // ✅ AQUÍ ESTABA EL ERROR
                submitFormDelayed();
            });
        });

        // cerrar si clic fuera
        document.addEventListener("click", (e) => {
            if (!picker.contains(e.target) && e.target !== input) {
                picker.classList.add("d-none");
            }
        });
    }

    // ===================== PERSONALIZADO (DOBLE) =====================
    if (rango === "personalizado") {

    if (window.__mov_fp) {
        window.__mov_fp.destroy();
    }

    // Detectar rango por estructura, no por símbolo
    const fechaBackend = "{{ $fecha }}";
    const partes = fechaBackend.split(" a ");
    const tieneRangoPrevio = partes.length === 2;

    window.__mov_fp = flatpickr("#picker-wrapper", {
        wrap: true,
        mode: "range",

        locale: {
            ...flatpickr.l10ns.es,
            rangeSeparator: " → "
        },

        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "j M",

        showMonths: 2,
        allowInput: false,

        // 🔑 USAR EL RANGO REAL QUE VIENE DEL BACKEND
        defaultDate: tieneRangoPrevio ? partes : null,

        // 🔑 SOLO limpiar si NO hay rango previo
        onOpen(selectedDates, dateStr, fp) {
            if (!tieneRangoPrevio) {
                fp.clear();
                fp.jumpToDate(new Date());
            }
        },

        onChange(dates) {
            if (dates.length === 2) {
                submitFormDelayed();
            }
        }
    });
}

})();
</script>

@endpush
