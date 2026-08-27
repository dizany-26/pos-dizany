@extends('layouts.app')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="{{ asset('css/crear_productos.css') }}?v={{ filemtime(public_path('css/crear_productos.css')) }}" rel="stylesheet" />
@endpush

{{-- BOTÓN ATRÁS --}}
@section('header-back')

@php
    $from = request()->get('from');
@endphp

@if($from === 'lotes')
    <a href="{{ route('inventario.lote') }}" class="btn-header-back">
        <i class="fas fa-arrow-left"></i>
    </a>
@else
    <button class="btn-header-back" onclick="history.back()">
        <i class="fas fa-arrow-left"></i>
    </button>
@endif

@endsection

{{-- TÍTULO --}}
@section('header-title')
Nuevo Producto
@endsection

{{-- BOTONES DERECHA --}}
@section('header-buttons')
{{-- (vacío, no hay acciones en esta vista) --}}
@endsection


@section('content')
<!-- Formulario en tarjeta -->
<div class="card ui-card container-card my-4">

    <div class="card-header text-center pt-4">
        <h4 class="mb-0 fw-semibold">
            <i class="fas fa-box-open me-2 text-primary"></i> Nuevo Producto
        </h4>
    </div>

    <div class="card-body pt-2 pb-4">

        {{-- Errores de validación --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="form-nuevo-producto" action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">

                {{-- ================= DATOS BÁSICOS ================= --}}
                <div class="col-md-4">
                    <label class="form-label">Código de Barras</label>
                    <div class="codigo-barras-field">
                        <input type="text"
                            name="codigo_barras"
                            id="codigo_barras"
                            class="form-control ui-input"
                            inputmode="numeric"
                            autocomplete="off"
                            value="{{ old('codigo_barras') }}">

                        <button type="button"
                            class="btn-soft btn-soft-info codigo-barras-scan-btn"
                            id="btnEscanearCodigo"
                            title="Escanear código de barras"
                            aria-label="Escanear código de barras con cámara">
                            <i class="fas fa-barcode"></i>
                        </button>
                    </div>

                    <small class="codigo-barras-help">
                        En móvil abre la cámara para escanear. En PC puedes usar una pistola lectora con el cursor en este campo.
                    </small>

                    <div id="codigo_barras_error" class="invalid-feedback d-none">
                        Este código de barras ya está registrado.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Nombre</label>
                    <input type="text"
                        id="nombre"
                        name="nombre"
                        class="form-control ui-input"
                        value="{{ old('nombre') }}"
                        required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ubicación</label>
                    <input type="text"
                        name="ubicacion"
                        class="form-control ui-input"
                        value="{{ old('ubicacion') }}">
                </div>

                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion"
                        id="descripcion"
                        class="form-control ui-input"
                        rows="2">{{ old('descripcion') }}</textarea>
                </div>

                {{-- ================= PRESENTACIONES ================= --}}
                <div class="col-12 mt-2">
                    <div class="ui-section-box p-3 rounded-4 border bg-white">

                        <div class="ui-section-title mb-2">
                            <i class="fas fa-layer-group me-2 text-primary"></i>
                            Presentaciones
                        </div>

                        <div class="d-flex flex-column gap-2">

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked disabled>
                                <label class="form-check-label">
                                    Unidad (siempre disponible)
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chk_paquete">
                                <label class="form-check-label">Paquete</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="chk_caja">
                                <label class="form-check-label">Caja</label>
                            </div>

                        </div>

                        {{-- ================= CONVERSIONES ================= --}}
                        <div class="row g-3 mt-2">

                            <div class="col-md-4 d-none" id="grupo_unidades_paquete">
                                <label class="form-label">Unidades por paquete</label>
                                <input type="number"
                                    name="unidades_por_paquete"
                                    class="form-control ui-input"
                                    min="1">
                            </div>

                            <div class="col-md-4 d-none" id="grupo_paquetes_caja">
                                <label class="form-label">Paquetes por caja</label>
                                <input type="number"
                                    name="paquetes_por_caja"
                                    class="form-control ui-input"
                                    min="1">
                            </div>

                            <div class="col-md-4 d-none" id="grupo_unidades_caja">
                                <label class="form-label">Unidades por caja</label>
                                <input type="number"
                                    name="unidades_por_caja"
                                    class="form-control ui-input"
                                    min="1">
                            </div>

                        </div>

                    </div>
                </div>

                {{-- ================= VENCIMIENTO ================= --}}
                <div class="col-md-4 mt-2">
                    <div class="form-check">
                        <input class="form-check-input"
                            type="checkbox"
                            name="maneja_vencimiento"
                            value="1">
                        <label class="form-check-label">
                            Maneja fecha de vencimiento
                        </label>
                    </div>
                </div>

                {{-- ================= CATEGORÍA / MARCA ================= --}}
                <div class="col-md-4">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Categoría</span>
                        <button type="button"
                            class="btn-soft btn-soft-primary btn-soft-icon"
                            data-bs-toggle="modal"
                            data-bs-target="#modalNuevaCategoria"
                            title="Nueva categoría">
                            <i class="fas fa-plus"></i>
                        </button>
                    </label>
                    <select name="categoria_id" id="categoria_id" class="form-select ui-input" required>
                        <option value="">Seleccione</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id }}">
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>Marca</span>
                        <button type="button"
                            class="btn-soft btn-soft-primary btn-soft-icon"
                            data-bs-toggle="modal"
                            data-bs-target="#modalNuevaMarca"
                            title="Nueva marca">
                            <i class="fas fa-plus"></i>
                        </button>
                    </label>
                    <select name="marca_id" id="marca_id" class="form-select ui-input">
                        <option value="">Seleccione</option>
                        @foreach($marcas as $marca)
                            <option value="{{ $marca->id }}">
                                {{ $marca->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- ================= IMAGEN ================= --}}
                <div class="col-md-4">
                    <label class="form-label">Imagen principal</label>
                    <input type="file"
                        name="imagen"
                        id="imagen"
                        class="form-control ui-input"
                        accept="image/*">

                    <img id="preview_imagen"
                        src=""
                        class="ui-product-preview d-none mt-2"
                        alt="Vista previa">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Imágenes adicionales del catálogo</label>
                    <input type="file"
                        name="imagenes_catalogo[]"
                        class="form-control ui-input"
                        accept="image/*"
                        multiple
                        data-catalog-images>
                    <div class="catalog-images-preview d-none" data-catalog-previews aria-live="polite"></div>
                    <div class="catalog-images-error d-none" data-catalog-images-error role="alert"></div>
                    <small class="form-text text-muted">Opcional. Elige hasta 2 imágenes; sólo aparecerán en el catálogo público.</small>
                </div>

                {{-- ================= ESTADO ================= --}}
                <div class="col-md-4 mt-2">
                    <div class="d-flex flex-column gap-2">

                        <div class="form-check">
                            <input class="form-check-input"
                                type="checkbox"
                                name="activo"
                                value="1"
                                checked>
                            <label class="form-check-label">Activo</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input"
                                type="checkbox"
                                name="visible_en_catalogo"
                                value="1"
                                checked>
                            <label class="form-check-label">Visible en catálogo</label>
                        </div>

                    </div>
                </div>

            </div>

            <div class="mt-4 text-center">
                <button type="submit" class="btn-soft btn-soft-success px-5">
                    Guardar Producto
                </button>
            </div>

        </form>

    </div>
</div>

<!-- Modal Escanear Código de Barras -->
<div class="modal fade" id="modalEscanearCodigoBarras" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Escanear código de barras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div class="barcode-scanner-shell">
                    <div class="barcode-reader-frame">
                        <div id="barcode-reader" class="barcode-reader"></div>
                    </div>

                    <div class="barcode-camera-controls">
                        <label class="barcode-camera-select-wrap d-none" id="barcodeCameraSelectWrap">
                            <span><i class="fas fa-camera-rotate"></i> Cámara</span>
                            <select id="barcodeCameraSelect" class="form-select"></select>
                        </label>
                        <label class="barcode-zoom-control d-none" id="barcodeZoomControl">
                            <span><i class="fas fa-search-plus"></i> Zoom <b id="barcodeZoomValue">1.0×</b></span>
                            <input type="range" id="barcodeZoomRange" min="1" max="3" step="0.1" value="1">
                        </label>
                    </div>

                    <div class="barcode-reader-toolbar">
                        <button type="button"
                            class="btn-soft btn-soft-info barcode-tool-btn d-none"
                            id="btnBarcodeTorch"
                            title="Encender o apagar linterna"
                            aria-label="Encender o apagar linterna">
                            <i class="fas fa-lightbulb"></i>
                            <span>Linterna</span>
                        </button>

                        <button type="button"
                            class="btn-soft btn-soft-info barcode-tool-btn d-none"
                            id="btnBarcodeZoom"
                            title="Activar modo macro"
                            aria-label="Activar modo macro">
                            <i class="fas fa-search-plus"></i>
                            <span>Código pequeño</span>
                        </button>

                        <button type="button"
                            class="btn-soft btn-soft-info barcode-tool-btn"
                            id="btnBarcodePhoto"
                            title="Tomar una foto o elegir una imagen"
                            aria-label="Escanear código desde una fotografía">
                            <i class="fas fa-camera"></i>
                            <span>Usar foto</span>
                        </button>
                        <input type="file" id="barcodePhotoInput" accept="image/*" capture="environment" hidden>
                    </div>

                    <div class="barcode-fallback-actions d-none" id="barcodeFallbackActions">
                        <button type="button" class="btn-soft btn-soft-primary barcode-fallback-btn" id="btnEscanerExterno">
                            <i class="fas fa-barcode"></i>
                            Escanear con app externa
                        </button>
                    </div>

                    <p id="barcodeScannerStatus" class="barcode-scanner-status barcode-scanner-status-info">
                        En móvil usa la cámara. En PC puedes usar una pistola lectora enfocando el campo.
                    </p>

                    <small class="barcode-scanner-tip">
                        Para códigos pequeños usa <b>Código pequeño</b>, ajusta el zoom o toma una foto nítida.
                    </small>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-soft btn-soft-info" id="btnCerrarEscaner">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Nueva Categoría -->

<div class="modal fade" id="modalNuevaCategoria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">Nombre</label>
                <input type="text" id="nueva_categoria_nombre" class="form-control">
                <small id="error_categoria" class="text-danger d-none"></small>
            </div>

            <div class="modal-footer">
                <button class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn-soft btn-soft-success" id="btnGuardarCategoria">Guardar</button>
            </div>

        </div>
    </div>
</div>
<!-- Modal Nueva Marca -->
<div class="modal fade" id="modalNuevaMarca" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            
            <div class="modal-header">
                <h5 class="modal-title">Nueva Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">Nombre</label>
                <input type="text" id="nueva_marca_nombre" class="form-control">
                <small id="error_marca" class="text-danger d-none"></small>
            </div>

            <div class="modal-footer">
                <button class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn-soft btn-soft-success" id="btnGuardarMarca">Guardar</button>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/product-catalog-image-previews.js') }}?v={{ filemtime(public_path('js/product-catalog-image-previews.js')) }}"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="{{ asset('js/validarCodigoBarras.js') }}"></script>
    <script src="{{ asset('js/productoScanner.js') }}?v={{ filemtime(public_path('js/productoScanner.js')) }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(function () {
        $('#categoria_id').select2({
            width: '100%',
            placeholder: 'Seleccione...',
            minimumResultsForSearch: 0,
            dropdownCssClass: 'producto-select2-dropdown'
        });

        $('#marca_id').select2({
            width: '100%',
            placeholder: 'Seleccione...',
            allowClear: true,
            minimumResultsForSearch: 0,
            dropdownCssClass: 'producto-select2-dropdown'
        });

        $('#categoria_id, #marca_id').on('select2:open', function () {
            const buscador = document.querySelector('.select2-container--open .select2-search__field');
            if (buscador) {
                buscador.placeholder = 'Buscar...';
                buscador.focus();
            }
        });

        $('#nueva_categoria_nombre, #nueva_marca_nombre').on('input', function () {
            this.value = this.value.toLocaleUpperCase('es-PE');
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('form-nuevo-producto');
        const nombre = document.getElementById('nombre');
        const descripcion = document.getElementById('descripcion');

        const normalizarNombre = function () {
            nombre.value = nombre.value.toLocaleUpperCase('es-PE');
        };

        const normalizarDescripcion = function () {
            const texto = descripcion.value.toLocaleLowerCase('es-PE');
            descripcion.value = texto.replace(/\p{L}/u, function (letra) {
                return letra.toLocaleUpperCase('es-PE');
            });
        };

        nombre.addEventListener('input', normalizarNombre);
        descripcion.addEventListener('input', normalizarDescripcion);

        form.addEventListener('submit', function () {
            normalizarNombre();
            normalizarDescripcion();
        });
    });
</script>

    
<script>
//mostrar imagen
    document.addEventListener('DOMContentLoaded', function () {

        const input = document.getElementById('imagen');
        const preview = document.getElementById('preview_imagen');

        if (!input || !preview) return;

        input.addEventListener('change', function (e) {

            const file = e.target.files[0];

            if (!file) {
                preview.classList.add('d-none');
                preview.src = '';
                return;
            }

            // Validar que sea imagen
            if (!file.type.startsWith('image/')) {
                alert('El archivo seleccionado no es una imagen');
                input.value = '';
                preview.classList.add('d-none');
                return;
            }

            const reader = new FileReader();

            reader.onload = function (event) {
                preview.src = event.target.result;
                preview.classList.remove('d-none');
            };

            reader.readAsDataURL(file);
        });
    });
</script>


  {{-- ================= JS PRESENTACIONES ================= --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const chkPaquete = document.getElementById('chk_paquete');
        const chkCaja    = document.getElementById('chk_caja');

        const grpUP = document.getElementById('grupo_unidades_paquete');
        const grpPC = document.getElementById('grupo_paquetes_caja');
        const grpUC = document.getElementById('grupo_unidades_caja');

        function ocultar(grupo) {
            grupo.classList.add('d-none');
            const input = grupo.querySelector('input');
            if (input) input.value = '';
        }

        function mostrar(grupo) {
            grupo.classList.remove('d-none');
        }

        function updateUI() {

            // RESET
            ocultar(grpUP);
            ocultar(grpPC);
            ocultar(grpUC);

            // ===== SOLO PAQUETE =====
            if (chkPaquete.checked && !chkCaja.checked) {
                mostrar(grpUP);
            }

            // ===== SOLO CAJA (directo a unidad) =====
            if (!chkPaquete.checked && chkCaja.checked) {
                mostrar(grpUC);
            }

            // ===== PAQUETE + CAJA =====
            if (chkPaquete.checked && chkCaja.checked) {
                mostrar(grpUP);
                mostrar(grpPC);
                // 🚫 NO mostrar unidades_por_caja
            }
        }

        chkPaquete.addEventListener('change', updateUI);
        chkCaja.addEventListener('change', updateUI);

        updateUI();
    });
</script>


<script>
    // GUARDAR CATEGORÍA
    $("#btnGuardarCategoria").click(function () {

        let nombre = $("#nueva_categoria_nombre").val().trim().toLocaleUpperCase('es-PE');
        $("#nueva_categoria_nombre").val(nombre);
        if (!nombre) {
            $("#error_categoria").text("El nombre es obligatorio.").removeClass("d-none");
            return;
        }

        $.post("{{ route('categoria.ajax.store') }}", {
            nombre,
            _token: '{{ csrf_token() }}'
        }, function (response) {

            if (response.error) {
                $("#error_categoria").text(response.message).removeClass("d-none");
                return;
            }

            $("#error_categoria").addClass("d-none");

            const select = $("#categoria_id");

            select.append(
                new Option(response.data.nombre, response.data.id, true, true)
            ).trigger("change");

            $("#modalNuevaCategoria").modal("hide");
            $("#nueva_categoria_nombre").val("");

            Swal.fire("Éxito", "Categoría registrada correctamente.", "success");
        });
    });


    // GUARDAR MARCA
    $("#btnGuardarMarca").click(function () {

        let nombre = $("#nueva_marca_nombre").val().trim().toLocaleUpperCase('es-PE');
        $("#nueva_marca_nombre").val(nombre);
        if (!nombre) {
            $("#error_marca").text("El nombre es obligatorio.").removeClass("d-none");
            return;
        }

        $.post("{{ route('marca.ajax.store') }}", {
            nombre,
            _token: '{{ csrf_token() }}'
        }, function (response) {

            if (response.error) {
                $("#error_marca").text(response.message).removeClass("d-none");
                return;
            }

            $("#error_marca").addClass("d-none");

            const select = $("#marca_id");

            select.append(
                new Option(response.data.nombre, response.data.id, true, true)
            ).trigger("change");

            $("#modalNuevaMarca").modal("hide");
            $("#nueva_marca_nombre").val("");

            Swal.fire("Éxito", "Marca registrada correctamente.", "success");
        });
    });
</script>

@endpush
