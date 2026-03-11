@extends('layouts.app')

@push('styles')

    <link href="{{ asset('css/clientes.css') }}" rel="stylesheet" />
@endpush

{{-- BOTÓN ATRÁS (opcional) --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Clientes
@endsection

{{-- BOTONES DERECHA --}}
@section('header-buttons')
{{-- vacio --}}
@endsection

@section('content')
<div class="card ui-card container-card my-4">

        <div class="card-header text-center pt-4">
            <h4 class="mb-0 fw-semibold">
                <i class="fas fa-user-friends me-2 text-primary"></i>
                Lista de Clientes ROBUSTOS
            </h4>
        </div>

    <div class="card-body px-4 pb-4">

            {{-- BUSCADOR CENTRADO --}}
            <div class="d-flex justify-content-center mb-4">
                <div class="ui-search-wrapper" style="max-width: 350px; width:100%;">
                    <i class="fas fa-search ui-search-icon"></i>
                    <input type="text"
                        name="search"
                        id="search"
                        class="form-control ui-input ui-search-input"
                        placeholder="Buscar cliente..."
                        value="{{ request()->query('search') }}">
                </div>
            </div>

            {{-- TABLA --}}
            <div class="table-responsive ui-scroll" style="max-height: 500px; overflow-y:auto;">
                <div id="table-content">

                    <table class="table table-hover align-middle mb-0 ui-table text-nowrap">

                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Dirección</th>
                                <th>Teléfono</th>
                                <th>RUC</th>
                                <th>DNI</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($clientes as $cliente)
                            <tr>
                                <td data-label="ID">{{ $cliente->id }}</td>

                                <td data-label="Nombre" class="fw-semibold">
                                    {{ $cliente->nombre }}
                                </td>

                                <td data-label="Dirección">
                                    {{ $cliente->direccion ?? 'No disponible' }}
                                </td>

                                <td data-label="Teléfono">
                                    {{ $cliente->telefono ?? 'No disponible' }}
                                </td>

                                <td data-label="RUC">
                                    {{ $cliente->ruc ?? 'No disponible' }}
                                </td>

                                <td data-label="DNI">
                                    {{ $cliente->dni ?? 'No disponible' }}
                                </td>

                                <td data-label="Acciones">
                                    <div class="d-flex justify-content-center action-buttons">

                                        <a href="javascript:void(0);"
                                            class="btn-soft btn-soft-warning btn-soft-icon btn-edit"
                                            data-id="{{ $cliente->id }}">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- PAGINACIÓN --}}
                    <div class="d-flex justify-content-center mt-4">
                        {{ $clientes->appends(['search' => request()->query('search')])->links() }}
                    </div>

                </div>
            </div>
    </div>
</div>
<!-- Modal de Edición de Cliente -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editClientForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="client_id" name="client_id">
                    <div class="mb-3">
                        <label for="client_name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="client_name" name="client_name">
                    </div>
                    <div class="mb-3">
                        <label for="client_address" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="client_address" name="client_address">
                    </div>
                    <div class="mb-3">
                        <label for="client_phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="client_phone" name="client_phone">
                    </div>
                    <div class="mb-3">
                        <label for="client_ruc" class="form-label">RUC</label>
                        <input type="text" class="form-control" id="client_ruc" name="client_ruc">
                    </div>
                    <div class="mb-3">
                        <label for="client_dni" class="form-label">DNI</label>
                        <input type="text" class="form-control" id="client_dni" name="client_dni">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">
                            Cerrar
                        </button>

                        <button type="submit" class="btn-soft btn-soft-primary">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Detectar cambios en el campo de búsqueda
    $('#search').on('keyup', function() {
        var query = $(this).val();

        // Realizar la solicitud AJAX
        $.ajax({
            url: '{{ route('clientes.index') }}',
            method: 'GET',
            data: { search: query },
            success: function(response) {
                // Actualizar solo el contenido de la tabla con los nuevos resultados
                $('#table-content').html($(response).find('#table-content').html());
            }
        });
    });
});
</script>
<script>
    $(document).ready(function() {
    // Detectar el clic en el botón "Editar"
    $(document).on('click', '.btn-edit', function() {
        var clientId = $(this).data('id'); // Obtener el ID del cliente desde el atributo data-id
        
        // Realizar una solicitud AJAX para obtener los datos del cliente
        $.ajax({
            url: '/clientes/' + clientId + '/edit', // Ruta para obtener los datos del cliente
            method: 'GET',
            success: function(response) {
                // Rellenar los campos del modal con los datos del cliente
                $('#client_id').val(response.id);
                $('#client_name').val(response.nombre);
                $('#client_address').val(response.direccion);
                $('#client_phone').val(response.telefono);
                $('#client_ruc').val(response.ruc);
                $('#client_dni').val(response.dni);

                // Mostrar el modal con los datos del cliente
                $('#editModal').modal('show');
            },
            error: function() {
                alert("Error al obtener los datos del cliente.");
            }
        });
    });

    // Enviar el formulario de edición con AJAX para actualizar los datos
    $('#editClientForm').on('submit', function(e) {
        e.preventDefault(); // Prevenir que el formulario se envíe de forma tradicional

        var clientId = $('#client_id').val(); // Obtener el ID del cliente
        var formData = $(this).serialize(); // Obtener todos los datos del formulario

        // Realizar la solicitud AJAX para actualizar los datos
        $.ajax({
            url: '/clientes/' + clientId, // Ruta para actualizar los datos del cliente
            method: 'PUT',
            data: formData, // Enviar los datos del formulario
            success: function(response) {
                // Si la respuesta es exitosa, mostrar un SweetAlert de éxito
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Cliente actualizado correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then((result) => {
                    // Después de cerrar el SweetAlert, cerrar el modal y recargar la página
                    if (result.isConfirmed) {
                        $('#editModal').modal('hide');
                        location.reload(); // Recargar la página o la tabla de clientes
                    }
                });
            },
            error: function(xhr, status, error) {
                alert('Error al actualizar el cliente. Inténtalo nuevamente.');
            }
        });
    });
});

</script>
@endpush
