@extends('layouts.app')

@push('styles')
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
@endpush

{{-- BOTÓN ATRÁS (opcional) --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Gastos
@endsection

{{-- BOTONES DERECHA --}}
@section('header-buttons')
<button type="button" class="btn-gasto" data-bs-toggle="modal" data-bs-target="#modalNuevoGasto">
    <i class="fa-solid fa-plus"></i>
    <span class="btn-text">Nuevo gasto</span>
</button>
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('css/gastos.css') }}?v={{ filemtime(public_path('css/gastos.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
<div class="container-fluid px-3">
    <div class="card ui-card container-card my-4 gastos-panel">
        <div class="card-header gastos-panel-header">
            <div class="gastos-heading-icon"><i class="fas fa-receipt"></i></div>
            <div><span class="gastos-eyebrow">CONTROL DE EGRESOS</span><h4 class="mb-0 fw-semibold">Gastos registrados</h4><p class="mb-0">Consulta y administra los movimientos de tu caja.</p></div>
        </div>
        <div class="card-body px-4 pb-4">
            <!-- Filtros Dinámicos -->
            <form method="GET" action="{{ route('gastos.index') }}" id="filtrosGastos" class="row g-3 mb-3 filters-group">
                <div class="col-12 col-md-4">
                    <label for="filter-date" class="form-label">Fecha</label>
                    <input type="text" id="filter-date" name="fecha" value="{{ request('fecha', now()->toDateString()) }}" class="form-control" placeholder="Selecciona una fecha">
                </div>
                <div class="col-12 col-md-4">
                    <label for="filter-descripcion" class="form-label">Descripción</label>
                    <input type="text" id="filter-descripcion" name="descripcion" value="{{ request('descripcion') }}" class="form-control" placeholder="Filtrar por descripción">
                </div>
                <div class="col-12 col-md-4">
                    <label for="filter-usuario" class="form-label">Usuario</label>
                    <select id="filter-usuario" name="usuario" class="form-select">
                        <option value="">Seleccione un usuario</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((string) request('usuario') === (string) $usuario->id)>{{ $usuario->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="gastos-summary-row mb-3">
                <span class="gastos-results">{{ $gastos->total() }} {{ $gastos->total() === 1 ? 'gasto encontrado' : 'gastos encontrados' }}</span>
                <div class="gastos-total-resumen">
                    <span class="total-label">Total filtrado</span>
                    <span id="total-gastos" class="total-value">S/ {{ number_format($totalFiltrado, 2) }}</span>
                </div>
            </div>

            <!-- Tabla Gastos -->
            <div class="table-responsive ui-scroll gastos-table-wrap">
                <table class="table table-hover align-middle mb-0 ui-table gastos-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Descripción</th>
                            <th class="text-end">Monto</th>
                            <th>Método</th>
                            <th>Usuario</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody id="tabla-gastos">
                    @forelse($gastos as $gasto)
                        <tr>
                            <td data-label="Fecha" class="gastos-date">
                                <span>{{ date('d/m/Y', strtotime($gasto->fecha)) }}</span><small>{{ date('H:i', strtotime($gasto->fecha)) }}</small>
                            </td>

                            <td data-label="Descripción" class="gastos-description">
                                <strong>{{ $gasto->descripcion }}</strong>
                            </td>

                            <td data-label="Monto" class="text-end gastos-amount">
                                - S/ {{ number_format($gasto->monto, 2) }}
                            </td>

                            <td data-label="Método">
                                <span class="gastos-method text-capitalize">
                                    {{ $gasto->metodo_pago }}
                                </span>
                            </td>

                            <td data-label="Usuario" class="gastos-user">
                                {{ $gasto->usuario->nombre ?? '—' }}
                            </td>

                            <td data-label="Acciones" class="text-center">
                                @if(auth()->user()->esAdmin())
                                    <div class="d-flex justify-content-center gap-2 action-buttons">
                                        <a href="{{ route('gastos.edit', $gasto->id) }}"
                                        class="btn btn-warning btn-sm"
                                        title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </a>

                                        <form action="{{ route('gastos.destroy', $gasto->id) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('¿Anular este gasto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm"
                                                    title="Anular">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="gastos-empty-row"><td colspan="6"><i class="fas fa-receipt"></i><strong>Sin gastos para estos filtros</strong><span>Prueba con otra fecha, descripción o usuario.</span></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-3">
                {{ $gastos->links() }}
            </div>
        </div>
    </div>
</div>
@include('gastos._modal_create')
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const form               = document.getElementById("filtrosGastos");
    const inputFecha         = document.getElementById("filter-date");
    const filterDescripcion  = document.getElementById("filter-descripcion");
    const filterUsuario      = document.getElementById("filter-usuario");
    const initialDescription = filterDescripcion.value;

    // Usar flatpickr para seleccionar la fecha
    flatpickr("#filter-date", {
        dateFormat: "Y-m-d", // Formato de la fecha
        defaultDate: inputFecha.value,
        onChange: function () {
            form.requestSubmit();
        }
    });

    filterDescripcion.addEventListener("keydown", event => {
        if (event.key !== "Enter") return;
        event.preventDefault();
        form.requestSubmit();
    });
    filterDescripcion.addEventListener("blur", () => {
        if (filterDescripcion.value !== initialDescription) form.requestSubmit();
    });
    filterUsuario.addEventListener("change", () => form.requestSubmit());
});
</script>

@endpush
