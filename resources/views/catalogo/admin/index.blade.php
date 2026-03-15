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
            Resumen ejecutivo del catálogo público: visibilidad, calidad de configuración y acciones clave.
        </p>
    </div>

    <div class="card-body px-4 pb-4">
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-xl-3">
                <div class="catalogo-kpi kpi-status">
                    <div class="kpi-label">Productos visibles</div>
                    <div class="kpi-value">{{ $productosVisibles }}</div>
                    <span class="ui-badge ui-badge-success">En catálogo</span>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="catalogo-kpi kpi-config">
                    <div class="kpi-label">Productos ocultos</div>
                    <div class="kpi-value">{{ $productosOcultos }}</div>
                    <span class="ui-badge ui-badge-warning">Por revisar</span>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="catalogo-kpi kpi-products">
                    <div class="kpi-label">Categorías públicas</div>
                    <div class="kpi-value">{{ $categoriasPublicas }}</div>
                    <span class="ui-badge ui-badge-secondary">Con productos</span>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <div class="catalogo-kpi kpi-visibility">
                    <div class="kpi-label">Configuración completada</div>
                    <div class="kpi-value">{{ $porcentajeConfig }}%</div>
                    <span class="ui-badge {{ $porcentajeConfig >= 75 ? 'ui-badge-success' : 'ui-badge-warning' }}">
                        {{ $porcentajeConfig >= 75 ? 'Óptimo' : 'Pendiente' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-7">
                <div class="card ui-card h-100 mb-0">
                    <div class="card-header">
                        <h6 class="mb-0 fw-semibold">Checklist del módulo</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive ui-scroll">
                            <table class="table ui-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Componente</th>
                                        <th>Detalle</th>
                                        <th class="text-end">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-semibold">Catálogo público</td>
                                        <td>{{ $productosVisibles > 0 ? 'Hay productos publicados.' : 'No hay productos visibles.' }}</td>
                                        <td class="text-end">
                                            <span class="ui-badge {{ $productosVisibles > 0 ? 'ui-badge-success' : 'ui-badge-warning' }}">
                                                {{ $productosVisibles > 0 ? 'Activo' : 'Sin publicar' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Contenido principal</td>
                                        <td>{{ $config && !empty($config->mensaje_bienvenida) ? 'Mensaje de bienvenida configurado.' : 'Falta mensaje de bienvenida.' }}</td>
                                        <td class="text-end">
                                            <span class="ui-badge {{ $config && !empty($config->mensaje_bienvenida) ? 'ui-badge-success' : 'ui-badge-warning' }}">
                                                {{ $config && !empty($config->mensaje_bienvenida) ? 'Completo' : 'Pendiente' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-semibold">Imagen de marca</td>
                                        <td>{{ $config && !empty($config->logo) ? 'Logo de catálogo cargado.' : 'No hay logo cargado.' }}</td>
                                        <td class="text-end">
                                            <span class="ui-badge {{ $config && !empty($config->logo) ? 'ui-badge-success' : 'ui-badge-warning' }}">
                                                {{ $config && !empty($config->logo) ? 'Ok' : 'Falta logo' }}
                                            </span>
                                        </td>
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
                            <i class="fas fa-box-open"></i> Gestionar productos
                        </a>
                        <a href="{{ route('catalogo') }}" target="_blank" class="btn-soft btn-soft-info w-100 justify-content-center">
                            <i class="fas fa-external-link-alt"></i> Ver catálogo público
                        </a>

                        <div class="catalogo-note mt-2">
                            <div class="note-title">Siguiente paso recomendado</div>
                            <div class="note-text">
                                @if($porcentajeConfig < 75)
                                    Completa la configuración general para mejorar la confianza del cliente en el catálogo.
                                @elseif($productosVisibles === 0)
                                    Publica al menos un producto visible para habilitar la experiencia de compra.
                                @else
                                    Tu catálogo está listo. Revisa periódicamente productos ocultos para mantenerlo actualizado.
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
