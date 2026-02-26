@extends('layouts.app')

@push('styles')
<link href="{{ asset('css/edit_productos.css') }}" rel="stylesheet" />
@endpush

{{-- BOTÓN ATRÁS --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Editar Producto
@endsection

{{-- BOTONES DERECHA --}}
@section('header-buttons')

@endsection

@section('content')
<div class="card ui-card container-card my-4">

    <div class="card-header text-center pt-4">
        <h4 class="mb-0 fw-semibold">
            <i class="fas fa-box me-2 text-primary"></i> Editar Producto
        </h4>
    </div>

    <div class="card-body pt-2 pb-4">

        {{-- ERRORES --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <input type="hidden" id="producto_id" value="{{ $producto->id }}">

        <form action="{{ route('productos.update', $producto->id) }}"
              method="POST"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

        <div class="row g-3">

            {{-- ================= DATOS BÁSICOS ================= --}}
            <div class="col-md-4">
                <label class="form-label">Código de Barras</label>
                <input type="text"
                       name="codigo_barras"
                       id="codigo_barras"
                       class="form-control ui-input"
                       value="{{ old('codigo_barras', $producto->codigo_barras) }}">

                <div id="codigo_barras_error"
                     class="invalid-feedback d-none">
                    Este código de barras ya está registrado.
                </div>
            </div>

            <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text"
                       name="nombre"
                       class="form-control ui-input"
                       value="{{ old('nombre', $producto->nombre) }}"
                       required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Ubicación</label>
                <input type="text"
                       name="ubicacion"
                       class="form-control ui-input"
                       value="{{ old('ubicacion', $producto->ubicacion) }}">
            </div>

            <div class="col-12">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion"
                          class="form-control ui-input"
                          rows="2">{{ old('descripcion', $producto->descripcion) }}</textarea>
            </div>

            {{-- ================= PRESENTACIONES ================= --}}
            <div class="col-12 mt-4">
                <div class="ui-section-title">
                    <i class="fas fa-layer-group me-2 text-primary"></i>
                    Presentaciones
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" checked disabled>
                    <label class="form-check-label">
                        Unidad (siempre disponible)
                    </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           name="usa_paquete"
                           id="chk_paquete"
                           {{ $producto->unidades_por_paquete ? 'checked' : '' }}>
                    <label class="form-check-label">Paquete</label>
                </div>

                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           name="usa_caja"
                           id="chk_caja"
                           {{ ($producto->paquetes_por_caja || $producto->unidades_por_caja) ? 'checked' : '' }}>
                    <label class="form-check-label">Caja</label>
                </div>
            </div>

            {{-- ================= CONVERSIONES ================= --}}
            <div class="row mt-2">

                <div class="col-md-4 {{ $producto->unidades_por_paquete ? '' : 'd-none' }}"
                     id="grupo_unidades_paquete">
                    <label class="form-label">Unidades por paquete</label>
                    <input type="number"
                           name="unidades_por_paquete"
                           class="form-control ui-input"
                           min="1"
                           value="{{ old('unidades_por_paquete', $producto->unidades_por_paquete) }}">
                </div>

                <div class="col-md-4 {{ $producto->paquetes_por_caja ? '' : 'd-none' }}"
                     id="grupo_paquetes_caja">
                    <label class="form-label">Paquetes por caja</label>
                    <input type="number"
                           name="paquetes_por_caja"
                           class="form-control ui-input"
                           min="1"
                           value="{{ old('paquetes_por_caja', $producto->paquetes_por_caja) }}">
                </div>

                <div class="col-md-4 {{ $producto->unidades_por_caja ? '' : 'd-none' }}"
                     id="grupo_unidades_caja">
                    <label class="form-label">Unidades por caja</label>
                    <input type="number"
                           name="unidades_por_caja"
                           class="form-control ui-input"
                           min="1"
                           value="{{ old('unidades_por_caja', $producto->unidades_por_caja) }}">
                </div>

            </div>

            {{-- ================= VENCIMIENTO ================= --}}
            <div class="col-md-4 mt-3">
                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           name="maneja_vencimiento"
                           value="1"
                           {{ old('maneja_vencimiento', $producto->maneja_vencimiento) ? 'checked' : '' }}>
                    <label class="form-check-label">
                        Maneja fecha de vencimiento
                    </label>
                </div>
            </div>

            {{-- ================= CATEGORÍA / MARCA ================= --}}
            <div class="col-md-4">
                <label class="form-label d-flex justify-content-between">
                    <span>Categoría</span>
                    <button type="button"
                            class="btn-soft btn-soft-primary btn-soft-icon"
                            data-bs-toggle="modal"
                            data-bs-target="#modalNuevaCategoria">
                        <i class="fas fa-plus"></i>
                    </button>
                </label>

                <select name="categoria_id"
                        id="categoria_id"
                        class="form-select ui-input"
                        required>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}"
                            {{ old('categoria_id', $producto->categoria_id) == $categoria->id ? 'selected' : '' }}>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label d-flex justify-content-between">
                    <span>Marca</span>
                    <button type="button"
                            class="btn-soft btn-soft-primary btn-soft-icon"
                            data-bs-toggle="modal"
                            data-bs-target="#modalNuevaMarca">
                        <i class="fas fa-plus"></i>
                    </button>
                </label>

                <select name="marca_id"
                        id="marca_id"
                        class="form-select ui-input">
                    @foreach($marcas as $marca)
                        <option value="{{ $marca->id }}"
                            {{ old('marca_id', $producto->marca_id) == $marca->id ? 'selected' : '' }}>
                            {{ $marca->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- ================= IMAGEN ================= --}}
            <div class="col-md-4">
                <label class="form-label">Imagen</label>
                <input type="file"
                       name="imagen"
                       id="imagen"
                       class="form-control ui-input"
                       accept="image/*">

                <img id="preview_imagen"
                     src="{{ $producto->imagen ? asset('uploads/productos/'.$producto->imagen) : '' }}"
                     class="ui-product-preview mt-2 {{ $producto->imagen ? '' : 'd-none' }}"
                     style="max-height:150px;">
            </div>

            {{-- ================= ESTADO ================= --}}
            <div class="col-md-4 mt-3">
                <div class="form-check">
                    <input class="form-check-input"
                           type="checkbox"
                           name="activo"
                           value="1"
                           {{ old('activo', $producto->activo) ? 'checked' : '' }}>
                    <label class="form-check-label">Activo</label>
                </div>

                <div class="form-check mt-2">
                    <input class="form-check-input"
                           type="checkbox"
                           name="visible_en_catalogo"
                           value="1"
                           {{ old('visible_en_catalogo', $producto->visible_en_catalogo) ? 'checked' : '' }}>
                    <label class="form-check-label">Visible en catálogo</label>
                </div>
            </div>

        </div>

        <div class="mt-4 text-center">
            <button type="submit" class="btn-soft btn-soft-success px-5 py-2">
                Actualizar Producto
            </button>
        </div>

        </form>
    </div>
</div>

<!-- Modal Nueva Categoría -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ui-card">

            <div class="modal-header">
                <h5 class="modal-title fw-semibold">
                    Nueva Categoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">Nombre</label>
                <input type="text"
                       id="nueva_categoria_nombre"
                       class="form-control ui-input">

                <small id="error_categoria"
                       class="text-danger d-none"></small>
            </div>

            <div class="modal-footer">
                <button class="btn-soft btn-soft-info"
                        data-bs-dismiss="modal">
                    Cerrar
                </button>

                <button type="button"
                        class="btn-soft btn-soft-success"
                        id="btnGuardarCategoria">
                    Guardar
                </button>
            </div>

        </div>
    </div>
</div>
<!-- Modal Nueva Marca -->
<div class="modal fade" id="modalNuevaMarca" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ui-card">

            <div class="modal-header">
                <h5 class="modal-title fw-semibold">
                    Nueva Marca
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <label class="form-label">Nombre</label>
                <input type="text"
                       id="nueva_marca_nombre"
                       class="form-control ui-input">

                <small id="error_marca"
                       class="text-danger d-none"></small>
            </div>

            <div class="modal-footer">
                <button class="btn-soft btn-soft-info"
                        data-bs-dismiss="modal">
                    Cerrar
                </button>

                <button type="button"
                        class="btn-soft btn-soft-success"
                        id="btnGuardarMarca">
                    Guardar
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    /* ==========================
    VALIDAR CÓDIGO DE BARRAS
    ========================== */
    document.addEventListener("DOMContentLoaded", () => {
        const input = document.getElementById("codigo_barras");
        const errorDiv = document.getElementById("codigo_barras_error");
        const productoId = document.getElementById("producto_id")?.value;

        if (!input || !errorDiv) return;

        let timer;

        input.addEventListener("input", () => {
            const codigo = input.value.trim();
            clearTimeout(timer);

            if (!codigo) {
                input.classList.remove("is-invalid");
                errorDiv.classList.add("d-none");
                return;
            }

            timer = setTimeout(() => {
                fetch(`{{ route('productos.validarCodigoBarras') }}?codigo_barras=${codigo}&producto_id=${productoId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.exists) {
                            input.classList.add("is-invalid");
                            errorDiv.classList.remove("d-none");
                        } else {
                            input.classList.remove("is-invalid");
                            errorDiv.classList.add("d-none");
                        }
                    });
            }, 300);
        });
    });
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const chkPaquete = document.getElementById('chk_paquete');
    const chkCaja    = document.getElementById('chk_caja');

    const grupoUP = document.getElementById('grupo_unidades_paquete');
    const grupoPC = document.getElementById('grupo_paquetes_caja');
    const grupoUC = document.getElementById('grupo_unidades_caja');

    // Si no existen (por seguridad), no romper
    if (!chkPaquete || !chkCaja) return;

    function actualizar() {

        // ===== PAQUETE =====
        if (chkPaquete.checked) {
            grupoUP.classList.remove('d-none');
        } else {
            grupoUP.classList.add('d-none');
            grupoPC.classList.add('d-none');
        }

        // ===== CAJA =====
        if (chkCaja.checked) {

            if (chkPaquete.checked) {
                // Caja basada en paquetes
                grupoPC.classList.remove('d-none');
                grupoUC.classList.add('d-none');
            } else {
                // Caja directa
                grupoUC.classList.remove('d-none');
                grupoPC.classList.add('d-none');
            }

        } else {
            grupoPC.classList.add('d-none');
            grupoUC.classList.add('d-none');
        }
    }

    chkPaquete.addEventListener('change', actualizar);
    chkCaja.addEventListener('change', actualizar);

    actualizar(); // inicializa según estado actual
});
</script>

<script>
    /* ==========================
    PREVIEW IMAGEN
    ========================== */
    document.getElementById('imagen')?.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('preview_imagen');
            img.src = e.target.result;
            img.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });
</script>

<script>
    $(document).on('click', '#btnGuardarCategoria', function () {

        let nombre = $("#nueva_categoria_nombre").val().trim();

        if (!nombre) {
            $("#error_categoria").text("El nombre es obligatorio.").removeClass("d-none");
            return;
        }

        $.post("{{ route('categoria.ajax.store') }}", {
            nombre: nombre,
            _token: '{{ csrf_token() }}'
        }, function (response) {

            if (response.error) {
                $("#error_categoria").text(response.message).removeClass("d-none");
                return;
            }

            $("#error_categoria").addClass("d-none").text("");

            // 👇 CLAVE: agregar y seleccionar
            $("#categoria_id").append(
                new Option(response.data.nombre, response.data.id, true, true)
            );

            $("#modalNuevaCategoria").modal("hide");
            $("#nueva_categoria_nombre").val("");

            Swal.fire("Éxito", "Categoría registrada correctamente.", "success");
        });
    });

    $(document).on('click', '#btnGuardarMarca', function () {

        let nombre = $("#nueva_marca_nombre").val().trim();

        if (!nombre) {
            $("#error_marca").text("El nombre es obligatorio.").removeClass("d-none");
            return;
        }

        $.post("{{ route('marca.ajax.store') }}", {
            nombre: nombre,
            _token: '{{ csrf_token() }}'
        }, function (response) {

            if (response.error) {
                $("#error_marca").text(response.message).removeClass("d-none");
                return;
            }

            $("#error_marca").addClass("d-none").text("");

            $("#marca_id").append(
                new Option(response.data.nombre, response.data.id, true, true)
            );

            $("#modalNuevaMarca").modal("hide");
            $("#nueva_marca_nombre").val("");

            Swal.fire("Éxito", "Marca registrada correctamente.", "success");
        });
    });

</script>
@endpush
