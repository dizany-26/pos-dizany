@extends('layouts.app')

@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

@section('header-title')
Ingreso de inventario
@endsection

@section('header-buttons')
<a href="{{ route('inventario.lotes') }}" class="btn-gasto">
    <i class="fas fa-layer-group"></i>
    <span class="btn-text">Ver inventario</span>
</a>
@endsection

@section('content')
<div class="inventory-entry-shell my-4">
    <div class="inventory-entry-heading">
        <div>
            <span class="inventory-entry-kicker">ABASTECIMIENTO</span>
            <h2><i class="fas fa-boxes-stacked"></i> Nuevo ingreso de inventario</h2>
            <p>Registra la compra, crea el lote y actualiza el stock en una sola operación.</p>
        </div>
        <div class="inventory-entry-security">
            <i class="fas fa-shield-halved"></i>
            <span><strong>Independiente de caja</strong>La compra no altera el efectivo del cajón.</span>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rounded-4">
            <strong>Revisa la información ingresada.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('inventario.lote.store') }}" method="POST" id="formIngresoInventario">
        @csrf

        <section class="inventory-entry-card">
            <div class="inventory-step">
                <span>1</span>
                <div><strong>Documento de compra</strong><small>Proveedor, comprobante y condición de pago</small></div>
            </div>

            @if(!empty($compraEnCurso))
                <div class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-2 rounded-4 mb-3">
                    <div><i class="fas fa-link me-2"></i><strong>Compra en curso.</strong> Los datos del documento se conservarán al agregar otro producto.</div>
                    <button type="submit" formnovalidate formaction="{{ route('inventario.compra-en-curso.limpiar') }}" formmethod="POST" class="btn-soft inventory-new-purchase-btn"><i class="fas fa-file-circle-plus"></i> Nueva compra</button>
                </div>
            @endif

            <div class="row g-3">
                <div class="col-lg-5">
                    <label class="inv-label" for="proveedor-select">Proveedor</label>
                    <div class="inventory-select-row">
                        <select name="proveedor_id" id="proveedor-select" class="form-select ui-input">
                            <option value="">Seleccionar proveedor...</option>
                            @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}"
                                    data-doc="{{ $proveedor->tipo_documento }} {{ $proveedor->numero_documento }}"
                                    @selected(old('proveedor_id', data_get($compraEnCurso, 'proveedor_id')) == $proveedor->id)>
                                    {{ $proveedor->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn-soft btn-soft-success btn-soft-icon" title="Nuevo proveedor" aria-label="Nuevo proveedor" data-bs-toggle="modal" data-bs-target="#modalProveedorInventario">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <small class="field-help">Obligatorio si la compra queda pendiente de pago.</small>
                </div>

                <div class="col-md-3 col-lg-2">
                    <label class="inv-label" for="tipo_comprobante">Comprobante</label>
                    <select name="tipo_comprobante" id="tipo_comprobante" class="form-select ui-input">
                        <option value="">Sin comprobante</option>
                        <option value="factura" @selected(old('tipo_comprobante', data_get($compraEnCurso, 'tipo_comprobante')) === 'factura')>Factura</option>
                        <option value="boleta" @selected(old('tipo_comprobante', data_get($compraEnCurso, 'tipo_comprobante')) === 'boleta')>Boleta</option>
                        <option value="nota_venta" @selected(old('tipo_comprobante', data_get($compraEnCurso, 'tipo_comprobante')) === 'nota_venta')>Nota de venta</option>
                        <option value="guia" @selected(old('tipo_comprobante', data_get($compraEnCurso, 'tipo_comprobante')) === 'guia')>Guía</option>
                        <option value="otro" @selected(old('tipo_comprobante', data_get($compraEnCurso, 'tipo_comprobante')) === 'otro')>Otro</option>
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="inv-label" for="codigo_comprobante">Serie y número</label>
                    <input type="text" name="codigo_comprobante" id="codigo_comprobante"
                        class="form-control ui-input" placeholder="F001-000123"
                        value="{{ old('codigo_comprobante', data_get($compraEnCurso, 'codigo_comprobante')) }}">
                </div>

                <div class="col-md-5 col-lg-3">
                    <label class="inv-label">Fecha de ingreso</label>
                    <input type="text" name="fecha_ingreso" class="form-control ui-input date-ingreso"
                        value="{{ old('fecha_ingreso', data_get($compraEnCurso, 'fecha_ingreso', now()->format('Y-m-d'))) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="inv-label d-block">Condición de pago</label>
                    <div class="payment-choice">
                        <label>
                            <input type="radio" name="condicion_pago" value="contado"
                                @checked(old('condicion_pago', data_get($compraEnCurso, 'condicion_pago', 'contado')) === 'contado')>
                            <span><i class="fas fa-circle-check"></i><strong>Pagado</strong><small>La compra ya fue cancelada</small></span>
                        </label>
                        <label>
                            <input type="radio" name="condicion_pago" value="credito"
                                @checked(old('condicion_pago', data_get($compraEnCurso, 'condicion_pago')) === 'credito')>
                            <span><i class="fas fa-clock"></i><strong>Por pagar</strong><small>Se registrará como deuda</small></span>
                        </label>
                    </div>
                </div>

                <div class="col-md-3" id="grupoMetodoPago">
                    <label class="inv-label" for="metodo_pago">Medio utilizado</label>
                    <select name="metodo_pago" id="metodo_pago" class="form-select ui-input">
                        <option value="">Seleccionar...</option>
                        @foreach(['efectivo' => 'Efectivo externo', 'yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', 'otro' => 'Otro'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('metodo_pago', data_get($compraEnCurso, 'metodo_pago')) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="field-help">No se descuenta de la caja operativa.</small>
                </div>

                <div class="col-md-3 d-none" id="grupoVencimientoPago">
                    <label class="inv-label">Vencimiento del pago</label>
                    <input type="text" name="fecha_vencimiento_pago"
                        class="form-control ui-input date-pago"
                        value="{{ old('fecha_vencimiento_pago', data_get($compraEnCurso, 'fecha_vencimiento_pago')) }}">
                </div>
            </div>
        </section>

        <section class="inventory-entry-card" id="producto-y-lote">
            <div class="inventory-step">
                <span>2</span>
                <div><strong>Producto y lote</strong><small>Artículo, cantidad, costo y vencimiento</small></div>
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    <label class="inv-label">Producto</label>
                    <div class="inventory-select-row">
                        <select name="producto_id" id="producto-select" class="form-select ui-input" required>
                            <option value="">Buscar producto...</option>
                            @foreach($productos as $producto)
                                <option value="{{ $producto->id }}"
                                    data-vencimiento="{{ $producto->maneja_vencimiento }}"
                                    data-precio-unidad="{{ $producto->precio_venta }}"
                                    data-precio-paquete="{{ $producto->precio_paquete }}"
                                    data-precio-caja="{{ $producto->precio_caja }}"
                                    data-stock-minimo="{{ $producto->stock_minimo ?? 10 }}"
                                    data-descripcion="{{ \Illuminate\Support\Str::limit($producto->descripcion, 45) }}"
                                    @selected(old('producto_id') == $producto->id)>
                                    {{ $producto->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn-soft btn-soft-primary btn-soft-icon" title="Nuevo producto" aria-label="Nuevo producto" data-bs-toggle="modal" data-bs-target="#modalProductoInventario">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="inv-label" for="stock_inicial">Cantidad recibida</label>
                    <div class="input-group">
                        <input type="number" name="stock_inicial" id="stock_inicial"
                            class="form-control ui-input" min="1" step="1"
                            value="{{ old('stock_inicial') }}" required>
                        <span class="input-group-text">unds.</span>
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="inv-label" for="precio_compra">Costo por unidad</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" name="precio_compra" id="precio_compra"
                            class="form-control ui-input" min="0" step="0.000001"
                            value="{{ old('precio_compra') }}" required>
                    </div>
                </div>

                <div class="col-md-4 col-lg-3" id="grupo-vencimiento" style="display:none;">
                    <label class="inv-label">Vencimiento del producto</label>
                    <input type="text" name="fecha_vencimiento"
                        class="form-control ui-input date-vencimiento"
                        placeholder="Hoy: {{ now()->format('d/m/Y') }}"
                        value="{{ old('fecha_vencimiento') }}">
                    <small class="field-help">Selecciona la fecha de vencimiento real.</small>
                </div>
            </div>

            <div class="purchase-summary mt-4">
                <span><small>Cantidad</small><strong id="resumenCantidad">0 unds.</strong></span>
                <i class="fas fa-xmark"></i>
                <span><small>Costo unitario</small><strong id="resumenCosto">S/ 0.00</strong></span>
                <i class="fas fa-equals"></i>
                <span class="purchase-summary-total"><small>Total de compra</small><strong id="resumenTotal">S/ 0.00</strong></span>
            </div>
        </section>

        <section class="inventory-entry-card">
            <div class="inventory-step">
                <span>3</span>
                <div><strong>Precios de venta</strong><small>Confirma los precios públicos vigentes</small></div>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="inv-label">Precio por unidad</label>
                    <div class="input-group">
                        <span class="input-group-text">S/</span>
                        <input type="number" name="precio_unidad" id="input_precio_unidad"
                            class="form-control ui-input" step="0.01" min="0"
                            value="{{ old('precio_unidad') }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="inv-label d-flex gap-2 align-items-center">
                        <input type="checkbox" id="chk_precio_paquete" class="form-check-input mt-0"
                            @checked(old('precio_paquete') !== null)>
                        Precio por paquete
                    </label>
                    <div class="input-group {{ old('precio_paquete') === null ? 'd-none' : '' }}" id="grupoPrecioPaquete">
                        <span class="input-group-text">S/</span>
                        <input type="number" name="precio_paquete" id="input_precio_paquete"
                            class="form-control ui-input" step="0.01" min="0"
                            value="{{ old('precio_paquete') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="inv-label d-flex gap-2 align-items-center">
                        <input type="checkbox" id="chk_precio_caja" class="form-check-input mt-0"
                            @checked(old('precio_caja') !== null)>
                        Precio por caja
                    </label>
                    <div class="input-group {{ old('precio_caja') === null ? 'd-none' : '' }}" id="grupoPrecioCaja">
                        <span class="input-group-text">S/</span>
                        <input type="number" name="precio_caja" id="input_precio_caja"
                            class="form-control ui-input" step="0.01" min="0"
                            value="{{ old('precio_caja') }}">
                    </div>
                </div>
                <div class="col-lg-4">
                    <input type="hidden" name="actualizar_precio_producto" value="0">
                    <label class="inventory-switch">
                        <input type="checkbox" name="actualizar_precio_producto" value="1"
                            @checked(old('actualizar_precio_producto', '1') == '1')>
                        <span></span>
                        Aplicar estos precios como precios públicos vigentes
                    </label>
                </div>
                <div class="col-lg-4">
                    <label class="inv-label" for="stock_minimo">Alerta de stock bajo</label>
                    <div class="input-group">
                        <input type="number" name="stock_minimo" id="stock_minimo" class="form-control ui-input"
                            min="0" step="1" value="{{ old('stock_minimo', 10) }}" required>
                        <span class="input-group-text">unds.</span>
                    </div>
                    <small class="field-help">Se alertará cuando el stock total llegue a esta cantidad.</small>
                </div>
                <div class="col-lg-4">
                    <label class="inv-label">Observación de la compra</label>
                    <input type="text" name="observaciones_compra" class="form-control ui-input"
                        maxlength="500" placeholder="Opcional" value="{{ old('observaciones_compra', data_get($compraEnCurso, 'observaciones_compra')) }}">
                </div>
            </div>
        </section>

        <div class="inventory-entry-footer">
            <a href="{{ route('inventario.resumen') }}" class="btn-soft btn-soft-info">Cancelar</a>
            <button type="submit" class="btn-soft btn-soft-success px-4" id="btnGuardarIngreso">
                <i class="fas fa-check me-2"></i>Registrar ingreso
            </button>
        </div>
    </form>
</div>

<div class="modal fade" id="modalProveedorInventario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form id="formNuevoProveedor" class="modal-content" action="{{ route('proveedores.store') }}" method="POST">
            @csrf
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-truck me-2 text-success"></i>Nuevo proveedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body provider-modal-body">
                <div class="alert alert-danger d-none inventory-modal-error" role="alert"></div>
                <section class="provider-form-section">
                    <div class="provider-section-heading"><span class="provider-section-icon"><i class="fas fa-id-card"></i></span><div><strong>Identificación del proveedor</strong><small>Consulta el documento para completar sus datos automáticamente.</small></div></div>
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="inv-proveedor-tipo">Tipo de documento</label><select id="inv-proveedor-tipo" name="tipo_documento" class="form-select ui-input" required><option value="RUC">RUC</option><option value="DNI">DNI</option><option value="OTRO">OTRO</option></select></div>
                        <div class="col-md-8"><label class="form-label" for="inv-proveedor-documento">Número de documento</label><div class="input-group provider-document-input"><input id="inv-proveedor-documento" name="numero_documento" class="form-control ui-input" maxlength="11" autocomplete="off" required><button type="button" id="inv-consultar-proveedor" class="btn btn-primary"><i class="fas fa-search"></i><span>Consultar</span></button></div><div id="inv-proveedor-estado" class="provider-query-status" aria-live="polite"></div></div>
                        <div class="col-12"><label class="form-label" for="inv-proveedor-nombre">Razón social o nombre</label><div class="input-icon-field"><i class="fas fa-building"></i><input id="inv-proveedor-nombre" name="nombre" class="form-control ui-input" maxlength="150" required></div></div>
                    </div>
                </section>
                <section class="provider-form-section">
                    <div class="provider-section-heading"><span class="provider-section-icon provider-section-icon-green"><i class="fas fa-address-book"></i></span><div><strong>Datos de contacto</strong><small>Información para comunicarse con el proveedor.</small></div></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Persona de contacto</label><input name="contacto" class="form-control ui-input" maxlength="255"></div>
                        <div class="col-md-6"><label class="form-label">Teléfono</label><input name="telefono" type="tel" class="form-control ui-input" maxlength="30"></div>
                        <div class="col-md-6"><label class="form-label">Correo electrónico</label><input name="email" type="email" class="form-control ui-input" maxlength="255"></div>
                        <div class="col-md-6"><label class="form-label">Dirección fiscal</label><input id="inv-proveedor-direccion" name="direccion" class="form-control ui-input" maxlength="255"></div>
                    </div>
                </section>
            </div>
            <div class="modal-footer"><button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn-soft btn-soft-success">Guardar proveedor</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalProductoInventario" tabindex="-1" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form id="formProductoInventario" class="modal-content" action="{{ route('productos.store') }}" method="POST">
            @csrf
            <div class="modal-header"><h5 class="modal-title"><i class="fas fa-box-open me-2 text-primary"></i>Nuevo producto</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body">
                <div class="alert alert-danger d-none inventory-modal-error" role="alert"></div>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Código de barras</label><input id="inv-producto-codigo" name="codigo_barras" class="form-control ui-input" inputmode="numeric" autocomplete="off" maxlength="50"></div>
                    <div class="col-md-4"><label class="form-label" for="inv-producto-nombre">Nombre</label><input id="inv-producto-nombre" name="nombre" class="form-control ui-input" maxlength="255" required></div>
                    <div class="col-md-4"><label class="form-label">Ubicación</label><input name="ubicacion" class="form-control ui-input" maxlength="255"></div>
                    <div class="col-12"><label class="form-label">Descripción</label><textarea name="descripcion" class="form-control ui-input" rows="2"></textarea></div>
                    <div class="col-12"><div class="ui-section-box p-3 rounded-4 border"><div class="ui-section-title mb-2"><i class="fas fa-layer-group me-2 text-primary"></i>Presentaciones</div><label class="form-check-label d-block mb-2"><input type="checkbox" class="form-check-input me-2" checked disabled>Unidad (siempre disponible)</label><label class="form-check-label me-4"><input type="checkbox" id="inv-chk-paquete" class="form-check-input me-2">Paquete</label><label class="form-check-label"><input type="checkbox" id="inv-chk-caja" class="form-check-input me-2">Caja</label><div class="row g-3 mt-1"><div class="col-md-4 d-none" id="inv-grupo-paquete"><label class="form-label">Unidades por paquete</label><input name="unidades_por_paquete" type="number" min="1" class="form-control ui-input"></div><div class="col-md-4 d-none" id="inv-grupo-paquetes-caja"><label class="form-label">Paquetes por caja</label><input name="paquetes_por_caja" type="number" min="1" class="form-control ui-input"></div><div class="col-md-4 d-none" id="inv-grupo-caja-directa"><label class="form-label">Unidades por caja</label><input name="unidades_por_caja" type="number" min="1" class="form-control ui-input"></div></div></div></div>
                    <div class="col-md-4"><label class="form-check-label"><input type="checkbox" name="maneja_vencimiento" value="1" class="form-check-input me-2">Maneja fecha de vencimiento</label></div>
                    <div class="col-md-4"><label class="form-label d-flex justify-content-between align-items-center">Categoría <button type="button" class="btn-soft btn-soft-primary btn-soft-icon" data-inv-nuevo-param="categoria" title="Nueva categoría"><i class="fas fa-plus"></i></button></label><select id="inv-producto-categoria" name="categoria_id" class="form-select ui-input" required><option value="">Seleccionar...</option>@foreach($categorias as $categoria)<option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label d-flex justify-content-between align-items-center">Marca <button type="button" class="btn-soft btn-soft-primary btn-soft-icon" data-inv-nuevo-param="marca" title="Nueva marca"><i class="fas fa-plus"></i></button></label><select id="inv-producto-marca" name="marca_id" class="form-select ui-input"><option value="">Seleccionar...</option>@foreach($marcas as $marca)<option value="{{ $marca->id }}">{{ $marca->nombre }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Imagen principal</label><input name="imagen" type="file" accept="image/*" class="form-control ui-input"><img id="inv-producto-imagen-preview" class="ui-product-preview d-none mt-2" alt="Vista previa de imagen"></div>
                    <div class="col-md-4"><label class="form-label">Imágenes adicionales del catálogo</label><input name="imagenes_catalogo[]" type="file" accept="image/*" multiple class="form-control ui-input"><small class="text-muted">Máximo 2 imágenes.</small></div>
                    <div class="col-md-4"><label class="form-check-label d-block mb-2"><input type="checkbox" name="activo" value="1" class="form-check-input me-2" checked>Activo</label><label class="form-check-label"><input type="checkbox" name="visible_en_catalogo" value="1" class="form-check-input me-2" checked>Visible en catálogo</label></div>
                </div>
                <small class="text-muted d-block mt-3">El costo, los precios y el stock se registran en este ingreso de inventario.</small>
            </div>
            <div class="modal-footer"><button type="button" class="btn-soft btn-soft-info" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn-soft btn-soft-success">Guardar producto</button></div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
<link rel="stylesheet" href="{{ asset('css/calendar-theme.css') }}?v={{ filemtime(public_path('css/calendar-theme.css')) }}">
<link rel="stylesheet" href="{{ asset('css/lote.css') }}?v={{ filemtime(public_path('css/lote.css')) }}">
<link rel="stylesheet" href="{{ asset('css/proveedor.css') }}?v={{ filemtime(public_path('css/proveedor.css')) }}">
<link rel="stylesheet" href="{{ asset('css/crear_productos.css') }}?v={{ filemtime(public_path('css/crear_productos.css')) }}">
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    flatpickr('.date-ingreso', { locale: 'es', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd F Y', disableMobile: true });
    flatpickr('.date-vencimiento, .date-pago', { locale: 'es', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd F Y', minDate: 'today', disableMobile: true });

    const formatOption = option => {
        if (!option.id) return option.text;
        const detail = option.element.dataset.descripcion || option.element.dataset.doc || '';
        return $(`<div class="select-rich"><strong>${option.text}</strong>${detail ? `<small>${detail}</small>` : ''}</div>`);
    };
    $('#producto-select, #proveedor-select').select2({
        width: '100%', allowClear: true, placeholder: 'Seleccionar...',
        templateResult: formatOption,
        templateSelection: formatOption, escapeMarkup: markup => markup
    });

    $('#metodo_pago').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Seleccionar...',
        minimumResultsForSearch: Infinity
    });

    $('#producto-select, #proveedor-select').on('select2:open', function () {
        const buscador = document.querySelector('.select2-container--open .select2-search__field');
        if (buscador) {
            buscador.placeholder = 'Buscar...';
            buscador.focus();
        }
    });

    $(document).on('mousedown', '.select2-selection__clear', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        const select = $(this).closest('.select2-container').prev('select');
        select.val(null).trigger('change');
    });

    const product = document.getElementById('producto-select');
    const quantity = document.getElementById('stock_inicial');
    const cost = document.getElementById('precio_compra');

    const tipoProveedor = document.getElementById('inv-proveedor-tipo');
    const documentoProveedor = document.getElementById('inv-proveedor-documento');
    const estadoProveedor = document.getElementById('inv-proveedor-estado');
    const botonConsulta = document.getElementById('inv-consultar-proveedor');
    const botonGuardarProveedor = document.querySelector('#formNuevoProveedor [type="submit"]');
    let consultaProveedorTimer;
    let ultimaConsultaProveedor = '';
    function configurarDocumentoProveedor() {
        const tipo = tipoProveedor.value;
        const longitud = tipo === 'DNI' ? 8 : tipo === 'RUC' ? 11 : 20;
        documentoProveedor.maxLength = longitud;
        documentoProveedor.inputMode = tipo === 'OTRO' ? 'text' : 'numeric';
        documentoProveedor.placeholder = tipo === 'OTRO' ? 'Ingresa el documento' : `Ingresa ${longitud} dígitos`;
        botonConsulta.classList.toggle('d-none', tipo === 'OTRO');
        estadoProveedor.textContent = tipo === 'OTRO' ? 'Completa los datos manualmente.' : 'La consulta se realizará al completar el documento.';
        estadoProveedor.className = 'provider-query-status';
        botonGuardarProveedor.disabled = false;
        ultimaConsultaProveedor = '';
    }
    async function consultarProveedor() {
        const tipo = tipoProveedor.value;
        const numero = documentoProveedor.value.trim();
        const longitud = tipo === 'DNI' ? 8 : tipo === 'RUC' ? 11 : 0;
        if (!longitud || !new RegExp(`^\\d{${longitud}}$`).test(numero)) {
            estadoProveedor.textContent = `El ${tipo} debe tener ${longitud} dígitos.`;
            estadoProveedor.className = 'provider-query-status is-warning';
            return;
        }
        if (`${tipo}-${numero}` === ultimaConsultaProveedor) return;
        ultimaConsultaProveedor = `${tipo}-${numero}`;
        botonConsulta.disabled = true;
        estadoProveedor.textContent = 'Consultando información oficial…';
        estadoProveedor.className = 'provider-query-status is-loading';
        try {
            const registrado = await fetch(`{{ route('proveedores.verificar-documento') }}?numero=${encodeURIComponent(numero)}`, { headers: { Accept: 'application/json' } });
            if (!registrado.ok) throw new Error('No se pudo verificar el documento.');
            const duplicado = await registrado.json();
            if (duplicado.existe) {
                botonGuardarProveedor.disabled = true;
                estadoProveedor.textContent = `Este documento ya está registrado para ${duplicado.proveedor.nombre}.`;
                estadoProveedor.className = 'provider-query-status is-error';
                return;
            }
            const response = await fetch(`/consulta-documento/${tipo.toLowerCase()}/${numero}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Documento no encontrado.');
            document.getElementById('inv-proveedor-nombre').value = data.nombre || '';
            if (data.direccion) document.getElementById('inv-proveedor-direccion').value = data.direccion;
            estadoProveedor.textContent = tipo === 'RUC' ? `Proveedor encontrado${data.estado ? ` · Estado: ${data.estado}` : ''}` : 'Persona encontrada correctamente.';
            estadoProveedor.className = 'provider-query-status is-success';
        } catch (error) {
            ultimaConsultaProveedor = '';
            estadoProveedor.textContent = error.message || 'No se pudo consultar el documento.';
            estadoProveedor.className = 'provider-query-status is-error';
        } finally {
            botonConsulta.disabled = false;
        }
    }
    tipoProveedor.addEventListener('change', () => {
        documentoProveedor.value = '';
        document.getElementById('inv-proveedor-nombre').value = '';
        document.getElementById('inv-proveedor-direccion').value = '';
        configurarDocumentoProveedor();
    });
    documentoProveedor.addEventListener('input', () => {
        if (tipoProveedor.value !== 'OTRO') documentoProveedor.value = documentoProveedor.value.replace(/\D/g, '');
        botonGuardarProveedor.disabled = false;
        ultimaConsultaProveedor = '';
        clearTimeout(consultaProveedorTimer);
        const longitud = tipoProveedor.value === 'DNI' ? 8 : 11;
        if (tipoProveedor.value !== 'OTRO' && documentoProveedor.value.length === longitud) {
            consultaProveedorTimer = setTimeout(consultarProveedor, 350);
        }
    });
    botonConsulta.addEventListener('click', consultarProveedor);
    configurarDocumentoProveedor();

    const formProductoModal = document.getElementById('formProductoInventario');
    const chkPaquete = document.getElementById('inv-chk-paquete');
    const chkCaja = document.getElementById('inv-chk-caja');
    function actualizarPresentaciones() {
        const grupos = [
            ['inv-grupo-paquete', chkPaquete.checked],
            ['inv-grupo-paquetes-caja', chkPaquete.checked && chkCaja.checked],
            ['inv-grupo-caja-directa', !chkPaquete.checked && chkCaja.checked]
        ];
        grupos.forEach(([id, visible]) => {
            const grupo = document.getElementById(id);
            grupo.classList.toggle('d-none', !visible);
            const campo = grupo.querySelector('input');
            campo.required = visible;
            if (!visible) campo.value = '';
        });
    }
    [chkPaquete, chkCaja].forEach(input => input.addEventListener('change', actualizarPresentaciones));
    actualizarPresentaciones();
    document.getElementById('modalProductoInventario').addEventListener('hidden.bs.modal', () => {
        formProductoModal.reset();
        actualizarPresentaciones();
        document.getElementById('inv-producto-imagen-preview').classList.add('d-none');
        formProductoModal.querySelector('.inventory-modal-error').classList.add('d-none');
    });
    document.getElementById('modalProveedorInventario').addEventListener('hidden.bs.modal', () => {
        document.getElementById('formNuevoProveedor').reset();
        configurarDocumentoProveedor();
        document.getElementById('formNuevoProveedor').querySelector('.inventory-modal-error').classList.add('d-none');
    });

    formProductoModal.elements['imagen'].addEventListener('change', event => {
        const preview = document.getElementById('inv-producto-imagen-preview');
        const archivo = event.target.files[0];
        preview.classList.toggle('d-none', !archivo);
        if (archivo) preview.src = URL.createObjectURL(archivo);
    });
    document.querySelectorAll('[data-inv-nuevo-param]').forEach(button => button.addEventListener('click', async () => {
        const tipo = button.dataset.invNuevoParam;
        const { value: nombre } = await Swal.fire({
            title: `Nueva ${tipo}`,
            input: 'text',
            inputLabel: 'Nombre',
            inputAttributes: { maxlength: 255 },
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            inputValidator: value => value?.trim() ? null : 'El nombre es obligatorio.'
        });
        if (!nombre?.trim()) return;
        const formData = new FormData();
        formData.set('_token', '{{ csrf_token() }}');
        formData.set('nombre', nombre.trim().toLocaleUpperCase('es-PE'));
        try {
            const response = await fetch(tipo === 'categoria' ? `{{ route('categoria.ajax.store') }}` : `{{ route('marca.ajax.store') }}`, {
                method: 'POST', body: formData, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok || result.error) throw new Error(result.message || 'No se pudo guardar.');
            const select = document.getElementById(tipo === 'categoria' ? 'inv-producto-categoria' : 'inv-producto-marca');
            select.add(new Option(result.data.nombre, result.data.id, true, true));
        } catch (error) {
            formProductoModal.querySelector('.inventory-modal-error').textContent = error.message;
            formProductoModal.querySelector('.inventory-modal-error').classList.remove('d-none');
        }
    }));

    async function guardarDesdeModal(form, onCreated) {
        const errorBox = form.querySelector('.inventory-modal-error');
        const submit = form.querySelector('[type="submit"]');
        errorBox.classList.add('d-none');
        submit.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok) {
                const errors = Object.values(result.errors || {}).flat();
                throw new Error(errors.join(' ') || result.message || 'No se pudo guardar el registro.');
            }
            onCreated(result);
            bootstrap.Modal.getInstance(form.closest('.modal'))?.hide();
            form.reset();
        } catch (error) {
            errorBox.textContent = error.message || 'No se pudo guardar. Inténtalo nuevamente.';
            errorBox.classList.remove('d-none');
        } finally {
            submit.disabled = false;
        }
    }

    document.getElementById('formNuevoProveedor').addEventListener('submit', event => {
        event.preventDefault();
        guardarDesdeModal(event.currentTarget, ({ proveedor }) => {
            const option = new Option(proveedor.nombre, proveedor.id, true, true);
            option.dataset.doc = `${proveedor.tipo_documento} ${proveedor.numero_documento}`;
            $('#proveedor-select').append(option).trigger('change');
        });
    });

    document.getElementById('formProductoInventario').addEventListener('submit', event => {
        event.preventDefault();
        const form = event.currentTarget;
        const paquete = Number(form.elements['unidades_por_paquete'].value || 0);
        const paquetesCaja = Number(form.elements['paquetes_por_caja'].value || 0);
        const cajaDirecta = Number(form.elements['unidades_por_caja'].value || 0);
        if ((paquetesCaja && !paquete) || (paquete && cajaDirecta)) {
            const errorBox = form.querySelector('.inventory-modal-error');
            errorBox.textContent = paquetesCaja && !paquete
                ? 'Indica primero las unidades por paquete.'
                : 'Elige caja directa o caja compuesta por paquetes, no ambas.';
            errorBox.classList.remove('d-none');
            return;
        }
        guardarDesdeModal(form, ({ producto }) => {
            const option = new Option(producto.nombre, producto.id, true, true);
            option.dataset.vencimiento = producto.maneja_vencimiento ? '1' : '0';
            option.dataset.precioUnidad = producto.precio_venta || '';
            option.dataset.precioPaquete = producto.precio_paquete || '';
            option.dataset.precioCaja = producto.precio_caja || '';
            option.dataset.stockMinimo = producto.stock_minimo || '10';
            option.dataset.descripcion = producto.descripcion || '';
            $('#producto-select').append(option).trigger('change');
        });
    });

    function syncProduct() {
        const option = product.options[product.selectedIndex];
        const expiryInput = document.querySelector('[name="fecha_vencimiento"]');
        if (!option?.value) {
            document.getElementById('grupo-vencimiento').style.display = 'none';
            expiryInput.required = false;
            document.getElementById('input_precio_unidad').value = '';
            document.getElementById('chk_precio_paquete').checked = false;
            document.getElementById('grupoPrecioPaquete').classList.add('d-none');
            document.getElementById('input_precio_paquete').value = '';
            document.getElementById('chk_precio_caja').checked = false;
            document.getElementById('grupoPrecioCaja').classList.add('d-none');
            document.getElementById('input_precio_caja').value = '';
            document.getElementById('stock_minimo').value = '10';
            return;
        }
        const hasExpiry = Number(option.dataset.vencimiento) === 1;
        document.getElementById('grupo-vencimiento').style.display = hasExpiry ? '' : 'none';
        expiryInput.required = hasExpiry;
        if (!hasExpiry) expiryInput.value = '';

        document.getElementById('input_precio_unidad').value = option.dataset.precioUnidad || '';
        document.getElementById('stock_minimo').value = option.dataset.stockMinimo || '10';

        const packagePrice = option.dataset.precioPaquete;
        const boxPrice = option.dataset.precioCaja;
        const hasPackagePrice = Number(packagePrice) > 0;
        const hasBoxPrice = Number(boxPrice) > 0;

        document.getElementById('chk_precio_paquete').checked = hasPackagePrice;
        document.getElementById('grupoPrecioPaquete').classList.toggle('d-none', !hasPackagePrice);
        document.getElementById('input_precio_paquete').value = hasPackagePrice ? packagePrice : '';

        document.getElementById('chk_precio_caja').checked = hasBoxPrice;
        document.getElementById('grupoPrecioCaja').classList.toggle('d-none', !hasBoxPrice);
        document.getElementById('input_precio_caja').value = hasBoxPrice ? boxPrice : '';
    }

    function syncPayment() {
        const credit = document.querySelector('[name="condicion_pago"]:checked').value === 'credito';
        document.getElementById('grupoMetodoPago').classList.toggle('d-none', credit);
        document.getElementById('grupoVencimientoPago').classList.toggle('d-none', !credit);
        document.getElementById('metodo_pago').required = !credit;
        document.querySelector('[name="fecha_vencimiento_pago"]').required = credit;
    }

    function syncTotal() {
        const units = Number(quantity.value || 0);
        const unitCost = Number(cost.value || 0);
        document.getElementById('resumenCantidad').textContent = `${units} unds.`;
        document.getElementById('resumenCosto').textContent = `S/ ${unitCost.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 6 })}`;
        document.getElementById('resumenTotal').textContent = `S/ ${(units * unitCost).toFixed(2)}`;
    }

    $('#producto-select').on('change', syncProduct);
    document.querySelectorAll('[name="condicion_pago"]').forEach(input => input.addEventListener('change', syncPayment));
    [quantity, cost].forEach(input => input.addEventListener('input', syncTotal));

    [['chk_precio_paquete', 'grupoPrecioPaquete', 'input_precio_paquete'], ['chk_precio_caja', 'grupoPrecioCaja', 'input_precio_caja']]
        .forEach(([checkboxId, groupId, inputId]) => {
            document.getElementById(checkboxId).addEventListener('change', event => {
                document.getElementById(groupId).classList.toggle('d-none', !event.target.checked);
                if (!event.target.checked) document.getElementById(inputId).value = '';
            });
        });

    syncProduct();
    syncPayment();
    syncTotal();
});
</script>
@endpush
