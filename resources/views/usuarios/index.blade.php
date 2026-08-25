@extends('layouts.app')

@php
    $catalogoPermisos = [
        'INICIO' => [
            'dashboard.admin' => 'Dashboard administrador',
            'dashboard.empleado' => 'Dashboard empleado',
        ],
        'GESTIÓN' => [
            'usuarios' => 'Usuarios',
            'clientes' => 'Clientes',
            'proveedores' => 'Proveedores',
        ],
        'INVENTARIO' => [
            'productos' => 'Productos',
            'productos.create' => 'Nuevo producto',
            'inventario.resumen' => 'Resumen inventario',
            'parametros.productos' => 'Parámetros de productos',
            'inventario.lote' => 'Ingreso de lotes',
        ],
        'OPERACIONES' => [
            'ventas' => 'Ventas',
            'movimientos' => 'Movimientos',
            'gastos' => 'Gastos',
        ],
        'CATÁLOGO WEB' => [
            'catalogo.ver' => 'Vista catálogo',
            'catalogo.config' => 'Configurar catálogo',
        ],
    ];
@endphp

@push('styles')
    <link href="{{ asset('css/usuarios.css') }}" rel="stylesheet" />
@endpush

@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

@section('header-title')
Usuarios
@endsection

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
<div class="card ui-card container-card my-4">
    <div class="card-header text-center pt-4">
        <h4 class="mb-0 fw-semibold">
            <i class="fa-solid fa-users me-2 text-primary"></i>
            Lista de Usuarios
        </h4>
    </div>

    <div class="card-body px-4 pb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div class="ui-search-wrapper flex-grow-1" style="max-width: 400px;">
                <i class="fas fa-search ui-search-icon"></i>
                <input type="text"
                    id="buscadorUsuarios"
                    class="form-control ui-input ui-search-input"
                    placeholder="Buscar por nombre, correo, DNI o rol...">
            </div>

            <a href="{{ route('usuarios.exportarExcel') }}"
            class="btn-soft btn-soft-success">
                <i class="fa-solid fa-file-excel me-1"></i>
                Exportar Excel
            </a>
        </div>

        <div class="table-responsive ui-scroll" style="max-height: 500px; overflow-y:auto;">
            <table id="tablaUsuarios"
                class="table table-hover align-middle mb-0 ui-table text-nowrap">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>DNI</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($usuarios as $usuario)
                    <tr>
                        <td data-label="ID">{{ $usuario->id }}</td>
                        <td data-label="Nombre" class="fw-semibold">{{ $usuario->nombre }}</td>
                        <td data-label="DNI">{{ $usuario->dni ?: '—' }}</td>
                        <td data-label="Correo">{{ $usuario->email }}</td>
                        <td data-label="Rol">
                            <span class="badge bg-light text-dark border">
                                {{ $usuario->rol->nombre ?? 'Sin rol' }}
                            </span>
                        </td>
                        <td data-label="Acciones">
                            <div class="d-flex justify-content-center gap-2 action-buttons">
                                <button type="button"
                                    class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarUsuario"
                                    data-id="{{ $usuario->id }}"
                                    data-nombre="{{ $usuario->nombre }}"
                                    data-dni="{{ $usuario->dni }}"
                                    data-email="{{ $usuario->email }}"
                                    data-rol="{{ $usuario->rol_id }}"
                                    data-permisos='@json($usuario->permisos->pluck("permiso")->values())'>
                                    <i class="fa fa-edit"></i>
                                </button>

                                <form action="{{ route('usuarios.destroy', $usuario->id) }}"
                                    method="POST"
                                    class="eliminar-usuario-form"
                                    data-usuario="{{ $usuario->nombre }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>

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

<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl usuario-modal-dialog">
        <form method="POST" action="{{ route('usuarios.store') }}" class="modal-content usuario-modal-form" id="formNuevoUsuario" autocomplete="off" data-form-type="other">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="row g-4 usuario-modal-layout align-items-stretch">
                    <div class="col-12 col-lg-5">
                        <div class="usuario-modal-panel h-100">
                            <h6 class="usuario-modal-section-title">Datos del usuario</h6>

                            <div class="mb-3">
                                <label class="form-label">DNI</label>
                                <div class="usuario-dni-group">
                                    <input type="text" name="dni" id="nuevo-usuario-dni" class="form-control ui-input"
                                           inputmode="numeric" maxlength="8" autocomplete="off" placeholder="8 dígitos" required>
                                    <button type="button" class="btn-soft btn-soft-primary usuario-dni-consultar" id="consultarDniUsuario" title="Consultar en RENIEC">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <span>Consultar</span>
                                    </button>
                                </div>
                                <div class="usuario-dni-estado mt-2" id="nuevo-usuario-dni-estado" aria-live="polite">Ingresa el DNI para completar el nombre.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" id="nuevo-usuario-nombre" class="form-control ui-input" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="email" class="form-control ui-input" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contraseña</label>
                                <div class="usuario-password-group position-relative">
                                    <input type="text" id="nuevo-password-visible" class="form-control ui-input usuario-password-input usuario-clave-protegida pe-5" autocomplete="one-time-code" value="" required data-lpignore="true" data-1p-ignore data-bwignore spellcheck="false" autocapitalize="none" aria-label="Contraseña nueva">
                                    <input type="hidden" name="password" id="nuevo-password-payload" value="">
                                    <button type="button" class="toggle-password-btn" data-target="password" aria-label="Mostrar contraseña">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-security-help mt-2">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    Usa 8 caracteres o más e incluye mayúscula, minúscula, número y símbolo.
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Rol</label>
                                <select name="rol_id" id="nuevo-rol-id" class="form-select ui-input" required>
                                    <option value="" disabled selected>Seleccione un rol</option>
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id }}"
                                                data-description="{{ $descripcionesRol[$rol->nombre] ?? '' }}">
                                            {{ $rol->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="nuevo-rol-ayuda" class="usuario-modal-section-help mt-2" aria-live="polite"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-7">
                        <div class="usuario-modal-panel usuario-modal-panel-permisos h-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div>
                                    <h6 class="usuario-modal-section-title mb-1">Permisos de acceso</h6>
                                    <p class="usuario-modal-section-help mb-0">Al elegir un rol se marcarán permisos recomendados. Puedes personalizarlos antes de guardar.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-light usuario-permisos-action" id="marcarTodosPermisos">Marcar todo</button>
                                    <button type="button" class="btn btn-sm btn-light usuario-permisos-action" id="limpiarPermisos">Limpiar</button>
                                </div>
                            </div>

                            <div class="usuario-permisos-grid">
                                @foreach($catalogoPermisos as $grupo => $permisos)
                                    <div class="usuario-permisos-card">
                                        <div class="usuario-permisos-card-title">{{ $grupo }}</div>
                                        <div class="row g-2">
                                            @foreach($permisos as $valor => $label)
                                                <div class="col-12 col-md-6">
                                                    <div class="form-check usuario-permiso-item">
                                                        <input class="form-check-input permiso-checkbox"
                                                            type="checkbox"
                                                            name="permisos[]"
                                                            value="{{ $valor }}"
                                                            id="permiso_{{ Str::slug($valor, '_') }}"
                                                            data-permiso="{{ $valor }}">
                                                        <label class="form-check-label" for="permiso_{{ Str::slug($valor, '_') }}">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">
                    Cerrar
                </button>

                <button type="submit" class="btn-soft btn-soft-success">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl usuario-modal-dialog">
        <form method="POST" id="formEditarUsuario" class="modal-content usuario-modal-form" autocomplete="off" data-form-type="other">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="editar-id">

                <div class="row g-4 usuario-modal-layout align-items-stretch">
                    <div class="col-12 col-lg-5">
                        <div class="usuario-modal-panel h-100">
                            <div class="mb-3">
                                <label class="form-label">DNI</label>
                                <input type="text" name="dni" id="editar-dni" class="form-control ui-input" inputmode="numeric" maxlength="8" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" id="editar-nombre" class="form-control ui-input" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Correo Electrónico</label>
                                <input type="email" name="email" id="editar-email" class="form-control ui-input" required>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Rol</label>
                                <select name="rol_id" id="editar-rol" class="form-select ui-input" required>
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id }}"
                                                data-description="{{ $descripcionesRol[$rol->nombre] ?? '' }}">
                                            {{ $rol->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="editar-rol-ayuda" class="usuario-modal-section-help mt-2" aria-live="polite"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-7">
                        <div class="usuario-modal-panel usuario-modal-panel-permisos h-100">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                <div>
                                    <h6 class="usuario-modal-section-title mb-1">Permisos de acceso</h6>
                                    <p class="usuario-modal-section-help mb-0">Edita los accesos del usuario según el rol y los permisos que necesites.</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-light usuario-permisos-action" id="editarMarcarTodosPermisos">Marcar todo</button>
                                    <button type="button" class="btn btn-sm btn-light usuario-permisos-action" id="editarLimpiarPermisos">Limpiar</button>
                                </div>
                            </div>

                            <div class="usuario-permisos-grid">
                                @foreach($catalogoPermisos as $grupo => $permisos)
                                    <div class="usuario-permisos-card">
                                        <div class="usuario-permisos-card-title">{{ $grupo }}</div>
                                        <div class="row g-2">
                                            @foreach($permisos as $valor => $label)
                                                <div class="col-12 col-md-6">
                                                    <div class="form-check usuario-permiso-item">
                                                        <input class="form-check-input editar-permiso-checkbox"
                                                            type="checkbox"
                                                            name="permisos[]"
                                                            value="{{ $valor }}"
                                                            id="editar_permiso_{{ Str::slug($valor, '_') }}">
                                                        <label class="form-check-label" for="editar_permiso_{{ Str::slug($valor, '_') }}">
                                                            {{ $label }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
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

<div class="modal fade" id="modalCambiarClave" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <form method="POST" action="{{ route('usuarios.cambiarClave') }}" class="modal-content" autocomplete="off" data-form-type="other">
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
                    <input type="text" id="cambiar-clave-visible" class="form-control ui-input usuario-clave-protegida" required autocomplete="one-time-code" data-lpignore="true" data-1p-ignore data-bwignore spellcheck="false" autocapitalize="none" aria-label="Nueva contraseña">
                    <input type="hidden" name="nueva_clave" id="cambiar-clave-payload" value="">
                    <div class="password-security-help mt-2">
                        <i class="fa-solid fa-shield-halved"></i>
                        Usa 8 caracteres o más e incluye mayúscula, minúscula, número y símbolo.
                    </div>
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
<script>
    window.rolesUsuarios = @json($roles->pluck('id', 'nombre'));
    window.plantillasRolesUsuarios = @json($plantillasRol);
</script>
<script src="{{ asset('js/usuarios.js') }}"></script>
@endpush
