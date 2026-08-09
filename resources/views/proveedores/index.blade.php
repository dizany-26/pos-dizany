@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/proveedor.css') }}?v={{ filemtime(public_path('css/proveedor.css')) }}" rel="stylesheet" />
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
                <div id="table-content" class="provider-table-content">
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
                                                class="btn-soft btn-soft-warning btn-soft-icon btn-edit"
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
        <form action="{{ route('proveedores.store') }}" method="POST" class="modal-content" id="formNuevoProveedor">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2 text-primary"></i>
                    Nuevo Proveedor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body provider-modal-body">
                <section class="provider-form-section">
                    <div class="provider-section-heading">
                        <span class="provider-section-icon"><i class="fas fa-id-card"></i></span>
                        <div>
                            <strong>Identificación del proveedor</strong>
                            <small>Consulta el documento para completar sus datos automáticamente.</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de documento</label>
                            <select name="tipo_documento" id="proveedor_tipo_documento" class="form-select ui-input" required>
                                <option value="RUC">RUC</option>
                                <option value="DNI">DNI</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Número de documento</label>
                            <div class="input-group provider-document-input">
                                <input type="text" name="numero_documento" id="proveedor_numero_documento"
                                    class="form-control ui-input" inputmode="numeric" autocomplete="off"
                                    maxlength="11" placeholder="Ingresa 11 dígitos" required>
                                <button type="button" class="btn btn-primary" id="btnConsultarProveedor">
                                    <i class="fas fa-search"></i>
                                    <span>Consultar</span>
                                </button>
                            </div>
                            <div id="proveedorConsultaEstado" class="provider-query-status" aria-live="polite"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="proveedor_nombre">Razón social o nombre</label>
                            <div class="input-icon-field">
                                <i class="fas fa-building"></i>
                                <input type="text" name="nombre" id="proveedor_nombre"
                                    class="form-control ui-input" placeholder="Nombre del proveedor" required>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="provider-form-section">
                    <div class="provider-section-heading">
                        <span class="provider-section-icon provider-section-icon-green"><i class="fas fa-address-book"></i></span>
                        <div>
                            <strong>Datos de contacto</strong>
                            <small>Información para comunicarse con el proveedor.</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Persona de contacto</label>
                            <input type="text" name="contacto" class="form-control ui-input" placeholder="Ej. Juan Pérez">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono" class="form-control ui-input"
                                inputmode="tel" placeholder="Ej. 987 654 321">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" name="email" class="form-control ui-input"
                                placeholder="proveedor@empresa.com">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Dirección fiscal</label>
                            <input type="text" name="direccion" id="proveedor_direccion"
                                class="form-control ui-input" placeholder="Dirección del proveedor">
                        </div>
                    </div>
                </section>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="submit" class="btn-soft btn-soft-success" id="btnGuardarProveedor">
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

            <div class="modal-body provider-modal-body">
                <input type="hidden" id="edit_id">

                <section class="provider-form-section">
                    <div class="provider-section-heading">
                        <span class="provider-section-icon"><i class="fas fa-id-card"></i></span>
                        <div>
                            <strong>Identificación del proveedor</strong>
                            <small>Verifica o actualiza la información usando el documento.</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de documento</label>
                            <select id="edit_tipo_documento" name="tipo_documento" class="form-select ui-input" required>
                                <option value="RUC">RUC</option>
                                <option value="DNI">DNI</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Número de documento</label>
                            <div class="input-group provider-document-input">
                                <input type="text" id="edit_numero_documento" name="numero_documento"
                                    class="form-control ui-input" inputmode="numeric" autocomplete="off" required>
                                <button type="button" class="btn btn-primary" id="btnConsultarProveedorEdit">
                                    <i class="fas fa-search"></i>
                                    <span>Consultar</span>
                                </button>
                            </div>
                            <div id="editProveedorConsultaEstado" class="provider-query-status" aria-live="polite"></div>
                        </div>

                        <div class="col-md-9">
                            <label class="form-label">Razón social o nombre</label>
                            <div class="input-icon-field">
                                <i class="fas fa-building"></i>
                                <input type="text" id="edit_nombre" name="nombre" class="form-control ui-input" required>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select id="edit_estado" name="estado" class="form-select ui-input" required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="provider-form-section">
                    <div class="provider-section-heading">
                        <span class="provider-section-icon provider-section-icon-green"><i class="fas fa-address-book"></i></span>
                        <div>
                            <strong>Datos de contacto</strong>
                            <small>Actualiza los medios de contacto y la dirección fiscal.</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Persona de contacto</label>
                            <input type="text" id="edit_contacto" name="contacto" class="form-control ui-input">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" id="edit_telefono" name="telefono" class="form-control ui-input" inputmode="tel">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico</label>
                            <input type="email" id="edit_email" name="email" class="form-control ui-input">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Dirección fiscal</label>
                            <input type="text" id="edit_direccion" name="direccion" class="form-control ui-input">
                        </div>
                    </div>
                </section>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">
                    Cancelar
                </button>

                <button type="submit" class="btn-soft btn-soft-primary" id="btnGuardarProveedorEdit">
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
        const tipoDocumento = document.getElementById('proveedor_tipo_documento');
        const numeroDocumento = document.getElementById('proveedor_numero_documento');
        const nombreProveedor = document.getElementById('proveedor_nombre');
        const direccionProveedor = document.getElementById('proveedor_direccion');
        const btnConsultar = document.getElementById('btnConsultarProveedor');
        const consultaEstado = document.getElementById('proveedorConsultaEstado');
        const modalProveedor = document.getElementById('modalProveedor');
        const btnGuardarProveedor = document.getElementById('btnGuardarProveedor');
        let consultaTimer = null;
        let ultimaConsulta = '';
        let documentoDuplicado = false;

        const verificarDocumentoRegistrado = async (numero, excepto = '') => {
            const params = new URLSearchParams({ numero });
            if (excepto) params.set('excepto', excepto);

            const response = await fetch(`/proveedores/verificar-documento?${params}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('No se pudo verificar el documento.');
            return response.json();
        };

        const configurarDocumento = () => {
            const tipo = tipoDocumento?.value || 'RUC';
            const longitud = tipo === 'DNI' ? 8 : (tipo === 'RUC' ? 11 : 20);
            const consultable = tipo === 'DNI' || tipo === 'RUC';

            numeroDocumento.maxLength = longitud;
            numeroDocumento.inputMode = consultable ? 'numeric' : 'text';
            numeroDocumento.placeholder = consultable
                ? `Ingresa ${longitud} dígitos`
                : 'Ingresa el documento';
            btnConsultar.classList.toggle('d-none', !consultable);
            consultaEstado.textContent = consultable
                ? 'La consulta se realizará al completar el documento.'
                : 'Completa los datos manualmente.';
            consultaEstado.className = 'provider-query-status';
            documentoDuplicado = false;
            btnConsultar.disabled = false;
            btnGuardarProveedor.disabled = false;
            ultimaConsulta = '';
        };

        const consultarDocumentoProveedor = async () => {
            const tipo = tipoDocumento.value;
            const numero = numeroDocumento.value.replace(/\D/g, '');
            const longitud = tipo === 'DNI' ? 8 : (tipo === 'RUC' ? 11 : 0);
            const claveConsulta = `${tipo}-${numero}`;

            if (!longitud || numero.length !== longitud) {
                consultaEstado.textContent = `El ${tipo} debe tener ${longitud} dígitos.`;
                consultaEstado.className = 'provider-query-status is-warning';
                return;
            }
            if (claveConsulta === ultimaConsulta) return;

            ultimaConsulta = claveConsulta;
            documentoDuplicado = false;
            btnGuardarProveedor.disabled = false;
            btnConsultar.disabled = true;
            btnConsultar.innerHTML = '<span class="spinner-border spinner-border-sm"></span><span>Consultando</span>';
            consultaEstado.textContent = 'Consultando información oficial…';
            consultaEstado.className = 'provider-query-status is-loading';

            try {
                const registrado = await verificarDocumentoRegistrado(numero);
                if (registrado.existe) {
                    const mensaje = `Este documento ya está registrado para ${registrado.proveedor.nombre}.`;
                    documentoDuplicado = true;
                    btnConsultar.disabled = true;
                    btnGuardarProveedor.disabled = true;
                    consultaEstado.textContent = mensaje;
                    consultaEstado.className = 'provider-query-status is-error';
                    return;
                }

                const response = await fetch(`/consulta-documento/${tipo.toLowerCase()}/${numero}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();

                if (!response.ok) throw new Error(data.message || 'Documento no encontrado.');

                nombreProveedor.value = data.nombre || '';
                if (data.direccion) direccionProveedor.value = data.direccion;
                consultaEstado.textContent = tipo === 'RUC'
                    ? `Proveedor encontrado${data.estado ? ` · Estado: ${data.estado}` : ''}`
                    : 'Persona encontrada correctamente.';
                consultaEstado.className = 'provider-query-status is-success';
                nombreProveedor.focus();
            } catch (error) {
                ultimaConsulta = '';
                consultaEstado.textContent = error.message || 'No se pudo consultar el documento.';
                consultaEstado.className = 'provider-query-status is-error';
            } finally {
                btnConsultar.disabled = documentoDuplicado;
                btnConsultar.innerHTML = '<i class="fas fa-search"></i><span>Consultar</span>';
            }
        };

        tipoDocumento?.addEventListener('change', () => {
            numeroDocumento.value = '';
            nombreProveedor.value = '';
            direccionProveedor.value = '';
            configurarDocumento();
            numeroDocumento.focus();
        });

        numeroDocumento?.addEventListener('input', () => {
            documentoDuplicado = false;
            btnConsultar.disabled = false;
            btnGuardarProveedor.disabled = false;
            consultaEstado.textContent = 'La consulta se realizará al completar el documento.';
            consultaEstado.className = 'provider-query-status';
            if (tipoDocumento.value !== 'OTRO') {
                numeroDocumento.value = numeroDocumento.value.replace(/\D/g, '');
            } else {
                consultaEstado.textContent = 'Completa los datos manualmente.';
            }

            clearTimeout(consultaTimer);
            const longitud = tipoDocumento.value === 'DNI' ? 8 : 11;
            if (numeroDocumento.value.length === longitud && tipoDocumento.value !== 'OTRO') {
                consultaTimer = setTimeout(consultarDocumentoProveedor, 350);
            } else {
                ultimaConsulta = '';
            }
        });

        btnConsultar?.addEventListener('click', consultarDocumentoProveedor);
        modalProveedor?.addEventListener('shown.bs.modal', configurarDocumento);
        modalProveedor?.addEventListener('hidden.bs.modal', () => {
            document.getElementById('formNuevoProveedor')?.reset();
            configurarDocumento();
        });

        const editTipo = document.getElementById('edit_tipo_documento');
        const editNumero = document.getElementById('edit_numero_documento');
        const editNombre = document.getElementById('edit_nombre');
        const editDireccion = document.getElementById('edit_direccion');
        const editEstadoConsulta = document.getElementById('editProveedorConsultaEstado');
        const editBtnConsultar = document.getElementById('btnConsultarProveedorEdit');
        const editBtnGuardar = document.getElementById('btnGuardarProveedorEdit');
        let editTimer = null;
        let editUltimaConsulta = '';
        let editDocumentoDuplicado = false;

        const configurarDocumentoEdit = (mostrarAyuda = true) => {
            const tipo = editTipo.value;
            const longitud = tipo === 'DNI' ? 8 : (tipo === 'RUC' ? 11 : 20);
            const consultable = tipo === 'DNI' || tipo === 'RUC';
            editNumero.maxLength = longitud;
            editNumero.inputMode = consultable ? 'numeric' : 'text';
            editNumero.placeholder = consultable ? `Ingresa ${longitud} dígitos` : 'Ingresa el documento';
            editBtnConsultar.classList.toggle('d-none', !consultable);
            editEstadoConsulta.textContent = mostrarAyuda
                ? (consultable ? 'Puedes volver a consultar este documento.' : 'Completa los datos manualmente.')
                : '';
            editEstadoConsulta.className = 'provider-query-status';
            editDocumentoDuplicado = false;
            editBtnConsultar.disabled = false;
            editBtnGuardar.disabled = false;
            editUltimaConsulta = '';
        };

        const consultarDocumentoEdit = async () => {
            const tipo = editTipo.value;
            const numero = editNumero.value.replace(/\D/g, '');
            const longitud = tipo === 'DNI' ? 8 : (tipo === 'RUC' ? 11 : 0);
            const clave = `${tipo}-${numero}`;

            if (!longitud || numero.length !== longitud) {
                editEstadoConsulta.textContent = `El ${tipo} debe tener ${longitud} dígitos.`;
                editEstadoConsulta.className = 'provider-query-status is-warning';
                return;
            }
            if (clave === editUltimaConsulta) return;

            editUltimaConsulta = clave;
            editDocumentoDuplicado = false;
            editBtnGuardar.disabled = false;
            editBtnConsultar.disabled = true;
            editBtnConsultar.innerHTML = '<span class="spinner-border spinner-border-sm"></span><span>Consultando</span>';
            editEstadoConsulta.textContent = 'Verificando documento…';
            editEstadoConsulta.className = 'provider-query-status is-loading';

            try {
                const registrado = await verificarDocumentoRegistrado(numero, document.getElementById('edit_id').value);
                if (registrado.existe) {
                    const mensaje = `Este documento ya está registrado para ${registrado.proveedor.nombre}.`;
                    editDocumentoDuplicado = true;
                    editBtnConsultar.disabled = true;
                    editBtnGuardar.disabled = true;
                    editEstadoConsulta.textContent = mensaje;
                    editEstadoConsulta.className = 'provider-query-status is-error';
                    return;
                }

                const response = await fetch(`/consulta-documento/${tipo.toLowerCase()}/${numero}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Documento no encontrado.');

                editNombre.value = data.nombre || '';
                if (data.direccion) editDireccion.value = data.direccion;
                editEstadoConsulta.textContent = tipo === 'RUC'
                    ? `Datos actualizados${data.estado ? ` · Estado: ${data.estado}` : ''}`
                    : 'Datos actualizados correctamente.';
                editEstadoConsulta.className = 'provider-query-status is-success';
            } catch (error) {
                editUltimaConsulta = '';
                editEstadoConsulta.textContent = error.message || 'No se pudo consultar el documento.';
                editEstadoConsulta.className = 'provider-query-status is-error';
            } finally {
                editBtnConsultar.disabled = editDocumentoDuplicado;
                editBtnConsultar.innerHTML = '<i class="fas fa-search"></i><span>Consultar</span>';
            }
        };

        editTipo?.addEventListener('change', () => {
            editNumero.value = '';
            editNombre.value = '';
            editDireccion.value = '';
            configurarDocumentoEdit();
            editNumero.focus();
        });

        editNumero?.addEventListener('input', () => {
            editDocumentoDuplicado = false;
            editBtnConsultar.disabled = false;
            editBtnGuardar.disabled = false;
            editEstadoConsulta.textContent = editTipo.value === 'OTRO'
                ? 'Completa los datos manualmente.'
                : 'La consulta se realizará al completar el documento.';
            editEstadoConsulta.className = 'provider-query-status';
            if (editTipo.value !== 'OTRO') editNumero.value = editNumero.value.replace(/\D/g, '');
            clearTimeout(editTimer);
            const longitud = editTipo.value === 'DNI' ? 8 : 11;
            if (editTipo.value !== 'OTRO' && editNumero.value.length === longitud) {
                editTimer = setTimeout(consultarDocumentoEdit, 350);
            } else {
                editUltimaConsulta = '';
            }
        });

        editBtnConsultar?.addEventListener('click', consultarDocumentoEdit);

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
                editDocumentoDuplicado = false;
                editBtnConsultar.disabled = false;
                editBtnGuardar.disabled = false;
                configurarDocumentoEdit(false);

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
