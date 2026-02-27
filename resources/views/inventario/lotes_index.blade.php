@extends('layouts.app')
{{-- ===================== STYLES ===================== --}}
@push('styles')
<style>
    .fefo-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    }

</style>
@endpush
{{-- BOTÓN ATRÁS --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection
@section('header-title', 'Lotes de Productos')

@section('content')
<div class="card ui-card container-card my-4">

        <div class="card-header text-center pt-4">
            <h4 class="mb-0 fw-semibold">
                <i class="fas fa-layer-group me-2 text-primary"></i>
                Lotes registrados
            </h4>
        </div>

        <div class="card-body pt-2 pb-4">
                {{-- FILTROS (van arriba de la tabla) --}}
                <div class="px-2 mb-4">
                    <div class="row mb-3 g-2">
                        <div class="col-md-2">
                            <select id="filtroEstado" class="form-select ui-input">
                                <option value="">Estado</option>
                                <option value="vencido">Vencidos</option>
                                <option value="10">Vence ≤ 10 días</option>
                                <option value="30">Vence ≤ 30 días</option>
                                <option value="ok">Vigentes</option>
                                <option value="sin">Sin vencimiento</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select id="filtroProducto" class="form-select ui-input">
                                <option value="">Producto</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ strtolower($producto->nombre) }}">
                                        {{ $producto->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select id="filtroStock" class="form-select ui-input">
                                <option value="">Stock</option>
                                <option value="con">Con stock</option>
                                <option value="sin">Sin stock</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select id="filtroFefo" class="form-select ui-input">
                                <option value="">FEFO</option>
                                <option value="1">Prioridad FEFO</option>
                            </select>
                        </div>

                        <div class="col-auto">
                            <div class="dropdown">
                                <button
                                    id="filtroMovimientosBtn"
                                    class="btn-soft btn-soft-primary btn-soft-icon"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    aria-expanded="false"
                                    title="Filtrar por movimientos"
                                >
                                    <i class="fas fa-list-alt"></i>
                                </button>

                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" data-mov="">
                                            Todos
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-mov="1">
                                            Con movimientos
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" data-mov="0">
                                            Sin movimientos
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-3 d-flex align-items-center gap-1">
                            <input type="text" id="filtroBuscar" class="form-control ui-input"
                                placeholder="Buscar lote o producto…">

                                <button type="button"
                                        id="btnLimpiarFiltros"
                                        class="btn-soft btn-soft-info btn-soft-icon"
                                        title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </button>
                        </div>
                    </div>
                </div>

            <div class="table-responsive ui-scroll">
                
                <div class="tabla-scroll">
                <table class="table ui-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cód. Comprobante</th>
                            <th class="text-center" style="width:60px;">FEFO</th>
                            <th class="text-center" style="width:100px;">N° Lote</th>
                            <th>Producto</th>
                            <th style="width:100px;">Proveedor</th>
                            <th class="text-center">Stock</th>
                            <th>Ingreso</th>
                            <th>Vencimiento</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width:120px;">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $fefoIndex = [];
                        @endphp

                        @forelse ($lotes as $lote)
                            @php
                                // =========================
                                // FEFO POR PRODUCTO
                                // =========================
                                $pid = $lote->producto_id;
                                $fefoIndex[$pid] = ($fefoIndex[$pid] ?? 0) + 1;

                                if ($lote->fecha_vencimiento && \Carbon\Carbon::parse($lote->fecha_vencimiento)->isPast()) {
                                    $fefoIcon = '<i class="fas fa-times-circle text-danger" title="Lote vencido"></i>';
                                } elseif ($fefoIndex[$pid] === 1) {
                                    $fefoIcon = '<i class="fas fa-circle text-success" title="Primer lote en salir (FEFO)"></i>';
                                } elseif ($fefoIndex[$pid] === 2) {
                                    $fefoIcon = '<i class="fas fa-circle text-warning" title="Segundo en prioridad FEFO"></i>';
                                } else {
                                    $fefoIcon = '<i class="fas fa-circle text-secondary" title="Lote posterior"></i>';
                                }

                                // =========================
                                // ESTADO DE VENCIMIENTO (PARA FILTROS)
                                // =========================
                                $hoy = \Carbon\Carbon::today();
                                $dias = $lote->fecha_vencimiento
                                    ? $hoy->diffInDays(\Carbon\Carbon::parse($lote->fecha_vencimiento), false)
                                    : null;

                                if (is_null($dias)) {
                                    $estadoVenc = 'sin';
                                } elseif ($dias < 0) {
                                    $estadoVenc = 'vencido';
                                } elseif ($dias <= 10) {
                                    $estadoVenc = '10';
                                } elseif ($dias <= 30) {
                                    $estadoVenc = '30';
                                } else {
                                    $estadoVenc = 'ok';
                                }
                            @endphp

                            <tr
                                data-estado="{{ $estadoVenc }}"
                                data-producto="{{ strtolower($lote->producto->nombre ?? '') }}"
                                data-stock="{{ $lote->stock_actual > 0 ? 'con' : 'sin' }}"
                                data-fefo="{{ $fefoIndex[$pid] === 1 ? '1' : '0' }}"
                                data-movimientos="{{ $lote->movimientos_count > 0 ? '1' : '0' }}"
                                data-texto="{{ strtolower(
                                    ($lote->codigo_comprobante ?? '') . ' ' .
                                    ($lote->producto->nombre ?? '')
                                ) }}"
                            >
                                {{-- CODIGO COMPROBANTE --}}
                                <td data-label="Cód. Comprobante">
                                    <strong>{{ blank($lote->codigo_comprobante) ? '—' : $lote->codigo_comprobante }}</strong>

                                   @if ($lote->stock_actual > 0)

                                        @if (is_null($dias))
                                            <div>
                                                <span class="ui-badge ui-badge-secondary mt-1">Sin vencimiento</span>
                                            </div>
                                        @elseif ($dias < 0)
                                            <div>
                                                <span class="ui-badge ui-badge-danger mt-1">Vencido</span>
                                            </div>
                                        @elseif ($dias <= 10)
                                            <div>
                                                <span class="ui-badge ui-badge-danger mt-1">
                                                    Vence en {{ $dias }} días
                                                </span>
                                            </div>
                                        @elseif ($dias <= 30)
                                            <div class="mt-1">
                                                <span class="ui-badge ui-badge-warning">
                                                    Vence en {{ $dias }} días
                                                </span>
                                            </div>
                                        @endif

                                    @endif
                                </td>

                                {{-- FEFO --}}
                                <td data-label="FEFO" class="text-center">
                                    <span class="fefo-dot fefo-success">
                                    {!! $fefoIcon !!}
                                    </span>
                                </td>

                                {{-- N° LOTE --}}
                                <td data-label="N° Lote" class="text-center fw-bold">
                                    LT-{{ str_pad($lote->numero_lote, 5, '0', STR_PAD_LEFT) }}
                                </td>

                                {{-- PRODUCTO --}}
                                <td data-label="Producto">
                                    <strong>{{ $lote->producto->nombre ?? '—' }}</strong><br>
                                    <small class="text-muted">
                                        {{ \Illuminate\Support\Str::limit($lote->producto->descripcion ?? '', 50) }}
                                    </small>
                                </td>

                                {{-- PROVEEDOR --}}
                                <td data-label="Proveedor">
                                    {{ $lote->proveedor->nombre ?? '—' }}
                                </td>

                                {{-- STOCK --}}
                                <td data-label="Stock" class="text-center fw-bold">
                                    {{ $lote->stock_actual }}
                                    <small class="text-muted d-block">
                                        / {{ $lote->stock_inicial }}
                                    </small>
                                </td>

                                {{-- INGRESO --}}
                                <td data-label="Ingreso">
                                    {{ \Carbon\Carbon::parse($lote->fecha_ingreso)->format('d/m/Y') }}
                                </td>

                                {{-- VENCIMIENTO --}}
                                <td data-label="Vencimiento">
                                    @if ($lote->fecha_vencimiento)
                                        {{ \Carbon\Carbon::parse($lote->fecha_vencimiento)->format('d/m/Y') }}
                                    @else
                                        <span class="text-muted">No aplica</span>
                                    @endif
                                </td>

                                {{-- ESTADO --}}
                                <td data-label="Estado" class="text-center">
                                    @if ($lote->stock_actual == 0)
                                        <span class="ui-badge ui-badge-secondary">Agotado</span>
                                    @elseif ($lote->fecha_vencimiento && \Carbon\Carbon::parse($lote->fecha_vencimiento)->isPast())
                                        <span class="ui-badge ui-badge-danger">Vencido</span>
                                    @else
                                        <span class="ui-badge ui-badge-success">Activo</span>
                                    @endif
                                </td>

                                {{-- ACCIONES --}}
                                <td data-label="Acciones">
                                    <div class="acciones-lote">
                                        <a href="{{ route('lotes.edit', $lote->id) }}"
                                        class="btn-soft btn-soft-warning btn-soft-icon btn-sm"
                                        title="Editar lote">
                                            <i class="fas fa-pen"></i>
                                        </a>

                                        @if (($lote->movimientos_count ?? 0) > 0)
                                            <a href="{{ route('lotes.movimientos', $lote->id) }}"
                                            class="btn-soft btn-soft-primary btn-soft-icon btn-sm"
                                            title="Ver movimientos">
                                                <i class="fas fa-list-alt"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    No hay lotes registrados
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>

            </div>

        </div>

</div>
@endsection

{{-- ===================== SCRIPTS ===================== --}}
@push('scripts')

<script>
    document.getElementById('btnLimpiarFiltros')
        .addEventListener('click', function () {

            document.getElementById('filtroEstado').value = '';
            document.getElementById('filtroProducto').value = '';
            document.getElementById('filtroStock').value = '';
            document.getElementById('filtroFefo').value = '';
            document.getElementById('filtroBuscar').value = '';

            document.querySelectorAll('.filtro-activo')
                .forEach(el => el.classList.remove('filtro-activo'));

            // Mostrar todas las filas
            document.querySelectorAll('tbody tr').forEach(tr => {
                tr.style.display = '';
            });
    });
</script>

<script>
    let filtroMovimientosValor = "";
    
    document.addEventListener('DOMContentLoaded', function () { 

        const filas = document.querySelectorAll('tbody tr');

        const estado = document.getElementById('filtroEstado');
        const producto = document.getElementById('filtroProducto');
        const stock = document.getElementById('filtroStock');
        const fefo = document.getElementById('filtroFefo');
        const buscar = document.getElementById('filtroBuscar');

        function filtrar() {
            const vEstado = estado.value;
            const vProducto = producto.value;
            const vStock = stock.value;
            const vFefo = fefo.value;
            const vBuscar = buscar.value.toLowerCase();
            const vMov = filtroMovimientosValor;

            filas.forEach(tr => {
                let visible = true;

                if (vEstado && tr.dataset.estado !== vEstado) visible = false;
                if (vProducto && tr.dataset.producto !== vProducto) visible = false;
                if (vStock && tr.dataset.stock !== vStock) visible = false;
                if (vFefo && tr.dataset.fefo !== vFefo) visible = false;
                if (vBuscar && !tr.dataset.texto.includes(vBuscar)) visible = false;
                if (vMov && tr.dataset.movimientos !== vMov) visible = false;

                tr.style.display = visible ? '' : 'none';
            });
        }

        [estado, producto, stock, fefo].forEach(el =>
            el.addEventListener('change', filtrar)
        );

        
        buscar.addEventListener('input', filtrar);
    });
</script>
<script>
    document.querySelectorAll('#filtroMovimientosBtn + .dropdown-menu a')
        .forEach(item => {
            item.addEventListener('click', e => {
                e.preventDefault();

                filtroMovimientosValor = item.dataset.mov || "";

                // feedback visual
                const btn = document.getElementById('filtroMovimientosBtn');
                btn.classList.toggle('activo', filtroMovimientosValor !== "");

                // aplicar filtro
                const evento = new Event('change');
                document.getElementById('filtroEstado').dispatchEvent(evento);
            });
        });
</script>

<script>
    document.querySelectorAll('.filtros-lotes select, .filtros-lotes input')
        .forEach(el => {
            el.addEventListener('change', () => {
                el.classList.toggle('filtro-activo', el.value !== '');
            });
            el.addEventListener('input', () => {
                el.classList.toggle('filtro-activo', el.value !== '');
            });
    });
</script>

@endpush