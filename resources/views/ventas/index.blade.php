@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
@endpush
{{-- Activa el sistema de header-actions --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

@section('header-title')
Nueva venta
@endsection

@section('header-buttons')

<div class="pos-espera-wrapper">
    <button id="btn-pos-espera" class="btn-pos-espera btn-soft btn-soft-info">
        <i class="fas fa-receipt"></i>
        <span class="btn-text">Ventas en espera</span>
        <span id="pos-espera-count" class="badge">0</span>
    </button>

    <div id="pos-espera-panel" class="pos-espera-panel d-none"></div>
</div>

<div class="ordenar-wrapper">
    <button id="btn-ordenar" class="btn-ordenar btn-soft btn-soft-info btn-soft-icon"
            data-tooltip="Ordenar productos">
        <i class="fas fa-sort-amount-down"></i>
    </button>
</div>

<a href="{{ route('gastos.create') }}" class="btn-gasto">
    <i class="fas fa-receipt"></i>
    <span class="btn-text">Nuevo gasto</span>
</a>

@endsection


@section('content')
<link href="{{ asset('css/ventas.css') }}" rel="stylesheet" />

<div class="container-fluid ventas-treinta">

    <!-- 🔥 CUERPO PRINCIPAL 2 COLUMNAS -->
    <div class="treinta-body">

        <!-- ====================== 🟩 COLUMNA IZQUIERDA ====================== -->
        <div class="treinta-col izquierda">

            <!-- BUSCADOR -->
            <div class="contenedor-buscador">
                <div class="d-flex align-items-center">
                    <i class="fas fa-search me-2 text-primary"></i>
                    <input type="text" id="buscar_producto" class="form-control"
                        placeholder="Buscar productos por nombre o código...">
                    <button type="button"
                        id="btnEscanearVenta"
                        class="btn-soft btn-soft-info ventas-scan-btn ms-2"
                        title="Escanear código de barras"
                        aria-label="Escanear código de barras">
                        <i class="fas fa-barcode"></i>
                    </button>
                </div>

                <!-- CATEGORÍAS -->
                <div class="ventas-categorias mt-3">
                    <button class="btn-filtro-categoria active" data-cat="0">Todos</button>

                    @foreach($categorias as $cat)
                        <button class="btn-filtro-categoria" data-cat="{{ $cat->id }}">
                            {{ strtoupper($cat->nombre) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- GRID DE PRODUCTOS -->
            <div id="resultados-busqueda" class="productos-scroll row g-3">
                <!-- Productos renderizados por JS -->
            </div>

        </div> <!-- cierre columna izquierda -->
                <!-- ====================== 🟥 COLUMNA DERECHA ====================== -->
        <div class="treinta-col derecha">
        <div class="venta-steps">

            <!-- █████████████████████████████ -->
            <!--        FASE 1: CARRITO       -->
            <!-- █████████████████████████████ -->
            <!-- ==================== FASE 1 ==================== -->
            <div id="step-1" class="step-panel is-active">

                <!-- HEADER FIJO -->
                <div class="card shadow-sm mb-0">
                    <div class="card-header bg-dark text-white d-flex justify-content-between">
                        <span>Productos</span>
                        <a href="#" id="vaciar-canasta" class="text-white">Vaciar canasta</a>
                    </div>
                </div>

                <!-- LISTA (SCROLL) -->
                <div id="carrito-lista" class="carrito-scroll">
                    <!-- Aquí se agregan dinámicamente los productos -->
                </div>

                <!-- FOOTER FIJO -->
                <div class="card p-2 mt-0" style="border-radius: 0 0 12px 12px;">
                    <button id="btn-ir-step2" class="btn btn-primary w-100">
                        <span id="contador-items">0</span> Continuar
                        <span class="ms-2">S/ <span id="total-general-footer">0.00</span></span>
                    </button>
                </div>

            </div>

            <!-- █████████████████████████████ -->
            <!-- FASE 2: CLIENTE + RESUMEN    -->
            <!-- █████████████████████████████ -->
            <!-- ==================== FASE 2 ==================== -->
            <div id="step-2" class="step-panel step2-scroll">
                <!-- CLIENTE Y COMPROBANTE -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-primary text-white">
                        Cliente y Comprobante
                    </div>

                    <div class="card-body small">
                        <div class="row g-3">

                            <!-- ========= COLUMNA IZQUIERDA: CLIENTE ========= -->
                            <div class="col-md-6">
                                <h6 class="fw-bold text-secondary mb-2">Cliente</h6>

                                <!-- DOCUMENTO -->
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" id="documento" class="form-control" placeholder="DNI / RUC">
                                    <button id="btn-cliente-accion" class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-plus-circle" id="icono-plus"></i>
                                        <i class="fas fa-save d-none" id="icono-save"></i>
                                    </button>
                                </div>

                                <p id="estado_ruc" class="text-success small mb-1"></p>

                                <!-- RAZÓN SOCIAL -->
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" id="razon_social" class="form-control" placeholder="Razón Social" readonly>
                                </div>

                                <!-- DIRECCIÓN -->
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" id="direccion" class="form-control" placeholder="Dirección" readonly>
                                </div>

                                <!-- FECHA (debajo de dirección) -->
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="date"
                                        id="fecha_emision"
                                        class="form-control"
                                        value="{{ date('Y-m-d') }}"
                                        readonly>
                                </div>
                            </div>

                            <!-- ========= COLUMNA DERECHA: COMPROBANTE ========= -->
                            <div class="col-md-6">
                                <h6 class="fw-bold text-secondary mb-2">Comprobante</h6>

                                <!-- TIPO COMPROBANTE -->
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="fas fa-file-invoice"></i></span>
                                    <select id="tipo_comprobante" class="form-select">
                                        <option value="boleta">Boleta</option>
                                        <option value="factura">Factura</option>
                                        <option value="nota_venta">Nota de Venta</option>
                                    </select>
                                </div>

                                <!-- SERIE - CORRELATIVO -->
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="fas fa-receipt"></i></span>
                                    <input type="text" id="serie_correlativo" class="form-control" readonly>
                                </div>

                                <!-- ESTADO DE PAGO -->
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                    <select id="estado_pago" class="form-select">
                                        <option value="pagado">Pagado</option>
                                        <option value="pendiente">Pendiente</option>
                                        <option value="credito">Credito</option>
                                    </select>
                                </div>

                                <!-- HORA (debajo de estado pago) -->
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                    <input type="time"
                                        id="hora_actual"
                                        class="form-control"
                                        value="{{ date('H:i') }}"
                                        readonly>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ===== RESUMEN + MÉTODOS DE PAGO ===== -->
                <div class="card shadow-sm mb-3">
                    <div class="card-body">

                        <!-- IGV desde configuración global -->
                        <input type="hidden" id="igv-config" value="{{ $config->igv }}">

                        <div class="resumen-box mb-3">
                            <div class="resumen-row">
                                <div class="resumen-label">Op. Gravadas:</div>
                                <div class="resumen-value" id="resumen-op-gravadas">S/ 0.00</div>
                            </div>

                            <div class="resumen-row">
                                <div class="resumen-label">
                                    IGV (<span id="resumen-igv-porcentaje">0%</span>):
                                </div>
                                <div class="resumen-value" id="resumen-igv-monto">S/ 0.00</div>
                            </div>

                            <div class="resumen-row resumen-total">
                                <div class="resumen-label">TOTAL:</div>
                                <div class="resumen-value" id="resumen-total">S/ 0.00</div>
                            </div>
                        </div>

                        <!-- Inputs ocultos para backend -->
                        <input type="hidden" name="op_gravadas" value="0">
                        <input type="hidden" name="total" value="0">
                        <input type="hidden" name="monto_pagado" value="0">

                        <!-- MÉTODOS DE PAGO BONITOS -->
                        <label class="fw-bold mb-2">Método de pago:</label>
                        <div class="d-flex justify-content-between gap-1 metodo-pago-opciones">

                            @foreach([
                                ['efectivo','efectivo.svg','Efectivo'],
                                ['tarjeta','tarjeta.svg','Tarjeta'],
                                ['transferencia','transferencia.svg','Transf.'],
                                ['plin','plin.svg','Plin'],
                                ['yape','yape.svg','Yape'],
                                ['otro','otro.svg','Otro'],
                            ] as $mp)
                                <div class="metodo-pago-item" data-value="{{ $mp[0] }}">
                                    <img src="/images/{{ $mp[1] }}" class="icon-img">
                                    <span class="label">{{ $mp[2] }}</span>
                                </div>
                            @endforeach

                        </div>

                        <input type="hidden" id="metodo_pago" name="metodo_pago" value="">
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <button id="btn-volver-step1" class="btn btn-soft btn-soft-info px-4 venta-step-btn venta-step-btn-back">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </button>
                        <button id="btn-ir-step3" class="btn btn-soft btn-soft-primary px-5 venta-step-btn venta-step-btn-primary">
                            Continuar venta <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        <button id="btn-confirmar-venta-directo"
                            class="btn btn-soft btn-soft-primary px-5 venta-step-btn venta-step-btn-primary"
                            style="display:none">
                            <i class="fas fa-check me-2"></i> Confirmar venta
                        </button>
                    </div>
                </div>

            </div>

            <!-- █████████████████████████████ -->
            <!--     FASE 3: PANEL DE VUELTO    -->
            <!-- █████████████████████████████ -->
            <!-- ==================== FASE 3 ==================== -->
            <div id="step-3" class="step-panel">

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        Calcula el cambio de tu venta
                    </div>

                    <div class="card-body">

                        <label class="form-label">Valor de la venta</label>
                        <input type="text" id="vuelto-total-venta" class="form-control mb-3" readonly>

                        <label class="form-label">Valor a pagar</label>
                        <input type="number" id="vuelto-paga" class="form-control mb-3">

                        <label class="form-label">Vuelto</label>
                        <input type="text" id="vuelto-mostrar" class="form-control mb-3" readonly>

                        <label class="form-label">Formato de impresión</label>
                        <select id="formato_pdf" class="form-select">
                            <option value="a4">A4</option>
                            <option value="ticket">Ticket</option>
                        </select>

                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <button id="btn-volver-step2" class="btn btn-soft btn-soft-info px-5 venta-step-btn venta-step-btn-back">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                        <button id="btn-confirmar-venta" class="btn btn-soft btn-soft-primary px-5 venta-step-btn venta-step-btn-primary">
                            <i class="fas fa-check"></i> Confirmar venta
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div><!-- /.treinta-col.derecha -->

    </div> <!-- /.treinta-body -->
</div> <!-- /.ventas-treinta -->

<!-- MODAL ORDENAR PRODUCTOS -->
<div class="modal fade" id="modalOrdenar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">

            <div class="modal-header">
                <h5 class="modal-title fw-bold">Ordenar inventario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <p class="text-muted small mb-3">Solo puedes aplicar un orden a la vez</p>

                <!-- POR STOCK -->
                <label class="fw-bold small text-secondary">Por stock</label>
                <div class="d-flex gap-2 mb-3">
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="stock_asc">Menos stock</button>
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="stock_desc">Más stock</button>
                </div>

                <!-- POR VENTAS -->
                <label class="fw-bold small text-secondary">Por ventas (últimos 30 días)</label>
                <div class="d-flex gap-2 mb-3">
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="menos_vendidos">Menos vendidos</button>
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="mas_vendidos">Más vendidos</button>
                </div>

                <!-- POR NOMBRE -->
                <label class="fw-bold small text-secondary">Por nombre</label>
                <div class="d-flex gap-2 mb-3">
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="az">Nombre A-Z</button>
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="za">Nombre Z-A</button>
                </div>

                <!-- POR FECHA -->
                <label class="fw-bold small text-secondary">Por fecha de creación</label>
                <div class="d-flex gap-2 mb-3">
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="fecha_asc">Más antiguo</button>
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="fecha_desc">Más reciente</button>
                </div>

                <!-- POR PRECIO -->
                <label class="fw-bold small text-secondary">Por precio</label>
                <div class="d-flex gap-2 mb-3">
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="precio_asc">Más bajo</button>
                    <button class="orden-btn btn-soft btn-soft-info w-100 justify-content-center" data-type="precio_desc">Más alto</button>
                </div>

            </div>

            <div class="modal-footer d-flex justify-content-between">
                <button class="btn-soft btn-soft-info" id="btn-limpiar-orden">Limpiar</button>
                <button class="btn-soft btn-soft-primary" id="btn-aplicar-orden">Aplicar</button>
            </div>

        </div>
    </div>
</div>

<!-- Modal para registrar un cliente -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientModalLabel">Registrar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="clientForm">
                    <!-- Campo Nombre -->
                    <div class="mb-3">
                        <label for="client_name" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="client_name" name="client_name" required>
                    </div>
                    
                    <!-- Campo Dirección -->
                    <div class="mb-3">
                        <label for="client_address" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="client_address" name="client_address">
                    </div>

                    <!-- Campo Teléfono -->
                    <div class="mb-3">
                        <label for="client_phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" id="client_phone" name="client_phone">
                    </div>

                    <!-- Campo DNI -->
                    <div class="mb-3">
                        <label for="client_dni" class="form-label">DNI</label>
                        <input type="text" class="form-control" id="client_dni" name="client_dni">
                    </div>

                    <!-- Campo RUC -->
                    <div class="mb-3">
                        <label for="client_ruc" class="form-label">RUC</label>
                        <input type="text" class="form-control" id="client_ruc" name="client_ruc">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Registrar Cliente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Modal Escanear Código de Barras en Ventas -->
<div class="modal fade" id="modalEscanearVenta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Escanear para venta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="barcode-scanner-shell">
          <div class="barcode-reader-frame">
            <div id="venta-barcode-reader" class="barcode-reader"></div>
          </div>

          <div class="barcode-reader-toolbar">
            <button type="button"
                class="btn-soft btn-soft-info barcode-tool-btn d-none"
                id="btnVentaBarcodeTorch"
                title="Encender o apagar linterna"
                aria-label="Encender o apagar linterna">
                <i class="fas fa-lightbulb"></i>
                <span>Linterna</span>
            </button>

            <button type="button"
                class="btn-soft btn-soft-info barcode-tool-btn d-none"
                id="btnVentaBarcodeZoom"
                title="Acercar vista"
                aria-label="Acercar vista">
                <i class="fas fa-search-plus"></i>
                <span>Zoom</span>
            </button>
          </div>

          <p id="ventaBarcodeScannerStatus" class="barcode-scanner-status barcode-scanner-status-info">
            Escanea un código para enviarlo directo al carrito.
          </p>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-soft btn-soft-info" id="btnCerrarEscanerVenta">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!-- Modal de Venta Exitosa -->
<div class="modal fade" id="modalVentaExitosa" tabindex="-1" aria-labelledby="modalVentaExitosaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content text-center p-4">
      <div class="modal-body">
        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
        <h4 class="mb-3">¡Venta registrada con éxito!</h4>
        <p class="text-muted">Puedes imprimir o descargar el comprobante.</p>

        <div class="d-flex justify-content-center gap-3 my-4">
            <a id="btnImprimir" target="_blank" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </a>
            <a id="btn-descargar" href="#" class="btn btn-success">Descargar</a>

        </div>

        <button id="btnNuevaVenta" class="btn btn-success mt-3">
          <i class="fas fa-plus-circle"></i> Continuar vendiendo
        </button>
      </div>
    </div>
  </div>
</div>



<script src="{{ asset('js/ventas_dniruc.js') }}"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
@php
$productos = \App\Models\Producto::withSum('detalleVentas as total_vendido', 'cantidad')
    ->where('activo', 1)
    ->get();
@endphp
<script>
  window.PRODUCTOS_INICIALES = @json($productos); // productos activos con imagen, etc.
  window.USUARIO_ES_ADMIN = @json(Auth::user()->rol_id == 1);
</script>

<script src="js/ventas_core.js"></script>
<script src="js/ventas_state.js"></script>
<script src="js/ventas_stock.js"></script>

<script src="js/ventas_productos.js"></script>
<script src="js/ventas_carrito.js"></script>
<script src="js/ventas_scanner.js"></script>

<script src="js/ventas_ui.js"></script>
<script src="js/ventas_espera.js"></script>
<script src="js/ventas_ordenar.js"></script>
<script src="js/ventas_registro.js"></script>

<script src="js/ventas_bootstrap.js"></script>


<script>
    document.getElementById("btn-ordenar").addEventListener("click", () => {
        if (window.matchMedia("(hover: none)").matches) {
            const btn = document.getElementById("btn-ordenar");
            btn.classList.add("show-tooltip");

            setTimeout(() => {
                btn.classList.remove("show-tooltip");
            }, 2000); // 2 segundos visible
        }
    });
</script>


@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const efectivo = document.querySelector('.metodo-pago-item[data-value="efectivo"]');
        const hidden   = document.getElementById("metodo_pago");

        if (efectivo && hidden) {
            efectivo.classList.add("active");
            hidden.value = "efectivo";
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const horaInput = document.getElementById('hora_actual');
        if (horaInput) {
            function actualizarHora() {
                const ahora = new Date();
                const horas = String(ahora.getHours()).padStart(2, '0');
                const minutos = String(ahora.getMinutes()).padStart(2, '0');
                const segundos = String(ahora.getSeconds()).padStart(2, '0');
                horaInput.value = `${horas}:${minutos}:${segundos}`;
            }
            setInterval(actualizarHora, 1000);
            actualizarHora();
        }
    });
</script>
<script>
$(document).ready(function () {

    // abrir modal
    $(document).on('click', '#open-modal-btn', function () {
        $('#clientModal').modal('show');
    });

    // enviar formulario
    $('#clientForm').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '/clientes',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                client_name: $('#client_name').val(),
                client_address: $('#client_address').val(),
                client_phone: $('#client_phone').val(),
                client_dni: $('#client_dni').val(),
                client_ruc: $('#client_ruc').val()
            },
            success: function (response) {

                // llenar inputs visuales
                $('#razon_social').val(response.nombre);
                $('#direccion').val(response.direccion);
                $('#documento').val(response.dni || response.ruc);

                // 🔥 sincronizar con POS
                if (window.setClienteVentaPOS) {
                    window.setClienteVentaPOS({
                        documento: response.dni || response.ruc,
                        razon: response.nombre,
                        direccion: response.direccion
                    });
                }

                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Cliente registrado correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    $('#clientForm')[0].reset();
                    $('#clientModal').modal('hide');
                });
            },
            error: function () {
                Swal.fire(
                    'Error',
                    'Error al registrar el cliente. Inténtalo nuevamente.',
                    'error'
                );
            }
        });
    });
});
</script>

@endpush
