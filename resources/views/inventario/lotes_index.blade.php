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

    .fefo-empty-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 25px;
        border: 1px solid #d69e5b;
        border-radius: 7px;
        color: #a85d16;
        background: linear-gradient(145deg, #fff4d6, #e9bd78);
        box-shadow: 0 2px 4px rgba(120, 72, 20, .22);
        font-size: 15px;
    }

    .lot-provider {
        width: 150px;
        max-width: 150px;
        line-height: 1.35;
    }

    .lot-provider-name {
        display: block;
        overflow-wrap: anywhere;
    }

    .lot-provider-toggle {
        display: inline-flex;
        margin-top: 3px;
        padding: 0;
        border: 0;
        color: #1677ff;
        background: transparent;
        font-size: 11px;
        font-weight: 700;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .lot-provider-toggle:hover {
        color: #075bbb;
    }

    .lot-product {
        width: 235px;
        max-width: 235px;
        line-height: 1.35;
    }

    .lot-product-description {
        display: block;
        margin-top: 3px;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .lot-product-toggle {
        display: inline-flex;
        margin-top: 3px;
        padding: 0;
        border: 0;
        color: #1677ff;
        background: transparent;
        font-size: 11px;
        font-weight: 700;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .lot-product-toggle:hover {
        color: #075bbb;
    }

    /* La tabla conserva todas sus columnas y se desplaza en pantallas
       horizontales en lugar de recortarse contra el borde del contenido. */
    .table-responsive.ui-scroll {
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: auto !important;
        /* En móviles horizontales 100vh puede ser menor a 355px. La altura
           mínima evita que el cuerpo de la tabla colapse y oculte las filas. */
        max-height: clamp(240px, calc(100vh - 355px), 620px);
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x pan-y;
        scrollbar-gutter: stable;
    }

    .table-responsive.ui-scroll > .tabla-scroll {
        width: max-content;
        min-width: 1050px;
    }

    .table-responsive.ui-scroll > .tabla-scroll > table {
        width: 100%;
        min-width: 1050px;
    }

    .table-responsive.ui-scroll .ui-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
    }

    @media (max-width: 768px) and (orientation: portrait) {
        .table-responsive.ui-scroll {
            overflow-x: visible !important;
            overflow-y: visible !important;
            max-height: none;
            scrollbar-gutter: auto;
        }

        .table-responsive.ui-scroll > .tabla-scroll,
        .table-responsive.ui-scroll > .tabla-scroll > table {
            width: 100%;
            min-width: 0;
        }

        .table-responsive.ui-scroll .ui-table tr {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 12px;
            padding: 14px;
            margin-bottom: 14px;
        }

        .table-responsive.ui-scroll .ui-table td {
            min-width: 0;
            min-height: 54px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            gap: 4px;
            padding: 9px 4px;
            border-bottom: 1px dashed rgba(127, 151, 181, .22);
            text-align: left !important;
        }

        .table-responsive.ui-scroll .ui-table td::before {
            flex: none;
            color: #7d8da4;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .lot-product,
        .lot-provider {
            width: 100%;
            max-width: none;
        }

        .table-responsive.ui-scroll .ui-table td[data-label="Producto"] {
            grid-column: 1 / -1;
            grid-row: 1;
            min-height: 72px;
            padding: 10px 12px;
            border: 1px solid rgba(22, 119, 232, .2);
            border-radius: 12px;
            background: rgba(22, 119, 232, .07);
        }

        .table-responsive.ui-scroll .ui-table td[data-label="Producto"]::before {
            color: #1677e8;
        }

        .table-responsive.ui-scroll .ui-table td[data-label="Proveedor"] {
            grid-column: 1 / -1;
        }

        .table-responsive.ui-scroll .ui-table td[data-label="Acciones"] {
            grid-column: 1 / -1;
            min-height: auto;
            margin-top: 0;
            padding: 12px 0 0;
            border-bottom: 0;
            align-items: flex-end;
        }

        .lot-product-content,
        .lot-provider-content {
            min-width: 0;
            width: 100%;
            max-width: none;
            margin-left: 0;
            text-align: left;
            overflow-wrap: anywhere;
        }

        .lot-product-toggle,
        .lot-provider-toggle {
            justify-content: flex-start;
        }

        .table-responsive.ui-scroll .ui-table td[data-label="Acciones"] .acciones-lote {
            justify-content: flex-end;
        }

        :root[data-theme='dark'] .table-responsive.ui-scroll .ui-table td[data-label="Producto"] {
            border-color: rgba(82, 156, 245, .3);
            background: rgba(22, 119, 232, .12);
        }
    }

    @media (max-height: 520px) and (orientation: landscape) {
        .table-responsive.ui-scroll {
            max-height: 240px;
        }
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
                <form method="GET" action="{{ route('inventario.lotes') }}" id="formFiltrosLotes" class="px-2 mb-4 filtros-lotes">
                    <div class="row mb-3 g-2">
                        <div class="col-md-2">
                            <select name="estado" id="filtroEstado" class="form-select ui-input">
                                <option value="">Estado</option>
                                <option value="vencido" @selected(request('estado') === 'vencido')>Vencidos</option>
                                <option value="10" @selected(request('estado') === '10')>Vence ≤ 10 días</option>
                                <option value="30" @selected(request('estado') === '30')>Vence entre 11 y 30 días</option>
                                <option value="ok" @selected(request('estado') === 'ok')>Vigentes</option>
                                <option value="sin" @selected(request('estado') === 'sin')>Sin vencimiento</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select name="producto_id" id="filtroProducto" class="form-select ui-input">
                                <option value="">Producto</option>
                                @foreach ($productos as $producto)
                                    <option value="{{ $producto->id }}" @selected((string) request('producto_id') === (string) $producto->id)>
                                        {{ $producto->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select name="stock" id="filtroStock" class="form-select ui-input">
                                <option value="">Stock</option>
                                <option value="con" @selected(request('stock') === 'con')>Con stock</option>
                                <option value="sin" @selected(request('stock') === 'sin')>Sin stock</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select name="fefo" id="filtroFefo" class="form-select ui-input">
                                <option value="">FEFO</option>
                                <option value="1" @selected(request('fefo') === '1')>Prioridad FEFO</option>
                            </select>
                        </div>

                        <div class="col-auto">
                            <div class="dropdown">
                                <button
                                    id="filtroMovimientosBtn"
                                    class="btn-soft btn-soft-primary btn-soft-icon {{ request()->filled('movimientos') ? 'activo' : '' }}"
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
                                <input type="hidden" name="movimientos" id="filtroMovimientos" value="{{ request('movimientos') }}">
                            </div>
                        </div>

                        <div class="col-md-3 d-flex align-items-center gap-1">
                            <input type="search" name="buscar" id="filtroBuscar" class="form-control ui-input"
                                value="{{ request('buscar') }}" placeholder="Buscar lote, producto o proveedor…">

                                <button type="submit" class="btn-soft btn-soft-primary btn-soft-icon" title="Buscar en todos los lotes">
                                    <i class="fas fa-search"></i>
                                </button>

                                <a href="{{ route('inventario.lotes') }}"
                                        class="btn-soft btn-soft-info btn-soft-icon"
                                        title="Limpiar filtros">
                                    <i class="fas fa-times"></i>
                                </a>
                        </div>
                    </div>
                </form>

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
                        @forelse ($lotes as $lote)
                            @php
                                // =========================
                                // FEFO POR PRODUCTO
                                // =========================
                                if ($lote->stock_actual <= 0) {
                                    $fefoIcon = '<span class="fefo-empty-box" title="Lote agotado" aria-label="Lote agotado"><i class="fas fa-box-open"></i></span>';
                                    $prioridadFefo = null;
                                } else {
                                    $prioridadFefo = $prioridadesFefo->get($lote->id);
                                }

                                if ($lote->stock_actual <= 0) {
                                    // Un lote agotado ya no participa en la prioridad FEFO.
                                } elseif ($lote->fecha_vencimiento && \Carbon\Carbon::parse($lote->fecha_vencimiento)->isPast()) {
                                    $fefoIcon = '<i class="fas fa-times-circle text-danger" title="Lote vencido"></i>';
                                } elseif ($prioridadFefo === 1) {
                                    $fefoIcon = '<i class="fas fa-circle text-success" title="Primer lote en salir (FEFO)"></i>';
                                } elseif ($prioridadFefo === 2) {
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

                            <tr>
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
                                <td data-label="Producto" class="lot-product">
                                    @php
                                        $descripcionProducto = trim((string) ($lote->producto->descripcion ?? ''));
                                        $descripcionEsLarga = mb_strlen($descripcionProducto) > 50;
                                    @endphp
                                    <div class="lot-product-content">
                                        <strong>{{ $lote->producto->nombre ?? '—' }}</strong>
                                        @if($descripcionProducto !== '')
                                            <small class="text-muted lot-product-description lot-product-short">
                                                {{ $descripcionEsLarga ? \Illuminate\Support\Str::limit($descripcionProducto, 50) : $descripcionProducto }}
                                            </small>
                                            @if($descripcionEsLarga)
                                                <small class="text-muted lot-product-description lot-product-full d-none">
                                                    {{ $descripcionProducto }}
                                                </small>
                                                <button type="button" class="lot-product-toggle" aria-expanded="false">
                                                    Ver más
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>

                                {{-- PROVEEDOR --}}
                                <td data-label="Proveedor" class="lot-provider">
                                    @php
                                        $nombreProveedor = $lote->proveedor->nombre ?? '—';
                                        $proveedorEsLargo = mb_strlen($nombreProveedor) > 28;
                                    @endphp
                                    <div class="lot-provider-content">
                                        <span class="lot-provider-name lot-provider-short">
                                            {{ $proveedorEsLargo ? \Illuminate\Support\Str::limit($nombreProveedor, 28) : $nombreProveedor }}
                                        </span>
                                        @if ($proveedorEsLargo)
                                            <span class="lot-provider-name lot-provider-full d-none">
                                                {{ $nombreProveedor }}
                                            </span>
                                            <button type="button"
                                                class="lot-provider-toggle"
                                                aria-expanded="false">
                                                Ver más
                                            </button>
                                        @endif
                                    </div>
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

            @if($lotes->hasPages() || $lotes->total() > 0)
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 px-2 pt-3">
                    <small class="text-muted">
                        Mostrando {{ $lotes->firstItem() ?? 0 }}–{{ $lotes->lastItem() ?? 0 }} de {{ $lotes->total() }} lotes
                    </small>
                    {{ $lotes->links('pagination::simple-bootstrap-4') }}
                </div>
            @endif

        </div>

</div>
@endsection

{{-- ===================== SCRIPTS ===================== --}}
@push('scripts')

<script>
    document.addEventListener('click', function (event) {
        const productButton = event.target.closest('.lot-product-toggle');
        if (productButton) {
            event.preventDefault();
            event.stopPropagation();

            const cell = productButton.closest('.lot-product');
            const shortDescription = cell?.querySelector('.lot-product-short');
            const fullDescription = cell?.querySelector('.lot-product-full');
            if (!shortDescription || !fullDescription) return;

            const expanded = productButton.getAttribute('aria-expanded') === 'true';

            shortDescription.classList.toggle('d-none', !expanded);
            fullDescription.classList.toggle('d-none', expanded);
            productButton.setAttribute('aria-expanded', String(!expanded));
            productButton.textContent = expanded ? 'Ver más' : 'Ver menos';
            return;
        }

        const button = event.target.closest('.lot-provider-toggle');
        if (!button) return;

        event.preventDefault();
        event.stopPropagation();

        const cell = button.closest('.lot-provider');
        const shortName = cell?.querySelector('.lot-provider-short');
        const fullName = cell?.querySelector('.lot-provider-full');
        if (!shortName || !fullName) return;

        const expanded = button.getAttribute('aria-expanded') === 'true';

        shortName.classList.toggle('d-none', !expanded);
        fullName.classList.toggle('d-none', expanded);
        button.setAttribute('aria-expanded', String(!expanded));
        button.textContent = expanded ? 'Ver más' : 'Ver menos';
    }, true);
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formFiltrosLotes');
        form.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => form.submit());
            select.classList.toggle('filtro-activo', select.value !== '');
        });
    });
</script>
<script>
    document.querySelectorAll('#filtroMovimientosBtn + .dropdown-menu a')
        .forEach(item => {
            item.addEventListener('click', e => {
                e.preventDefault();

                document.getElementById('filtroMovimientos').value = item.dataset.mov || '';
                document.getElementById('formFiltrosLotes').submit();
            });
        });
</script>

@endpush
