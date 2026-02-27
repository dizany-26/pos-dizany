@extends('layouts.app')
@push('styles')
<link href="{{ asset('css/parametros.css') }}" rel="stylesheet" />
@endpush
{{-- BOTÓN ATRÁS --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Parametros
@endsection

{{-- BOTONES DERECHA --}}
@section('header-buttons')

@endsection

@section('content')
<div class="container py-4">
    <div class="row g-4">
        
    <!-- Sección Categorías -->
        <div class="col-md-6">
            <div class="card ui-card container-card my-4">
                <div class="card-header bg-white border-0 pb-3">
                    <div class="d-flex align-items-center justify-content-between">

                        <!-- Título -->
                        <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                            <i class="fas fa-layer-group text-primary"></i>
                            Categorías
                        </h5>

                        <!-- Buscador + Botón -->
                        <div class="d-flex align-items-center gap-3">

                            <div class="ui-search-wrapper" style="width: 260px;">
                                <i class="fas fa-search ui-search-icon"></i>
                                <input type="text"
                                    id="buscador-categoria"
                                    class="form-control ui-input ui-search-input"
                                    placeholder="Buscar categoría...">
                            </div>

                            <button class="btn-soft btn-soft-success d-flex align-items-center gap-2 px-3"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalCategoria">
                                <i class="fas fa-plus"></i>
                                Nueva
                            </button>

                        </div>

                    </div>
                </div>
                <div  class="card-body">
                    <div class="table-responsive ui-scroll" style="max-height: 500px; overflow-y:auto;">
                        <div id="table-content">

                            <table class="table table-hover align-middle mb-0 ui-table text-nowrap">   
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th class="text-start">Nombre</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-categorias">
                                    @foreach($categorias as $categoria)
                                    <tr>
                                        <td data-label="ID" class="text-center">
                                            {{ $categoria->id }}
                                        </td>

                                        <td data-label="Nombre" class="text-start fw-semibold">
                                            {{ $categoria->nombre }}
                                        </td>

                                        <td data-label="Acciones" class="text-center">
                                            <div class="d-flex justify-content-center gap-1 action-buttons">

                                                <button type="button"
                                                        class="btn-soft btn-soft-warning btn-soft-icon btn-editar-categoria"
                                                        data-id="{{ $categoria->id }}"
                                                        data-nombre="{{ $categoria->nombre }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEditarCategoria">
                                                    <i class="fas fa-pen"></i>
                                                </button>

                                                <button type="button"
                                                        class="btn-soft btn-soft-danger btn-soft-icon btn-eliminar-categoria"
                                                        data-id="{{ $categoria->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>

                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach

                                    @if($categorias->isEmpty())
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            Sin categorías registradas.
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección Marcas -->
        <div class="col-md-6">
            <div class="card ui-card container-card my-4">
                <div class="card-header bg-white border-0 pb-3">
                    <div class="d-flex align-items-center justify-content-between">

                        <!-- Título -->
                        <h5 class="mb-0 fw-semibold d-flex align-items-center gap-2">
                            <i class="fas fa-tags text-primary"></i>
                            Marcas
                        </h5>

                        <!-- Buscador + Botón -->
                        <div class="d-flex align-items-center gap-3">

                            <div class="ui-search-wrapper" style="width: 260px;">
                                <i class="fas fa-search ui-search-icon"></i>
                                <input type="text"
                                    id="buscador-marca"
                                    class="form-control ui-input ui-search-input"
                                    placeholder="Buscar marca...">
                            </div>

                            <button class="btn-soft btn-soft-success d-flex align-items-center gap-2 px-3"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalMarca">
                                <i class="fas fa-plus"></i>
                                Nueva
                            </button>

                        </div>

                    </div>
                </div>
                <div  class="card-body">
                    <div class="table-responsive ui-scroll" style="max-height: 400px; overflow-y:auto;">
                        <div id="table-content">

                            <table class="table table-hover align-middle mb-0 ui-table text-nowrap">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th class="text-start">Nombre</th>
                                        <th class="text-start">Descripción</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-marca">
                                    @foreach($marcas as $marca)
                                    <tr>

                                        <td data-label="ID" class="text-center">
                                            {{ $marca->id }}
                                        </td>

                                        <td data-label="Nombre" class="text-start fw-semibold">
                                            {{ $marca->nombre }}
                                        </td>

                                        <td data-label="Descripción" class="text-start">
                                            {{ $marca->descripcion }}
                                        </td>

                                        <td data-label="Acciones" class="text-center">
                                            <div class="d-flex justify-content-center gap-1 action-buttons">

                                                <button type="button"
                                                        class="btn-soft btn-soft-warning btn-soft-icon btn-editar-marca"
                                                        data-id="{{ $marca->id }}"
                                                        data-nombre="{{ $marca->nombre }}"
                                                        data-descripcion="{{ $marca->descripcion }}"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modalEditarMarca">
                                                    <i class="fas fa-pen"></i>
                                                </button>

                                                <button type="button"
                                                        class="btn-soft btn-soft-danger btn-soft-icon btn-eliminar-marca"
                                                        data-id="{{ $marca->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>

                                            </div>
                                        </td>

                                    </tr>
                                    @endforeach

                                    @if($marcas->isEmpty())
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            Sin marcas registradas.
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Categoría -->
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form action="{{ route('parametros.categorias.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Registrar Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="nombre_categoria" class="form-control" required>
                <small id="alerta-categoria" class="text-danger  d-block mt-1"></small>

            </div>
            <div class="modal-footer">
                <button id="btn-guardar-categoria" class="btn-soft btn-soft-success btn-soft-icon px-5" type="submit">Guardar</button>
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditarCategoria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form method="POST" id="formEditarCategoria" class="modal-content">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">Editar Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="edit_nombre_categoria" class="form-control" required>
            </div>

            <div class="modal-footer">
                <button class="btn-soft btn-soft-success" type="submit">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Nueva Marca -->
<div class="modal fade" id="modalMarca" tabindex="-1" aria-labelledby="modalMarcaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form action="{{ route('parametros.marcas.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Registrar Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="nombre_marca" class="form-control" required>
                <small id="alerta-marca" class="text-danger  d-block mt-1"></small>

                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"></textarea>
            </div>
            <div class="modal-footer">
                <button id="btn-guardar-marca" class="btn-soft btn-soft-success btn-soft-icon px-5" type="submit">Guardar</button>
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditarMarca" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form method="POST" id="formEditarMarca" class="modal-content">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">Editar Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" id="edit_nombre_marca" class="form-control" required>

                <label class="form-label mt-2">Descripción</label>
                <textarea name="descripcion" id="edit_descripcion_marca" class="form-control" rows="2"></textarea>
            </div>

            <div class="modal-footer">
                <button class="btn-soft btn-soft-success" type="submit">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function eliminarElemento(url, mensajeConfirmacion, mensajeExito) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: mensajeConfirmacion,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.exito) {
                        Swal.fire({
                            icon: 'success',
                            title: mensajeExito,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.mensaje || 'No se pudo eliminar.'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error inesperado.'
                    });
                });
            }
        });
    }

    // Eliminar Marca
    document.querySelectorAll('.btn-eliminar-marca').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            eliminarElemento(`/marcas/${id}`, 'La marca será eliminada permanentemente.', 'Marca eliminada correctamente');
        });
    });

    // Eliminar Categoría
    document.querySelectorAll('.btn-eliminar-categoria').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            eliminarElemento(`/categorias/${id}`, 'La categoría será eliminada permanentemente.', 'Categoría eliminada correctamente');
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', () => {

        function activarBuscador(inputId, tablaId) {

            const input = document.getElementById(inputId);

            input.addEventListener('input', () => {

                const filtro = input.value.toLowerCase();
                const filas = document.querySelectorAll(`#${tablaId} tr`);

                filas.forEach(fila => {
                    const texto = fila.textContent.toLowerCase();
                    fila.style.display = texto.includes(filtro) ? '' : 'none';
                });

            });
        }

        activarBuscador('buscador-categoria', 'tabla-categorias');
        activarBuscador('buscador-marca', 'tabla-marca');

    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const inputCategoria = document.getElementById('nombre_categoria');
        const btnGuardarCategoria = document.getElementById('btn-guardar-categoria');
        const alertaCategoria = document.getElementById('alerta-categoria');

        inputCategoria.addEventListener('input', () => {
            const nombre = inputCategoria.value.trim();

            if (nombre.length < 2) {
                alertaCategoria.textContent = '';
                btnGuardarCategoria.disabled = true;
                return;
            }

            fetch(`/validar-categoria?nombre=${encodeURIComponent(nombre)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.existe) {
                        alertaCategoria.textContent = '❌ Ya existe una categoría con ese nombre';
                        alertaCategoria.classList.add('text-danger');
                        btnGuardarCategoria.disabled = true;
                    } else {
                        alertaCategoria.textContent = '✅ Nombre válido';
                        alertaCategoria.classList.remove('text-danger');
                        alertaCategoria.classList.add('text-success');
                        btnGuardarCategoria.disabled = false;
                    }
                })
                .catch(() => {
                    alertaCategoria.textContent = '⚠️ Error al validar';
                    alertaCategoria.classList.add('text-danger');
                    btnGuardarCategoria.disabled = true;
                });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const inputMarca = document.getElementById('nombre_marca');
    const btnGuardarMarca = document.getElementById('btn-guardar-marca');
    const alertaMarca = document.getElementById('alerta-marca');

    inputMarca.addEventListener('input', () => {
        const nombre = inputMarca.value.trim();

        if (nombre.length < 2) {
            alertaMarca.textContent = '';
            btnGuardarMarca.disabled = true;
            return;
        }

        fetch(`/validar-marca?nombre=${encodeURIComponent(nombre)}`)
            .then(res => res.json())
            .then(data => {
                if (data.existe) {
                    alertaMarca.textContent = '❌ Ya existe una marca con ese nombre';
                    alertaMarca.classList.add('text-danger');
                    alertaMarca.classList.remove('text-success');
                    btnGuardarMarca.disabled = true;
                } else {
                    alertaMarca.textContent = '✅ Nombre válido';
                    alertaMarca.classList.remove('text-danger');
                    alertaMarca.classList.add('text-success');
                    btnGuardarMarca.disabled = false;
                }
            })
            .catch(() => {
                alertaMarca.textContent = '⚠️ Error al validar';
                alertaMarca.classList.add('text-danger');
                alertaMarca.classList.remove('text-success');
                btnGuardarMarca.disabled = true;
            });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalCategoria = document.getElementById('modalCategoria');
        const modalMarca = document.getElementById('modalMarca');

        const inputCategoria = document.getElementById('nombre_categoria');
        const alertaCategoria = document.getElementById('alerta-categoria');
        const btnGuardarCategoria = document.getElementById('btn-guardar-categoria');

        const inputMarca = document.getElementById('nombre_marca');
        const alertaMarca = document.getElementById('alerta-marca');
        const btnGuardarMarca = document.getElementById('btn-guardar-marca');

        // Al cerrar modal categoría
        modalCategoria.addEventListener('hidden.bs.modal', function () {
            inputCategoria.value = '';
            alertaCategoria.textContent = '';
            alertaCategoria.classList.remove('text-danger', 'text-success');
            btnGuardarCategoria.disabled = true;
        });

        // Al cerrar modal marca
        modalMarca.addEventListener('hidden.bs.modal', function () {
            inputMarca.value = '';
            alertaMarca.textContent = '';
            alertaMarca.classList.remove('text-danger', 'text-success');
            btnGuardarMarca.disabled = true;
        });
    });
</script>
<script>
    // Editar Categoría
    document.querySelectorAll('.btn-editar-categoria').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;

            document.getElementById('edit_nombre_categoria').value = nombre;
            document.getElementById('formEditarCategoria').action = `/categorias/${id}`;
        });
    });

    // Editar Marca
    document.querySelectorAll('.btn-editar-marca').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            const descripcion = this.dataset.descripcion;

             console.log("Nombre recibido:", nombre);

            document.getElementById('edit_nombre_marca').value = nombre;
            document.getElementById('edit_descripcion_marca').value = descripcion;
            document.getElementById('formEditarMarca').action = `/marcas/${id}`;
        });
    });

</script>

@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
            toast: true,
            icon: 'success',
            title: '{{ session('success') }}',
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
        });
    });
</script>
@endif

@endpush