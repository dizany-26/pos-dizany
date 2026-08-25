@extends('layouts.app')

@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

@section('header-title')
Panel de Editar
@endsection

@section('header-buttons')
<a href="{{ route('inventario.lotes') }}" class="btn-gasto">
    <i class="fas fa-layer-group"></i>
    <span class="btn-text">Ver lotes</span>
</a>
@endsection

@section('content')
<div class="container-fluid px-3">

    <div class="card mx-auto my-4" style="max-width: 1000px;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-truck-loading me-2"></i>
                Modificar Lote
            </h5>
        </div>

        <div class="p-3 p-md-4">
            <form action="{{ route('lotes.update', $lote->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    {{-- COLUMNA IZQUIERDA --}}
                    <div class="col-md-6">

                        <div class="alert alert-info small">
                            <div class="fw-semibold mb-1">Precio público vigente</div>
                            Unidad: S/ {{ number_format($lote->producto->precio_venta ?? 0, 2) }}
                            @if($lote->producto->precio_paquete)
                                · Paquete: S/ {{ number_format($lote->producto->precio_paquete, 2) }}
                            @endif
                            @if($lote->producto->precio_caja)
                                · Caja: S/ {{ number_format($lote->producto->precio_caja, 2) }}
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="inv-label">Cd. Comprobante</label>
                            <input type="text"
                                name="codigo_comprobante"
                                class="form-control inv-input"
                                value="{{ old('codigo_comprobante', $lote->codigo_comprobante) }}"
                                placeholder="E-000">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <input type="text" class="form-control" disabled
                                value="{{ $lote->producto->nombre }}">
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Stock</label>
                            <input type="text" class="form-control" disabled
                                value="{{ $lote->stock_actual }} / {{ $lote->stock_inicial }}">
                        </div>

                        <small class="text-muted d-flex align-items-center mb-3">
                            <i class="fas fa-lock me-2"></i>
                            El stock no puede modificarse porque este lote puede tener movimientos de venta.
                        </small>

                        <div class="mb-3">
                            <label class="form-label">Fecha de vencimiento</label>
                            <input type="date"
                                name="fecha_vencimiento"
                                class="form-control fecha-vencimiento-edit"
                                value="{{ $lote->fecha_vencimiento }}">
                        </div>

                        <div class="form-check mb-3">
                            <input type="hidden" name="actualizar_precio_producto" value="0">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="actualizar_precio_producto"
                                id="actualizar_precio_producto"
                                value="1"
                            >
                            <label class="form-check-label fw-semibold" for="actualizar_precio_producto">
                                Aplicar estos precios a todos los lotes del producto
                            </label>
                            <div class="form-text">
                                Márcalo solo si deseas cambiar el precio que verá el cliente en POS, catálogo y comprobante.
                            </div>
                        </div>

                    </div>

                    {{-- COLUMNA DERECHA --}}
                    <div class="col-md-6">

                        <div class="mb-3">
                            <label class="form-label">Precio unidad (S/)</label>
                            <input type="text"
                                name="precio_unidad"
                                class="form-control precio-decimal"
                                value="{{ $lote->precio_unidad }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Precio paquete (S/)</label>
                            <input type="text"
                                name="precio_paquete"
                                class="form-control precio-decimal"
                                value="{{ $lote->precio_paquete }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Precio caja (S/)</label>
                            <input type="text"
                                name="precio_caja"
                                class="form-control precio-decimal"
                                value="{{ $lote->precio_caja }}">
                        </div>

                    </div>
                </div>
                {{-- AJUSTE DE INVENTARIO --}}
                <div class="card border-warning mt-4">
                    <div class="card-header bg-warning bg-opacity-10 fw-semibold">
                        <i class="fas fa-tools me-2"></i> Ajuste de inventario
                    </div>

                    <div class="card-body">

                        <div class="alert alert-warning small mb-4">
                            <i class="fas fa-info-circle me-1"></i>
                            Usa este ajuste solo para correcciones de stock (conteo físico, merma, error).
                            Este cambio quedará registrado.
                        </div>

                        <div class="row align-items-end g-3">

                            {{-- TIPO DE AJUSTE --}}
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tipo de ajuste</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="tipo_ajuste" id="ajuste_restar" value="restar">
                                    <label class="btn btn-outline-danger" for="ajuste_restar">
                                        − Restar
                                    </label>

                                    <input type="radio" class="btn-check" name="tipo_ajuste" id="ajuste_sumar" value="sumar">
                                    <label class="btn btn-outline-success" for="ajuste_sumar">
                                        + Sumar
                                    </label>
                                </div>
                            </div>

                            {{-- CANTIDAD --}}
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Cantidad</label>

                                <div class="d-flex align-items-center qty-control">
                                    <button type="button" id="btn_minus" class="btn btn-light btn-qty" data-action="minus" disabled>−</button>

                                    <input type="number"
                                        id="ajuste_cantidad"
                                        class="form-control text-center mx-2"
                                        value="0">

                                    <button type="button" id="btn_plus" class="btn btn-light btn-qty" data-action="plus" disabled>+</button>
                                </div>

                                <small id="stock_resultante" class="text-muted d-block mt-1">
                                    Stock resultante: — unidades
                                </small>
                            </div>

                            {{-- MOTIVO --}}
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Motivo</label>
                                <select id="ajuste_motivo" class="form-select">
                                    <option value="">Seleccionar motivo</option>
                                    <option value="conteo_fisico">Conteo físico</option>
                                    <option value="merma">Merma</option>
                                    <option value="error_registro">Error de registro</option>
                                    <option value="ajuste_admin">Ajuste administrativo</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>

                        </div>

                        {{-- BOTÓN APLICAR --}}
                        <div class="text-center mt-4">
                            <button type="button"
                                    id="btn_aplicar_ajuste"
                                    class="btn btn-warning px-4"
                                    disabled>
                                <i class="fas fa-save me-1"></i> Aplicar ajuste
                            </button>
                        </div>

                    </div>
                </div>


                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('inventario.lotes') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                    <button class="btn btn-primary">
                        Guardar cambios
                    </button>
                </div>
            </form>
            
        </div>
    </div>

</div>
@endsection

{{-- ===================== STYLES ===================== --}}
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
<link rel="stylesheet" href="{{ asset('css/calendar-theme.css') }}?v={{ filemtime(public_path('css/calendar-theme.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ajuste_lote.css') }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr('.fecha-vencimiento-edit', {
            locale: 'es',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd F Y',
            allowInput: true,
            disableMobile: true
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {

        const stockActual = {{ (int) $lote->stock_actual }};
        const inputCantidad = document.getElementById("ajuste_cantidad");
        const stockResultante = document.getElementById("stock_resultante");
        const btnAplicar = document.getElementById("btn_aplicar_ajuste");
        const motivo = document.getElementById("ajuste_motivo");
        const btnMinus = document.getElementById("btn_minus");
        const btnPlus  = document.getElementById("btn_plus");

        function tipoAjuste() {
            return document.querySelector('input[name="tipo_ajuste"]:checked')?.value || null;
        }

        function actualizarBotonesCantidad() {
            const tipo = tipoAjuste();
            const cantidad = parseInt(inputCantidad.value) || 0;

            if (!tipo) {
                btnMinus.disabled = true;
                btnPlus.disabled = true;
                return;
            }

            // Si cantidad es 0 solo habilita el principal
            if (cantidad === 0) {
                if (tipo === "restar") {
                    btnMinus.disabled = false;
                    btnPlus.disabled = true;
                }

                if (tipo === "sumar") {
                    btnMinus.disabled = true;
                    btnPlus.disabled = false;
                }
            } else {
                // Si ya empezó a ajustar, ambos activos
                btnMinus.disabled = false;
                btnPlus.disabled = false;
            }
        }

        function recalcular() {
            const tipo = tipoAjuste();
            const cantidad = parseInt(inputCantidad.value) || 0;
            const cantidadAbs = Math.abs(cantidad);

            // Mostrar stock resultante
            if (!tipo || cantidadAbs === 0) {
                stockResultante.textContent = "Stock resultante: — unidades";
            } else {
                let nuevoStock = stockActual;

                if (tipo === "sumar") nuevoStock += cantidadAbs;
                if (tipo === "restar") nuevoStock -= cantidadAbs;

                stockResultante.textContent = `Stock resultante: ${nuevoStock} unidades`;

                if (nuevoStock < 0) {
                    stockResultante.textContent =
                        `Stock resultante: ${nuevoStock} unidades (inválido)`;
                }
            }

            // Validación botón aplicar
            const puedeAplicar = (
                tipo &&
                cantidadAbs > 0 &&
                motivo.value &&
                (tipo !== "restar" || (stockActual - cantidadAbs) >= 0)
            );

            btnAplicar.disabled = !puedeAplicar;
        }

        function setCantidadMagnitud(magnitud) {
            const tipo = tipoAjuste();
            if (!tipo) return;

            magnitud = Math.max(0, magnitud);

            // No permitir restar más del stock
            if (tipo === "restar" && magnitud > stockActual) {
                magnitud = stockActual;
            }

            if (tipo === "sumar") {
                inputCantidad.value = magnitud;
            }

            if (tipo === "restar") {
                inputCantidad.value = -magnitud;
            }

            actualizarBotonesCantidad();
            recalcular();
        }

        btnPlus.addEventListener("click", () => {
            const tipo = tipoAjuste();
            if (!tipo) return;

            let val = parseInt(inputCantidad.value) || 0;
            let magnitud = Math.abs(val);

            if (tipo === "sumar") {
                setCantidadMagnitud(magnitud + 1);
            }

            if (tipo === "restar") {
                // En restar, + reduce la magnitud (acercarse a 0)
                setCantidadMagnitud(magnitud - 1);
            }
        });

        btnMinus.addEventListener("click", () => {
            const tipo = tipoAjuste();
            if (!tipo) return;

            let val = parseInt(inputCantidad.value) || 0;
            let magnitud = Math.abs(val);

            if (tipo === "restar") {
                // 🔥 Aquí está la clave
                setCantidadMagnitud(magnitud + 1);
            }

            if (tipo === "sumar") {
                setCantidadMagnitud(magnitud - 1);
            }
        });


        // Cambiar tipo
        document.querySelectorAll('input[name="tipo_ajuste"]').forEach(r =>
            r.addEventListener("change", () => {

                // 🔥 Resetear cantidad al cambiar tipo
                inputCantidad.value = 0;

                actualizarBotonesCantidad();
                recalcular();
            })
        );

        inputCantidad.addEventListener("input", recalcular);
        $('#ajuste_motivo').on('change', recalcular);

        // Inicializar
        actualizarBotonesCantidad();
        recalcular();
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {

        $('#ajuste_motivo').select2({
            placeholder: 'Seleccionar motivo',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: Infinity, // 👈 oculta buscador
            dropdownParent: $('#ajuste_motivo').closest('.card-body')
        });

    });
</script>

<script>
    document.getElementById('btn_aplicar_ajuste').addEventListener('click', () => {

        const tipo = document.querySelector('input[name="tipo_ajuste"]:checked')?.value;
        const cantidadRaw = parseInt(document.getElementById('ajuste_cantidad').value) || 0;
        const cantidadAbs = Math.abs(cantidadRaw);
        const motivo = document.getElementById('ajuste_motivo').value;

        // 🔥 Validación correcta
        if (!tipo || cantidadAbs === 0 || !motivo) {
            Swal.fire({
                icon: 'warning',
                title: 'Datos incompletos',
                text: 'Completa todos los campos del ajuste',
                width: 320,
                confirmButtonText: 'Entendido'
            });
            return;
        }

        Swal.fire({
            title: 'Confirmar ajuste',
            text: `¿Deseas ${tipo === 'sumar' ? 'sumar' : 'restar'} ${cantidadAbs} unidades?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Aplicar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: tipo === 'sumar' ? '#198754' : '#dc3545',
            cancelButtonColor: '#6c757d',
            width: 360,
            reverseButtons: true
        }).then((result) => {

            if (!result.isConfirmed) return;

            fetch("{{ route('lotes.ajustar', $lote->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    tipo: tipo,
                    cantidad: cantidadAbs, // 🔥 enviamos positivo
                    motivo: motivo
                })
            })
            .then(res => res.json())
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Ajuste aplicado',
                    text: data.message,
                    timer: 1500,
                    showConfirmButton: false,
                    width: 320
                }).then(() => {
                    location.reload();
                });
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo aplicar el ajuste',
                    width: 320
                });
            });

        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        function formatearPrecio(input) {
            let valor = input.value.replace(',', '.').trim();

            if (valor === '' || isNaN(valor)) {
                input.value = '';
                return;
            }

            let numero = parseFloat(valor);

            // Máximo 3 decimales
            let formateado = numero.toFixed(3);

            // Quitar .000, .00 innecesarios
            formateado = formateado.replace(/\.?0+$/, '');

            // Asegurar mínimo 2 decimales
            if (!formateado.includes('.')) {
                formateado += '.00';
            } else {
                let decimales = formateado.split('.')[1].length;
                if (decimales === 1) {
                    formateado += '0';
                }
            }

            input.value = formateado;
        }

        document.querySelectorAll('.precio-decimal').forEach(input => {

            // 🔥 FORMATEAR AL CARGAR
            formatearPrecio(input);

            // 🔥 FORMATEAR AL SALIR
            input.addEventListener('blur', function () {
                formatearPrecio(this);
            });

        });

    });
</script>


@endpush
