@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/proveedor.css') }}" rel="stylesheet" />
@endpush

{{-- BOTÓN ATRÁS --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Proveedores
@endsection

{{-- ACCIONES DEL HEADER --}}
@section('header-buttons')
<button class="btn-gasto"
        data-bs-toggle="modal"
        data-bs-target="#modalProveedor">
    <i class="fas fa-plus me-1"></i>
    Nuevo proveedor
</button>
@endsection

@section('content')
<div class="card ui-card container-card my-4">

        <div class="card-header text-center pt-4">
            <h4 class="mb-0 fw-semibold">
                    <i class="fas fa-industry me-2 text-primary"></i>
                    Lista de Proveedores
            </h4>
        </div>

    <div class="card-body pt-2 pb-4">

        {{-- BUSCADOR --}}
        <div class="d-flex justify-content-center mb-4">
            <div class="ui-search-wrapper">
                <i class="fas fa-search ui-search-icon"></i>
                <input type="text"
                    id="searchProveedor"
                    class="form-control ui-input ui-search-input"
                    placeholder="Buscar por razón social, nombre, DNI o RUC...">
            </div>
        </div>

            <div class="table-responsive ui-scroll">
                <div id="table-content">
                    <table class="table table-hover align-middle mb-0 ui-table text-nowrap">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Documento</th>
                                <th>Contacto</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Estado</th>
                                <th class="text-center" style="width: 120px;">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($proveedores as $proveedor)
                            <tr>
                                <td data-label="Nombre" class="fw-semibold">
                                    {{ $proveedor->nombre }}
                                </td>

                                <td data-label="Documento">
                                    {{ $proveedor->tipo_documento }} {{ $proveedor->numero_documento }}
                                </td>

                                <td data-label="Contacto">
                                    {{ $proveedor->contacto ?? '—' }}
                                </td>

                                <td data-label="Teléfono">
                                    {{ $proveedor->telefono ?? '—' }}
                                </td>

                                <td data-label="Email">
                                    {{ $proveedor->email ?? '—' }}
                                </td>

                                <td data-label="Estado">
                                    @if($proveedor->estado)
                                        <span class="ui-badge ui-badge-success">Activo</span>
                                    @else
                                        <span class="ui-badge ui-badge-secondary">Inactivo</span>
                                    @endif
                                </td>

                                <td data-label="Acciones">
                                    <div class="d-flex justify-content-center gap-2 action-buttons">
                                        <button type="button"
                                                class="btn-soft btn-soft-warning btn-soft-icon"
                                                data-id="{{ $proveedor->id }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditarProveedor">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No hay proveedores registrados
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
</div>


{{-- ================= MODAL ================= --}}
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="{{ route('proveedores.store') }}" method="POST" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2 text-primary"></i>
                    Nuevo Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="nombre" class="form-control ui-input" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo Doc.</label>
                        <select name="tipo_documento" class="form-select ui-input" required>
                            <option value="RUC">RUC</option>
                            <option value="DNI">DNI</option>
                            <option value="OTRO">OTRO</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">N° Documento</label>
                        <input type="text" name="numero_documento" class="form-control ui-input" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contacto</label>
                        <input type="text" name="contacto" class="form-control ui-input">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control ui-input">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control ui-input">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Dirección</label>
                        <input type="text" name="direccion" class="form-control ui-input">
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="submit" class="btn-soft btn-soft-success">
                    <i class="fas fa-save me-1"></i>
                    Guardar proveedor
                </button>
            </div>

        </form>
    </div>
</div>

{{-- ================= MODAL EDITAR ================= --}}
<div class="modal fade" id="modalEditarProveedor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">

        <form method="POST" id="formEditarProveedor" class="modal-content">
            @csrf
            @method('PUT')

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-pen me-2 text-primary"></i>
                    Editar Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="edit_id">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombre</label>
                        <input type="text" id="edit_nombre" name="nombre" class="form-control ui-input" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo Doc.</label>
                        <select id="edit_tipo_documento" name="tipo_documento" class="form-select ui-input" required>
                            <option value="RUC">RUC</option>
                            <option value="DNI">DNI</option>
                            <option value="OTRO">OTRO</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">N° Documento</label>
                        <input type="text" id="edit_numero_documento" name="numero_documento" class="form-control ui-input" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Contacto</label>
                        <input type="text" id="edit_contacto" name="contacto" class="form-control ui-input">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Teléfono</label>
                        <input type="text" id="edit_telefono" name="telefono" class="form-control ui-input">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control ui-input">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Dirección</label>
                        <input type="text" id="edit_direccion" name="direccion" class="form-control ui-input">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Estado</label>
                        <select id="edit_estado" name="estado" class="form-select ui-input" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="submit" class="btn-soft btn-soft-primary">
                    Guardar cambios
                </button>
            </div>

        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {

        // ==========================
        // BUSCADOR AJAX (debounce)
        // ==========================
        const input = document.getElementById('searchProveedor');
        let timer = null;

        if (input) {
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    const q = input.value.trim();

                    fetch(`{{ route('proveedores.index') }}?search=${encodeURIComponent(q)}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.text())
                    .then(html => {
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const newContent = doc.querySelector('#table-content');
                        if (newContent) document.querySelector('#table-content').innerHTML = newContent.innerHTML;
                    });
                }, 250);
            });
        }

        // ==========================
        // EDITAR: cargar datos y abrir modal
        // ==========================
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-edit');
            if (!btn) return;

            const id = btn.dataset.id;

            fetch(`/proveedores/${id}/edit`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(p => {
                document.getElementById('edit_id').value = p.id;
                document.getElementById('edit_nombre').value = p.nombre ?? '';
                document.getElementById('edit_tipo_documento').value = p.tipo_documento ?? 'RUC';
                document.getElementById('edit_numero_documento').value = p.numero_documento ?? '';
                document.getElementById('edit_contacto').value = p.contacto ?? '';
                document.getElementById('edit_telefono').value = p.telefono ?? '';
                document.getElementById('edit_email').value = p.email ?? '';
                document.getElementById('edit_direccion').value = p.direccion ?? '';
                document.getElementById('edit_estado').value = p.estado ? '1' : '0';

                // set action del form PUT
                document.getElementById('formEditarProveedor').action = `/proveedores/${id}`;
            })
            .catch(() => {
                Swal.fire({ icon:'error', title:'Error', text:'No se pudo cargar el proveedor.' });
            });
        });

    });
</script>
@endpush