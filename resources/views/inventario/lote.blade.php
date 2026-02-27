@extends('layouts.app')

{{-- BOTÓN ATRÁS --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Ingreso de Mercadería
@endsection

{{-- ACCIONES DEL HEADER --}}
@section('header-buttons')
<a href="{{ route('inventario.lotes') }}" class="btn-gasto">
    <i class="fas fa-layer-group"></i>
    <span class="btn-text">Ver lotes</span>
</a>
@endsection

@section('content')
<div class="card ui-card container-card my-4">

        <div class="card-header text-center pt-4">
            <h4 class="mb-0 fw-semibold">
                <i class="fas fa-truck-loading me-2 text-primary"></i>
                Ingreso de mercadería por lote
            </h4>
        </div>

        <div class="card-body pt-2 pb-4">

            <form action="{{ route('inventario.lote.store') }}" method="POST">
                @csrf

                <div class="row g-4">

                    {{-- ================= COLUMNA IZQUIERDA ================= --}}
                    <div class="col-lg-6">
                        <div class="mb-3 inv-section-title">
                            <span class="dot"></span> Producto / Proveedor
                        </div>

                        {{-- PRODUCTO --}}
                        <div class="mb-3">
                            <label class="inv-label">Producto</label>

                            <div class="d-flex gap-2 align-items-stretch">
                                <select name="producto_id"
                                        id="producto-select"
                                        class="form-select ui-input"
                                        required>
                                    <option value="">Buscar producto...</option>
                                    @foreach($productos as $producto)
                                        <option value="{{ $producto->id }}"
                                            data-vencimiento="{{ $producto->maneja_vencimiento }}"
                                            data-descripcion="{{ \Illuminate\Support\Str::limit($producto->descripcion, 40) }}">
                                            {{ $producto->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                                <a href="{{ route('productos.create', ['from' => 'lotes']) }}"
                                class="btn-soft btn-soft-primary btn-soft-icon"
                                title="Agregar nuevo producto">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                        </div>

                        {{-- PROVEEDOR --}}
                        <div class="mb-3">
                            <label class="inv-label">Proveedor</label>

                            <div class="d-flex gap-2 align-items-stretch">
                                <select name="proveedor_id"
                                        id="proveedor-select"
                                        class="form-select ui-input">
                                    <option value="">— Sin proveedor —</option>
                                    @foreach($proveedores as $proveedor)
                                        <option value="{{ $proveedor->id }}"
                                            data-doc="{{ $proveedor->tipo_documento }} {{ $proveedor->numero_documento }}">
                                            {{ $proveedor->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                                <a href="{{ route('proveedores.index') }}"
                                class="btn-soft btn-soft-success btn-soft-icon"
                                title="Agregar nuevo proveedor">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                        </div>

                        <div class="ui-tip mt-3">
                            <i class="fas fa-lightbulb me-2"></i>
                            <span>Busca por nombre o presentación del producto.</span>
                        </div>
                    </div>

                    {{-- ================= COLUMNA DERECHA ================= --}}
                    <div class="col-lg-6">
                        <div class="mb-3 inv-section-title" style="color:#0f5132;">
                            <span class="dot" style="background:#14a44d; box-shadow:0 0 0 5px rgba(20,164,77,.12)"></span>
                            Datos del lote
                        </div>

                        {{-- STOCK + COSTOS --}}
                        <div class="mb-3">
                            <label class="inv-label">
                                Cd. Comprobante
                                <small class="text-muted">(opcional)</small>
                            </label>
                            <input type="text"
                                    name="codigo_comprobante"
                                    class="form-control ui-input"
                                    placeholder="E- 0000"
                                    value="{{ old('codigo_lote') }}">
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="inv-label">Cantidad (unds)</label>
                                <input type="number" name="stock_inicial" class="form-control ui-input" min="1" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="inv-label">Costo compra (S/)</label>
                                <input type="number" name="precio_compra" class="form-control ui-input" step="0.001" min="0" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="inv-label">Precio unidad (S/)</label>
                                <input type="number" name="precio_unidad" class="form-control ui-input" step="0.001" min="0" required>
                            </div>
                        </div>

                        {{-- PRESENTACIONES --}}
                        <h6 class="fw-semibold mb-2 text-muted">
                            Presentaciones disponibles (marca las que aplican)
                        </h6>
                        {{-- PRECIOS OPCIONALES --}}
                        <div class="row">

                            {{-- PRECIO PAQUETE --}}
                            <div class="col-md-6 mb-3">
                                <label class="inv-label d-flex align-items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="chk_precio_paquete"
                                        class="form-check-input mt-0"
                                    >
                                    Precio paquete (S/)
                                </label>

                                <input
                                    type="number"
                                    name="precio_paquete"
                                    id="input_precio_paquete"
                                    class="form-control inv-input d-none"
                                    step="0.001"
                                    min="0"
                                >
                            </div>

                            {{-- PRECIO CAJA --}}
                            <div class="col-md-6 mb-3">
                                <label class="inv-label d-flex align-items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="chk_precio_caja"
                                        class="form-check-input mt-0"
                                    >
                                    Precio caja (S/)
                                </label>

                                <input
                                    type="number"
                                    name="precio_caja"
                                    id="input_precio_caja"
                                    class="form-control inv-input d-none"
                                    step="0.001"
                                    min="0"
                                >
                            </div>

                        </div>

                        {{-- FECHAS --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="inv-label">Fecha ingreso</label>
                                <input
                                    type="text"
                                    name="fecha_ingreso"
                                    class="form-control ui-input date-ingreso"
                                    value="{{ now()->format('Y-m-d') }}"
                                    required
                                >
                            </div>

                            <div class="col-md-6 mb-3" id="grupo-vencimiento" style="display:none;">
                                <label class="inv-label">Fecha vencimiento</label>
                                <input
                                    type="text"
                                    name="fecha_vencimiento"
                                    class="form-control ui-input date-vencimiento"
                                >
                            </div>
                        </div>

                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top">
                    <a href="{{ route('inventario.resumen') }}" class="btn-soft btn-soft-info">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-soft btn-soft-success px-5">
                        <i class="fas fa-save me-2"></i> Guardar lote
                    </button>
                </div>

            </form>

        </div>

</div>
@endsection

{{-- ===================== STYLES ===================== --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
<link rel="stylesheet" href="{{ asset('css/lote.css') }}">
@endpush

{{-- ===================== SCRIPTS ===================== --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<script>
    flatpickr(".date-ingreso", {
        locale: "es",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d F Y",
        disableMobile: true
    });

    flatpickr(".date-vencimiento", {
        locale: "es",
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d F Y",
        minDate: "today",
        disableMobile: true
    });
</script>

<script>
    $(document).ready(function () {

        // ===============================
        // FORMATO SELECT2 PRODUCTOS
        // ===============================
        function formatProducto(producto) {
            if (!producto.id) return producto.text;

            const descripcion = producto.element.dataset.descripcion || '';

            return $(`
                <div style="line-height:1.25">
                    <div style="font-weight:600;">
                        ${producto.text}
                    </div>
                    ${
                        descripcion
                            ? `<div style="font-size:12px; color:#6c757d;">
                                ${descripcion}
                            </div>`
                            : ''
                    }
                </div>
            `);
        }

        // ===============================
        // SELECT PRODUCTO
        // ===============================
        $('#producto-select').select2({
            placeholder: 'Buscar producto...',
            allowClear: true,
            width: '100%',
            templateResult: formatProducto,
            templateSelection: formatProducto,
            escapeMarkup: m => m
        });

        // ===============================
        // MOSTRAR / OCULTAR VENCIMIENTO
        // ===============================
        $('#producto-select').on('change', function () {
            const vence = $(this).find(':selected').data('vencimiento');
            if (vence == 1) {
                $('#grupo-vencimiento').slideDown();
            } else {
                $('#grupo-vencimiento').slideUp();
                $('input[name="fecha_vencimiento"]').val('');
            }
        });

        // ===============================
        // FORMATO SELECT2 PROVEEDOR
        // ===============================
        function formatProveedor(proveedor) {
            if (!proveedor.id) return proveedor.text;

            const doc = proveedor.element.dataset.doc || '';

            return $(`
                <div style="line-height:1.25">
                    <div style="font-weight:600;">
                        ${proveedor.text}
                    </div>
                    ${
                        doc
                            ? `<div style="font-size:12px; color:#6c757d;">
                                ${doc}
                            </div>`
                            : ''
                    }
                </div>
            `);
        }

        // ===============================
        // SELECT PROVEEDOR
        // ===============================
        $('#proveedor-select').select2({
            placeholder: 'Buscar proveedor...',
            allowClear: true,
            width: '100%',
            templateResult: formatProveedor,
            templateSelection: formatProveedor,
            escapeMarkup: m => m
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {

        const chkPaquete = document.getElementById("chk_precio_paquete");
        const inputPaquete = document.getElementById("input_precio_paquete");

        const chkCaja = document.getElementById("chk_precio_caja");
        const inputCaja = document.getElementById("input_precio_caja");

        chkPaquete.addEventListener("change", () => {
            inputPaquete.classList.toggle("d-none", !chkPaquete.checked);
            if (!chkPaquete.checked) inputPaquete.value = "";
        });

        chkCaja.addEventListener("change", () => {
            inputCaja.classList.toggle("d-none", !chkCaja.checked);
            if (!chkCaja.checked) inputCaja.value = "";
        });

    });
</script>
@endpush
