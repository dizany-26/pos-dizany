@extends('layouts.app')

@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

@section('header-title')
Catálogo
@endsection

@section('header-buttons')
<a href="{{ route('catalogo') }}" target="_blank" class="btn-gasto">
    <i class="fas fa-eye"></i>
    <span class="btn-text">Ver catálogo</span>
</a>
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('css/catalogo-admin.css') }}">

<div class="card ui-card container-card my-4 catalogo-admin-panel">
    <div class="card-header text-center pt-4">
        <h4 class="mb-0 fw-semibold">
            <i class="fas fa-store me-2 text-primary"></i>
            Panel de Catálogo
        </h4>
        <p class="catalogo-admin-subtitle mb-0 mt-2">
            Gestiona estado, accesos y configuración pública del catálogo.
        </p>
    </div>

    <div class="card-body px-4 pb-4">
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="catalogo-kpi kpi-status">
                    <div class="kpi-label">Catálogo público</div>
                    <div class="kpi-value">Activo</div>
                    <span class="ui-badge ui-badge-success">Online</span>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="catalogo-kpi kpi-config">
                    <div class="kpi-label">Configuración</div>
                    <div class="kpi-value">General</div>
                    <span class="ui-badge ui-badge-secondary">Editable</span>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="catalogo-kpi kpi-products">
                    <div class="kpi-label">Productos</div>
                    <div class="kpi-value">Gestión</div>
                    <span class="ui-badge ui-badge-secondary">Inventario</span>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="catalogo-kpi kpi-visibility">
                    <div class="kpi-label">Vista cliente</div>
                    <div class="kpi-value">Disponible</div>
                    <span class="ui-badge ui-badge-success">Pública</span>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="card ui-card h-100 mb-0">
                    <div class="card-header">
                        <h6 class="mb-0 fw-semibold">Estado del módulo</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive ui-scroll">
                            <table class="table ui-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Componente</th>
                                        <th>Descripción</th>
                                        <th class="text-end">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold">Catálogo público</td>
                                        <td>Visible para clientes externos.</td>
                                        <td class="text-end"><span class="ui-badge ui-badge-success">Activo</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Configuración</td>
                                        <td>Logo, contacto, mensajes y textos.</td>
                                        <td class="text-end"><span class="ui-badge ui-badge-warning">Editable</span></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Productos</td>
                                        <td>Visibilidad y gestión de catálogo.</td>
                                        <td class="text-end"><span class="ui-badge ui-badge-secondary">Administrable</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="card ui-card h-100 mb-0">
                    <div class="card-header">
                        <h6 class="mb-0 fw-semibold">Acciones rápidas</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('catalogo.admin.config') }}" class="btn-soft btn-soft-primary w-100 justify-content-center">
                            <i class="fas fa-cog"></i> Configurar catálogo
                        </a>
                        <a href="{{ route('productos.index') }}" class="btn-soft btn-soft-success w-100 justify-content-center">
                            <i class="fas fa-box-open"></i> Ir a productos
                        </a>
                        <a href="{{ route('catalogo') }}" target="_blank" class="btn-soft btn-soft-info w-100 justify-content-center">
                            <i class="fas fa-external-link-alt"></i> Ver catálogo público
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
