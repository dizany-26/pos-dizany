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
                    <button type="submit" formnovalidate formaction="{{ route('inventario.compra-en-curso.limpiar') }}" formmethod="POST" class="btn-soft btn-soft-info"><i class="fas fa-file-circle-plus"></i> Nueva compra</button>
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
                        <a href="{{ route('proveedores.index') }}" class="btn-soft btn-soft-success btn-soft-icon" title="Nuevo proveedor">
                            <i class="fas fa-plus"></i>
                        </a>
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

        <section class="inventory-entry-card">
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
                        <a href="{{ route('productos.create', ['from' => 'lotes']) }}" class="btn-soft btn-soft-primary btn-soft-icon" title="Nuevo producto">
                            <i class="fas fa-plus"></i>
                        </a>
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
                        value="{{ old('fecha_vencimiento') }}">
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
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
<link rel="stylesheet" href="{{ asset('css/calendar-theme.css') }}?v={{ filemtime(public_path('css/calendar-theme.css')) }}">
<link rel="stylesheet" href="{{ asset('css/lote.css') }}?v={{ filemtime(public_path('css/lote.css')) }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
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

    $(document).on('mousedown', '.select2-selection__clear', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        const select = $(this).closest('.select2-container').prev('select');
        select.val(null).trigger('change');
    });

    const product = document.getElementById('producto-select');
    const quantity = document.getElementById('stock_inicial');
    const cost = document.getElementById('precio_compra');

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
