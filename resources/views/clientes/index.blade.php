@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/clientes.css') }}?v={{ filemtime(public_path('css/clientes.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/proveedor.css') }}?v={{ filemtime(public_path('css/proveedor.css')) }}" rel="stylesheet">
@endpush

@section('header-back')
<button class="btn-header-back" onclick="history.back()"><i class="fas fa-arrow-left"></i></button>
@endsection

@section('header-title')
Clientes
@endsection

@section('header-buttons')
<button type="button" class="btn-gasto" data-bs-toggle="modal" data-bs-target="#modalNuevoCliente">
    <i class="fas fa-user-plus"></i><span class="btn-text">Nuevo cliente</span>
</button>
@endsection

@section('content')
<div class="card ui-card container-card my-4">
    <div class="card-header text-center pt-4">
        <h4 class="mb-0 fw-semibold"><i class="fas fa-user-friends me-2 text-primary"></i>Lista de Clientes</h4>
    </div>
    <div class="card-body px-4 pb-4">
        <div class="d-flex justify-content-center mb-4">
            <div class="ui-search-wrapper" style="max-width:430px;width:100%">
                <i class="fas fa-search ui-search-icon"></i>
                <input type="search" id="searchCliente" class="form-control ui-input ui-search-input"
                    placeholder="Buscar por nombre, DNI, RUC o teléfono..." value="{{ request('search') }}" autocomplete="off">
            </div>
        </div>

        <div id="clientesTableContent">
            <div class="table-responsive ui-scroll" style="max-height:500px;overflow:auto">
                <table class="table table-hover align-middle mb-0 ui-table text-nowrap">
                    <thead><tr>
                        <th>ID</th><th>Nombre</th><th>Dirección</th><th>Teléfono</th><th>RUC</th><th>DNI</th><th class="text-center">Acciones</th>
                    </tr></thead>
                    <tbody>
                    @forelse($clientes as $cliente)
                        <tr>
                            <td data-label="ID">{{ $cliente->id }}</td>
                            <td data-label="Nombre" class="fw-semibold">{{ $cliente->nombre }}</td>
                            <td data-label="Dirección">{{ $cliente->direccion ?: '—' }}</td>
                            <td data-label="Teléfono">{{ $cliente->telefono ?: '—' }}</td>
                            <td data-label="RUC">{{ $cliente->ruc ?: '—' }}</td>
                            <td data-label="DNI">{{ $cliente->dni ?: '—' }}</td>
                            <td data-label="Acciones" class="text-center">
                                <button type="button" class="btn-soft btn-soft-warning btn-soft-icon btn-edit-client"
                                    data-id="{{ $cliente->id }}" aria-label="Editar cliente">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No hay clientes registrados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($clientes->hasPages())
                <div class="d-flex justify-content-center mt-4">{{ $clientes->appends(request()->query())->links() }}</div>
            @endif
        </div>
    </div>
</div>

@php
    $clientForms = [
        ['mode' => 'create', 'modal' => 'modalNuevoCliente', 'form' => 'formNuevoCliente', 'title' => 'Nuevo Cliente'],
        ['mode' => 'edit', 'modal' => 'modalEditarCliente', 'form' => 'formEditarCliente', 'title' => 'Editar Cliente'],
    ];
@endphp

@foreach($clientForms as $clientForm)
<div class="modal fade" id="{{ $clientForm['modal'] }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content client-form" id="{{ $clientForm['form'] }}" data-mode="{{ $clientForm['mode'] }}">
            @csrf
            @if($clientForm['mode'] === 'edit')<input type="hidden" name="cliente_id">@endif
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas {{ $clientForm['mode'] === 'create' ? 'fa-user-plus' : 'fa-user-pen' }} me-2 text-primary"></i>{{ $clientForm['title'] }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body provider-modal-body">
                <section class="provider-form-section">
                    <div class="provider-section-heading">
                        <span class="provider-section-icon"><i class="fas fa-id-card"></i></span>
                        <div><strong>Identificación del cliente</strong><small>Primero verificamos la base local y luego consultamos API Perú.</small></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de documento</label>
                            <select name="tipo_documento" class="form-select ui-input client-document-type" required>
                                <option value="DNI">DNI</option><option value="RUC">RUC</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Número de documento</label>
                            <div class="input-group provider-document-input">
                                <input type="text" name="numero_documento" class="form-control ui-input client-document-number"
                                    inputmode="numeric" autocomplete="off" required>
                                <button type="button" class="btn btn-primary client-query-button"><i class="fas fa-search"></i><span>Consultar</span></button>
                            </div>
                            <div class="provider-query-status client-query-status" aria-live="polite"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nombre o razón social</label>
                            <div class="input-icon-field"><i class="fas fa-user"></i><input type="text" name="nombre" class="form-control ui-input" required></div>
                        </div>
                    </div>
                </section>
                <section class="provider-form-section">
                    <div class="provider-section-heading">
                        <span class="provider-section-icon provider-section-icon-green"><i class="fas fa-address-book"></i></span>
                        <div><strong>Datos de contacto</strong><small>Información opcional para identificar y contactar al cliente.</small></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-5"><label class="form-label">Teléfono</label><input type="tel" name="telefono" class="form-control ui-input" inputmode="tel" placeholder="Ej. 987 654 321"></div>
                        <div class="col-md-7"><label class="form-label">Dirección</label><input type="text" name="direccion" class="form-control ui-input" placeholder="Dirección o referencia"></div>
                    </div>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-soft btn-soft-primary client-save-button"><i class="fas fa-save"></i> Guardar {{ strtolower($clientForm['title']) }}</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection

@push('scripts')
<script src="{{ asset('js/clientes.js') }}?v={{ filemtime(public_path('js/clientes.js')) }}" defer></script>
@endpush
