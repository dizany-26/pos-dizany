@extends('layouts.app')

@push('styles')
   
    <link href="{{ asset('css/usuarios.css') }}" rel="stylesheet" />
@endpush

{{-- BOTÓN ATRÁS (opcional) --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Usuarios
@endsection

{{-- BOTONES DERECHA --}}
@section('header-buttons')
<button class="btn-gasto"
        type="button"
        data-bs-toggle="modal"
        data-bs-target="#modalNuevoUsuario">
    <i class="fa-solid fa-plus"></i>
    <span class="btn-text">Nuevo usuario</span>
</button>
@endsection


@section('content')
<!-- alerta de confirmacion -->
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    <div class="card users-card mx-auto my-4">
    
        {{-- HEADER LIMPIO --}}
        <div class="card-header bg-transparent border-0 text-center pt-4">
            <h4 class="mb-0 fw-semibold">
                <i class="fa-solid fa-users me-2 text-primary"></i>
                Lista de Usuarios
            </h4>
        </div>

        <div class="card-body px-4 pb-4">

            {{-- BUSCADOR + EXPORTAR --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

                <div class="flex-grow-1" style="max-width: 400px;">
                    <input type="text"
                        id="buscadorUsuarios"
                        class="form-control users-search"
                        placeholder="Buscar por nombre, usuario o rol...">
                </div>

                <a href="{{ route('usuarios.exportarExcel') }}"
                class="btn btn-success users-export-btn">
                    <i class="fa-solid fa-file-excel me-1"></i>
                    Exportar Excel
                </a>

            </div>

            {{-- TABLA --}}
            <div class="table-responsive ui-scroll" style="max-height: 500px; overflow-y:auto;">
                <table id="tablaUsuarios"
                    class="table table-hover align-middle mb-0 ui-table text-nowrap">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($usuarios as $usuario)
                            <tr>
                                <td>{{ $usuario->id }}</td>
                                <td class="fw-semibold">{{ $usuario->nombre }}</td>
                                <td>{{ $usuario->usuario }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $usuario->rol->nombre ?? 'Sin rol' }}
                                    </span>
                                </td>

                                <td>
                                    <div class="d-flex justify-content-center gap-2 action-buttons">

                                        {{-- EDITAR --}}
                                        <button type="button"
                                            class="btn btn-warning btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditarUsuario"
                                            data-id="{{ $usuario->id }}"
                                            data-nombre="{{ $usuario->nombre }}"
                                            data-usuario="{{ $usuario->usuario }}"
                                            data-email="{{ $usuario->email }}"
                                            data-rol="{{ $usuario->rol_id }}">
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        {{-- ELIMINAR --}}
                                        <form action="{{ route('usuarios.destroy', $usuario->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('¿Estás seguro de eliminar este usuario?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>

                                        {{-- CAMBIAR CLAVE --}}
                                        <button class="btn btn-primary btn-sm cambiar-clave-btn"
                                                data-id="{{ $usuario->id }}"
                                                data-nombre="{{ $usuario->nombre }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalCambiarClave">
                                            <i class="fa-solid fa-key"></i>
                                        </button>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

        </div>
    </div>

<!-- Modal para Agregar Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form method="POST" action="{{ route('usuarios.store') }}" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control ui-input" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="usuario" class="form-control ui-input" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control ui-input" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control ui-input" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <select name="rol_id" class="form-select ui-input" required>
                        <option value="" disabled selected>Seleccione un rol</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">
                    Cerrar
                </button>

                <button type="submit" class="btn-soft btn-soft-primary">
                    Guardar cambios
                </button>
            </div>

        </form>
    </div>
</div>

<!-- Modal para Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form method="POST" id="formEditarUsuario" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="editar-id">
                
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" id="editar-nombre" class="form-control ui-input" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="usuario" id="editar-usuario" class="form-control ui-input" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo Electrónico</label>
                    <input type="email" name="email" id="editar-email" class="form-control ui-input" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <select name="rol_id" id="editar-rol" class="form-select ui-input" required>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">
                    Cerrar
                </button>
                <button type="submit" class="btn-soft btn-soft-primary">Actualizar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cambiar Clave -->
<div class="modal fade" id="modalCambiarClave" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form method="POST" action="{{ route('usuarios.cambiarClave') }}" class="modal-content">
            @csrf
            <input type="hidden" name="usuario_id" id="usuario_id_cambiar_clave">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p id="nombre_usuario_label"></p>
                <div class="mb-3">
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" name="nueva_clave" class="form-control ui-input" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn-soft btn-soft-primary">Actualizar</button>
            </div>
        </form>
    </div>
</div>


@endsection

@push('scripts')
<!-- Bootstrap JS con Popper incluido -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/usuarios.js') }}"></script>
    
    
@endpush
