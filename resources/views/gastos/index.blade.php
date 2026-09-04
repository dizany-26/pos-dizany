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
<a href="{{ route('gastos.create') }}" class="btn-gasto">
    <i class="fa-solid fa-plus"></i>
    <span class="btn-text">Nuevo gasto</span>
</a>
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('css/gastos.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
<div class="container-fluid px-3">
    <div class="card ui-card container-card my-4">
        <div class="card-header text-center pt-4">
            <h4 class="mb-0 fw-semibold">
                <i class="fas fa-money-bill-wave me-2 text-primary"></i>
                Lista de Gastos
            </h4>
        </div>
        <div class="card-body px-4 pb-4">
            <!-- Filtros Dinámicos -->
            <form method="GET" action="{{ route('gastos.index') }}" id="filtrosGastos" class="row g-3 mb-3 filters-group">
                <div class="col-12 col-md-4">
                    <label for="filter-date" class="form-label">: Por Fecha:</label>
                    <input type="text" id="filter-date" name="fecha" value="{{ request('fecha', now()->toDateString()) }}" class="form-control" placeholder="Selecciona una fecha">
                </div>
                <div class="col-12 col-md-4">
                    <label for="filter-descripcion" class="form-label">: Por Descripción:</label>
                    <input type="text" id="filter-descripcion" name="descripcion" value="{{ request('descripcion') }}" class="form-control" placeholder="Filtrar por descripción">
                </div>
                <div class="col-12 col-md-4">
                    <label for="filter-usuario" class="form-label">: Por Usuario:</label>
                    <select id="filter-usuario" name="usuario" class="form-select">
                        <option value="">Seleccione un usuario</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" @selected((string) request('usuario') === (string) $usuario->id)>{{ $usuario->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <!-- Mensaje de no encontrados -->
            <div id="no-gastos-msg" class="alert alert-warning d-none">
                No se encontraron gastos para los filtros aplicados.
            </div>

            <div class="d-flex justify-content-end mb-3">
                <div class="gastos-total-resumen">
                    <span class="total-label">Total del día</span>
                    <span id="total-gastos" class="total-value">S/ 0.00</span>
                </div>
            </div>

            <!-- Tabla Gastos -->
            <div class="table-responsive ui-scroll">
                <table class="table table-hover align-middle mb-0 ui-table text-nowrap">
                    <thead class="table-light">
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
                    @foreach($gastos as $gasto)
                        <tr>
                            <td class="text-muted">
                                {{ date('d/m/Y H:i', strtotime($gasto->fecha)) }}
                            </td>

                            <td>
                                <strong>{{ $gasto->descripcion }}</strong>
                            </td>

                            <td class="text-end text-danger fw-semibold">
                                - S/ {{ number_format($gasto->monto, 2) }}
                            </td>

                            <td>
                                <span class="badge bg-secondary text-capitalize">
                                    {{ $gasto->metodo_pago }}
                                </span>
                            </td>

                            <td>
                                {{ $gasto->usuario->nombre ?? '—' }}
                            </td>

                            <td class="text-center">
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
                    @endforeach
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
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const form               = document.getElementById("filtrosGastos");
    const inputFecha         = document.getElementById("filter-date");
    const filterDescripcion  = document.getElementById("filter-descripcion");
    const filterUsuario      = document.getElementById("filter-usuario");
    const totalGastosEl      = document.getElementById("total-gastos");
    const initialDescription = filterDescripcion.value;

    // Usar flatpickr para seleccionar la fecha
    flatpickr("#filter-date", {
        dateFormat: "Y-m-d", // Formato de la fecha
        defaultDate: inputFecha.value,
        onChange: function () {
            form.requestSubmit();
        }
    });

    // Suma los montos de las filas visibles
    function calcularTotal() {
        let suma = 0;
        document.querySelectorAll("#tabla-gastos tr").forEach(row => {
            if (row.style.display !== 'none') {
                const texto = row.cells[2].textContent.replace(/[^\d.]/g, '');
                suma += parseFloat(texto) || 0;
            }
        });
        totalGastosEl.textContent = `S/ ${suma.toFixed(2)}`;
    }

    filterDescripcion.addEventListener("keydown", event => {
        if (event.key !== "Enter") return;
        event.preventDefault();
        form.requestSubmit();
    });
    filterDescripcion.addEventListener("blur", () => {
        if (filterDescripcion.value !== initialDescription) form.requestSubmit();
    });
    filterUsuario.addEventListener("change", () => form.requestSubmit());
    calcularTotal();
});
</script>

@endpush
