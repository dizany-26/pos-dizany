/* ===========================
   movimientos.js (COMPLETO FE)
   Requiere: Bootstrap + SweetAlert2
=========================== */

document.addEventListener('DOMContentLoaded', () => {

    const responsableCaja = document.getElementById('responsableCaja');
    const ayudaResponsableCaja = document.getElementById('ayudaResponsableCaja');
    const botonAbrirCaja = document.getElementById('btnAbrirAsignarCaja');

    const actualizarFlujoCaja = () => {
        if (!responsableCaja || !ayudaResponsableCaja || !botonAbrirCaja) return;

        const opcion = responsableCaja.options[responsableCaja.selectedIndex];
        const esCajaPropia = opcion?.dataset.propio === '1';
        const textoBoton = botonAbrirCaja.querySelector('span');

        if (!opcion?.value) {
            ayudaResponsableCaja.textContent = 'Selecciona quién será responsable del dinero y de las ventas de este turno.';
            if (textoBoton) textoBoton.textContent = 'Abrir caja';
            return;
        }

        if (esCajaPropia) {
            ayudaResponsableCaja.textContent = 'Abrirás tu propia caja. No se generará una notificación dirigida a otro usuario.';
            if (textoBoton) textoBoton.textContent = 'Abrir mi caja';
            return;
        }

        ayudaResponsableCaja.textContent = 'El usuario seleccionado recibirá una notificación con el fondo inicial asignado.';
        if (textoBoton) textoBoton.textContent = 'Asignar caja';
    };

    responsableCaja?.addEventListener('change', actualizarFlujoCaja);
    actualizarFlujoCaja();

    const panel = document.getElementById('offcanvasDetalle');
    const contenido = document.getElementById('detalleContenido');
    const panelTitle = document.getElementById('detalleMovimientoTitulo');
    if (!panel || !contenido) return;

    const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(panel);

    // ===== SweetAlert helpers (toast sin OK) =====
    const toast = (icon, text) => {
        if (typeof Swal === 'undefined') {
            alert(text);
            return;
        }
        Swal.fire({
            toast: true,
            position: 'top',
            icon,
            title: text,
            showConfirmButton: false,
            timer: 2200,
            timerProgressBar: true
        });
    };

    const toastSuccess = (text) => toast('success', text);
    const toastWarn = (text) => toast('warning', text);
    const toastError = (text) => toast('error', text);

    const money = (n) => `S/ ${Number(n || 0).toFixed(2)}`;

    function renderEstadoBadge(estado) {
        switch (estado) {
            case 'pagado':
                return `<span class="ui-badge ui-badge-success">Pagado</span>`;
            case 'pendiente':
                return `<span class="ui-badge ui-badge-warning">Pendiente</span>`;
            case 'credito':
                return `<span class="ui-badge ui-badge-danger">Crédito</span>`;
            default:
                return `<span class="ui-badge ui-badge-secondary">—</span>`;
        }
    }

    // ✅ NUEVO: Badge SUNAT
    function renderSunatBadge(estadoSunat) {
        const s = (estadoSunat || '').toLowerCase();
        switch (s) {
            case 'aceptado':
            case 'aceptada':
                return `<span class="ui-badge ui-badge-success">Aceptado SUNAT</span>`;
            case 'enviado':
            case 'procesando':
                return `<span class="ui-badge ui-badge-warning">Enviado a SUNAT</span>`;
            case 'rechazado':
                return `<span class="ui-badge ui-badge-danger">Rechazado SUNAT</span>`;
            case 'anulado':
                return `<span class="ui-badge ui-badge-secondary">Anulado</span>`;
            case 'pendiente':
                return `<span class="ui-badge ui-badge-secondary">SUNAT pendiente</span>`;
            default:
                return `<span class="ui-badge ui-badge-secondary">SUNAT —</span>`;
        }
    }

    // ===== Click en fila de movimientos =====
    document.addEventListener('click', async (e) => {

        const row = e.target.closest('.mov-row');
        if (!row) return;

        const ventaId = row.dataset.refId;
        const tipoRef = row.dataset.refTipo;
        const movimientoId = row.dataset.movId;

        if (tipoRef === 'gasto') {
            offcanvas.show();
            if (panelTitle) panelTitle.textContent = 'Detalle del gasto';
            contenido.innerHTML = `<div class="text-muted">Cargando...</div>`;

            try {
                const res = await fetch(`/movimientos/gastos/${ventaId}/detalle`, {
                    headers: { 'Accept': 'application/json' }
                });
                const gasto = await res.json();
                if (!res.ok) throw new Error(gasto.message || 'No se pudo cargar el gasto');

                contenido.innerHTML = `
                    <div class="card ui-card rounded-4 detalle-card mt-2 p-3">
                        <div class="text-muted small">Concepto</div>
                        <h5 class="fw-bold mt-1 mb-3">${gasto.descripcion ?? '—'}</h5>
                        <div class="d-flex justify-content-between align-items-end">
                            <span class="text-muted">Monto pagado</span>
                            <strong class="text-danger fs-4">S/ ${Number(gasto.monto || 0).toFixed(2)}</strong>
                        </div>
                    </div>
                    <div class="card ui-card rounded-4 mt-3 p-3">
                        <div class="detalle-item">
                            <i class="far fa-user-circle"></i>
                            <span>Responsable</span>
                            <strong>${gasto.responsable ?? '—'}</strong>
                        </div>
                        <div class="detalle-item">
                            <i class="far fa-calendar"></i>
                            <span>Fecha y hora</span>
                            <strong>${gasto.fecha ?? '—'}</strong>
                        </div>
                        <div class="detalle-item">
                            <i class="far fa-credit-card"></i>
                            <span>Método de pago</span>
                            <strong>${gasto.metodo_pago ?? '—'}</strong>
                        </div>
                        <div class="detalle-item">
                            <i class="fas fa-circle-check"></i>
                            <span>Estado</span>
                            <strong>${gasto.estado === 'activo' ? 'Registrado' : gasto.estado}</strong>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 small">
                        Este egreso fue descontado de la caja según su método de pago.
                    </div>
                `;
            } catch (error) {
                console.error(error);
                contenido.innerHTML = `<div class="text-danger">No se pudo cargar el detalle del gasto.</div>`;
            }
            return;
        }

        if (tipoRef === 'lote') {
            offcanvas.show();
            if (panelTitle) panelTitle.textContent = 'Detalle de la compra';
            contenido.innerHTML = `<div class="text-muted">Cargando...</div>`;

            try {
                const detailUrl = row.dataset.detailUrl || `/movimientos/compras/${movimientoId}/detalle`;
                const res = await fetch(detailUrl, {
                    headers: { 'Accept': 'application/json' }
                });
                const compra = await res.json();
                if (!res.ok) throw new Error(compra.message || 'No se pudo cargar la compra');
                window.__compra = compra;
                const estaPagada = compra.estado === 'pagado';

                const historial = compra.pagos?.length
                    ? compra.pagos.map(p => `
                        <div class="detalle-item align-items-start">
                            <i class="fas fa-receipt mt-1"></i>
                            <span>${p.fecha}<small class="d-block text-muted">${p.metodo}${p.operacion ? ` · ${p.operacion}` : ''}</small></span>
                            <strong>${money(p.monto)}<small class="d-block text-muted fw-normal">${p.responsable}</small></strong>
                        </div>`).join('')
                    : `<div class="text-muted small">Todavía no se registraron abonos.</div>`;

                contenido.innerHTML = `
                    <div id="panel-compra-detalle">
                        <div class="card ui-card rounded-4 detalle-card mt-2 p-3">
                            <div class="text-muted small">Compra de mercadería</div>
                            <div class="d-flex justify-content-between align-items-center mt-1 mb-3"><h5 class="fw-bold mb-0">${compra.comprobante}</h5>${estaPagada ? '<span class="ui-badge ui-badge-success">Pagado</span>' : (compra.estado === 'parcial' ? '<span class="ui-badge ui-badge-warning">Pago parcial</span>' : '<span class="ui-badge ui-badge-danger">Pendiente</span>')}</div>
                            <div class="d-flex justify-content-between"><span>Total</span><strong>${money(compra.total)}</strong></div>
                            ${!estaPagada ? `<div class="d-flex justify-content-between mt-2"><span>Pagado</span><strong class="text-success">${money(compra.pagado)}</strong></div><div class="d-flex justify-content-between border-top mt-2 pt-2"><span class="fw-bold">Saldo pendiente</span><strong class="text-danger fs-5">${money(compra.saldo)}</strong></div>` : ''}
                        </div>
                        <div class="card ui-card rounded-4 mt-3 p-3">
                            <div class="detalle-item"><i class="far fa-building"></i><span>Proveedor</span><strong>${compra.proveedor}</strong></div>
                            <div class="detalle-item"><i class="far fa-file-alt"></i><span>Comprobante</span><strong>${compra.comprobante}</strong></div>
                            <div class="detalle-item"><i class="fas fa-layer-group"></i><span>Lote</span><strong>${compra.numero_lote}</strong></div>
                            <div class="detalle-item"><i class="far fa-calendar"></i><span>Fecha de compra</span><strong>${compra.fecha_compra ?? '—'}</strong></div>
                            ${!estaPagada ? `<div class="detalle-item"><i class="far fa-calendar-check"></i><span>Vencimiento del pago</span><strong>${compra.fecha_vencimiento ?? 'Sin fecha'}</strong></div>` : ''}
                        </div>
                        <h6 class="mt-4 fw-semibold text-muted small text-uppercase">Productos del lote</h6>
                        <div class="card ui-card rounded-4 p-3 listado-productos">${(compra.productos || []).map(p => `<div class="producto-item-pro"><div class="producto-info"><div class="producto-nombre">${p.nombre}</div>${p.descripcion ? `<div class="producto-desc">${p.descripcion}</div>` : ''}<div class="producto-cantidad">${p.cantidad} unidades × S/ ${Number(p.costo || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 6 })}</div></div><div class="producto-precio">${money(p.subtotal)}</div></div>`).join('')}</div>
                        ${compra.pagos?.length ? `<h6 class="mt-4 fw-semibold text-muted small text-uppercase">Historial de pagos</h6><div class="card ui-card rounded-4 p-3">${historial}</div>` : ''}
                        ${compra.puede_pagar ? `<button type="button" class="btn-soft btn-soft-warning w-100 mt-3" onclick="mostrarPagoCompra()"><i class="fas fa-cash-register"></i><span>Registrar pago</span></button>` : ''}
                    </div>
                    <div id="panel-compra-pago" style="display:none">
                        <h6 class="fw-semibold text-muted text-uppercase small mt-3">Pagar deuda al proveedor</h6>
                        <div class="alert alert-info small">Este pago es de tesorería y no modifica el efectivo de la caja operativa.</div>
                        <label class="form-label">Monto (saldo ${money(compra.saldo)})</label>
                        <input id="cp_monto" type="number" class="form-control ui-input mb-3" min="0.01" max="${compra.saldo}" step="0.01" value="${Number(compra.saldo).toFixed(2)}">
                        <label class="form-label">Fecha</label>
                        <input id="cp_fecha" type="date" class="form-control ui-input mb-3" value="${new Date().toISOString().slice(0, 10)}">
                        <label class="form-label">Medio utilizado</label>
                        <select id="cp_metodo" class="form-select ui-input mb-3"><option value="">Seleccionar...</option><option value="efectivo_externo">Efectivo externo</option><option value="transferencia">Transferencia</option><option value="yape">Yape</option><option value="plin">Plin</option><option value="tarjeta">Tarjeta</option><option value="otro">Otro</option></select>
                        <label class="form-label">N.º de operación (opcional)</label>
                        <input id="cp_operacion" class="form-control ui-input mb-3" maxlength="80">
                        <label class="form-label">Observación (opcional)</label>
                        <textarea id="cp_observacion" class="form-control ui-input mb-3" maxlength="500"></textarea>
                        <div class="d-flex gap-2"><button type="button" class="btn-soft btn-soft-info flex-fill" onclick="volverCompraDetalle()">Volver</button><button type="button" class="btn-soft btn-soft-success flex-fill" onclick="confirmarPagoCompra()">Confirmar pago</button></div>
                    </div>`;
            } catch (error) {
                console.error(error);
                contenido.innerHTML = `<div class="text-danger">No se pudo cargar el detalle de la compra.</div>`;
            }
            return;
        }

        if (tipoRef !== 'venta') return;

        offcanvas.show();
        if (panelTitle) panelTitle.textContent = 'Detalle de la venta';
        contenido.innerHTML = `<div class="text-muted">Cargando...</div>`;

        try {
            const res = await fetch(`/ventas/${ventaId}/detalle`, {
                headers: { 'Accept': 'application/json' }
            });

            // Si backend devuelve HTML por error
            const text = await res.text();
            let v;
            try { v = JSON.parse(text); }
            catch {
                console.error('Respuesta no JSON:', text);
                contenido.innerHTML = `<div class="text-danger">Error al cargar detalle</div>`;
                return;
            }

            const estado = (v.estado || '').toLowerCase(); // pagado | pendiente | credito
            const total = Number(v.total || 0);
            const saldo = Number(v.saldo || 0);

            // El saldo es la fuente de verdad para ventas fiadas y parciales.
            const montoCobrar = ['credito', 'pendiente'].includes(estado) ? saldo : total;
            const esVentaCredito = ['credito', 'pendiente'].includes(estado);

            // Fuente de verdad global
            window.__venta = {
                id: Number(v.id || ventaId),
                estado,
                total,
                saldo,
                montoCobrar
            };
const pdfUrl = v.pdf_url || v.url_pdf || v.pdf || v.pdfPath || null;
const xmlUrl = v.xml_url || v.url_xml || v.xml || v.xmlPath || null;
const cdrUrl = v.cdr_url || v.url_cdr || v.cdr || v.cdrPath || null;
const sol = v.sunat_sol || {};
const documentoSol = sol.documento || null;
            // Render detalle FE
            contenido.innerHTML = `
            <div id="panel-detalle">

                <!-- ===== CARD RESUMEN (Comprobante + Estados + Total) ===== -->

                <div class="card ui-card rounded-4 detalle-card mt-2 p-3">

                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted small">Comprobante</div>
                            <div class="fw-bold">
                                ${(v.tipo_comprobante ?? v.tipo ?? 'Comprobante')}
                                ${(v.serie ?? '')}-${(v.numero ?? '')}
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-1 align-items-end">
                            <div id="estadoVenta"></div>
                            <div id="estadoSunat"></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-end mt-2">
                        <div class="text-muted small fw-semibold">Total</div>

                        <div class="d-flex align-items-end gap-2">
                            <span class="text-muted fw-semibold" style="font-size:14px; line-height:1;">S/</span>
                            <span class="fw-bold" style="font-size:30px; line-height:1;">
                                ${Number(total || 0).toFixed(2)}
                            </span>
                        </div>
                    </div>

                    ${
                        esVentaCredito
                        ? `<div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3">
                                <span class="text-muted">Saldo pendiente</span>
                                <strong class="text-danger fs-5">${money(saldo)}</strong>
                           </div>`
                        : ''
                    }

                </div>

                <!-- ===== DATOS ===== -->
                <div class="card ui-card rounded-4 mt-3 p-3">
                    <div class="detalle-item">
                        <i class="far fa-user-circle"></i>
                        <span>Vendedor</span>
                        <strong class="d-flex align-items-center gap-2">
                            <span class="bg-success rounded-circle" 
                                style="width:8px;height:8px;display:inline-block;"></span>
                            ${v.vendedor ?? '—'}
                        </strong>
                    </div>
                    
                    <div class="detalle-item">
                        <i class="far fa-calendar"></i>
                        <span>Fecha y hora</span>
                        <strong>${v.fecha_formato ?? '—'}</strong>
                    </div>

                    <div class="detalle-item">
                        <i class="far fa-credit-card"></i>
                        <span>Método de pago</span>
                        <strong>${v.metodo_pago ?? '—'}</strong>
                    </div>

                    <div class="detalle-item">
                        <i class="far fa-user"></i>
                        <span>Cliente</span>
                        <strong>${typeof v.cliente === 'string' ? v.cliente : (v.cliente?.nombre ?? '—')}</strong>
                    </div>

                    <div class="detalle-item">
                        <i class="fas fa-hand-holding-dollar"></i>
                        <span>Condición de pago</span>
                        <strong>${v.condicion_pago ?? (esVentaCredito ? 'Crédito' : 'Contado')}</strong>
                    </div>

                    ${v.metodo_pago === 'efectivo' && v.efectivo_recibido !== null ? `
                        <div class="detalle-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Efectivo recibido</span>
                            <strong>${money(v.efectivo_recibido)}</strong>
                        </div>
                        <div class="detalle-item">
                            <i class="fas fa-coins"></i>
                            <span>Vuelto</span>
                            <strong>${money(v.vuelto)}</strong>
                        </div>
                    ` : ''}

                    ${esVentaCredito ? `
                        <div class="detalle-item">
                            <i class="fas fa-money-check-dollar"></i>
                            <span>Monto abonado</span>
                            <strong class="text-success">${money(v.monto_pagado)}</strong>
                        </div>
                        <div class="detalle-item">
                            <i class="fas fa-coins"></i>
                            <span>Saldo por cobrar</span>
                            <strong class="text-danger">${money(saldo)}</strong>
                        </div>
                        <div class="detalle-item">
                            <i class="far fa-calendar-check"></i>
                            <span>Vencimiento</span>
                            <strong class="${v.credito_vencido ? 'text-danger' : ''}">
                                ${v.fecha_vencimiento ?? 'Sin fecha registrada'}${v.credito_vencido ? ' · Vencido' : ''}
                            </strong>
                        </div>
                    ` : ''}

                    ${
                        v.cliente_doc
                        ? `<div class="detalle-item">
                                <i class="fas fa-id-card"></i>
                                <span>Documento</span>
                                <strong>${v.cliente_doc}</strong>
                           </div>`
                        : ''
                    }

                    <div class="detalle-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Ganancia</span>
                        <strong class="text-success">
                            ${money(v.ganancia || 0)}
                        </strong>
                    </div>
                </div>

                <!-- ===== TRIBUTOS (FE) ===== -->
                <h6 class="mt-4 fw-semibold text-muted small text-uppercase">
                    Información tributaria
                </h6>

                <div class="card ui-card rounded-4 p-3">
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Subtotal</span>
                        <strong>${money(v.subtotal || 0)}</strong>
                    </div>

                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">IGV</span>
                        <strong>${money(v.igv || 0)}</strong>
                    </div>

                    <div class="d-flex justify-content-between py-1 border-top mt-2 pt-2">
                        <span class="fw-bold">Total</span>
                        <strong class="fw-bold">${money(total)}</strong>
                    </div>
                </div>

                ${sol.aplica ? `
                    <h6 class="mt-4 fw-semibold text-muted small text-uppercase">
                        Boleta oficial SUNAT SOL
                    </h6>
                    ${documentoSol ? `
                        <div class="card ui-card rounded-4 p-3 sol-linked-card">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="sol-status-icon"><i class="fas fa-check"></i></span>
                                <div>
                                    <div class="fw-bold">Boleta vinculada</div>
                                    <small class="text-muted">Este registro ya no puede reemplazarse.</small>
                                </div>
                            </div>
                            <div class="detalle-item"><i class="far fa-file-alt"></i><span>Comprobante SUNAT</span><strong>${documentoSol.serie}-${documentoSol.numero}</strong></div>
                            <div class="detalle-item"><i class="far fa-calendar-check"></i><span>Fecha de emisión</span><strong>${documentoSol.fecha || '—'}</strong></div>
                            <div class="detalle-item"><i class="fas fa-coins"></i><span>Total oficial</span><strong>${money(documentoSol.total)}</strong></div>
                        </div>
                    ` : sol.puede_vincular ? `
                        <form id="form-vincular-sol" class="card ui-card rounded-4 p-3 sol-link-form" action="${sol.link_url}" method="post">
                            <div class="sol-info-box mb-3">
                                <i class="fas fa-info-circle"></i>
                                <span>${esVentaCredito
                                    ? 'Emite la boleta en SUNAT SOL como venta al crédito y registra su vencimiento. Luego vincula aquí sus datos oficiales; no necesitas esperar a cobrar todo el saldo.'
                                    : 'Primero emite la boleta en SUNAT SOL. Luego registra aquí sus datos oficiales para relacionarla con esta venta.'}</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-5">
                                    <label class="form-label">Serie SUNAT</label>
                                    <input name="series" class="form-control ui-input text-uppercase" maxlength="4" pattern="(?:EB01|B[A-Za-z0-9]{3})" placeholder="EB01" required>
                                </div>
                                <div class="col-7">
                                    <label class="form-label">Número</label>
                                    <input name="number" type="number" class="form-control ui-input" min="1" placeholder="125" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Fecha y hora de emisión</label>
                                    <input name="issued_at" type="datetime-local" class="form-control ui-input" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3">
                                <span class="text-muted">Total de esta venta</span>
                                <strong class="fs-5">${money(total)}</strong>
                            </div>
                            <button type="submit" class="btn-soft btn-soft-primary w-100 mt-3">
                                <i class="fas fa-link"></i><span>Vincular boleta oficial</span>
                            </button>
                        </form>
                    ` : `
                        <div class="card ui-card rounded-4 p-3 text-muted small">
                            Esta venta SEE-SOL todavía no tiene una boleta oficial vinculada. Solicita a un administrador que complete el registro.
                        </div>
                    `}
                ` : ''}

                <!-- ===== PRODUCTOS ===== -->
                <h6 class="mt-4 fw-semibold text-muted small text-uppercase">
                    Listado de productos
                </h6>

                <div class="card ui-card rounded-4 mt-3 p-3 listado-productos">
                    ${
                        Array.isArray(v.productos) && v.productos.length
                        ? v.productos.map(p => `
                            <div class="producto-item-pro">
                                <img src="${p.imagen ?? ''}" class="producto-img" onerror="this.style.display='none'">
                                <div class="producto-info">
                                    <div class="producto-nombre">${p.nombre ?? '—'}</div>
                                    ${p.descripcion ? `<div class="producto-desc">${p.descripcion}</div>` : ''}
                                    <div class="producto-cantidad">${p.cantidad_txt ?? ''}</div>
                                </div>
                                <div class="producto-precio">
                                    ${money(p.subtotal || 0)}
                                </div>
                            </div>
                        `).join('')
                        : `<div class="text-muted small">Sin productos</div>`
                    }
                </div>

                <!-- ===== ACCIONES (sticky abajo) ===== -->
                <div class="acciones-detalle sticky-actions d-flex gap-2 flex-wrap mt-3">
                    ${pdfUrl ? `
                        <button class="btn-soft btn-soft-primary" type="button"
                                onclick="menuPdf('${pdfUrl || ''}')">
                                <i class="fas fa-file-pdf"></i>
                                <span>PDF</span>
                        </button>` : ''}

                    ${v.xml_url ? `
                        <a class="btn-soft btn-soft-info" href="${v.xml_url}">
                            <i class="fas fa-file-code"></i>
                            <span>XML</span>
                        </a>` : ''}

                    ${v.cdr_url ? `
                        <a class="btn-soft btn-soft-success" href="${v.cdr_url}">
                            <i class="fas fa-check-circle"></i>
                            <span>CDR</span>
                        </a>` : ''}

                    ${
                        (estado === 'pendiente' || estado === 'credito')
                        ? `
                            <button class="btn-soft btn-soft-warning" onclick="mostrarCobro()">
                                <i class="fas fa-cash-register"></i>
                                <span>Cobrar</span>
                            </button>
                          `
                        : ''
                    }
                </div>
            </div>

            <div id="panel-cobro" style="display:none">
                <h6 class="fw-semibold text-muted text-uppercase small mt-3">
                    Cobrar venta
                </h6>

                <div class="fw-bold mb-2">
                    Total a pagar: S/ <span id="cc_total">${montoCobrar.toFixed(2)}</span>
                </div>

                <label class="form-label">Monto recibido</label>

                <input type="number"
                    id="cc_monto"
                    class="form-control ui-input mb-2"
                    value="0"
                    min="0"
                    step="0.01">

                <select id="cc_metodo" class="form-select ui-input mb-2">
                    <option value="">Seleccione método</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                    <option value="transferencia">Transferencia</option>
                </select>

                <div class="fw-bold text-success mt-2">
                    Vuelto: S/ <span id="cc_vuelto">0.00</span>
                </div>

                <div class="d-flex gap-2 mt-3">
                    <button type="button" class="btn-soft btn-soft-info flex-fill" onclick="volverDetalle()">
                        Volver
                    </button>

                    <button type="button" class="btn-soft btn-soft-success flex-fill" onclick="confirmarCobro()">
                        Registrar pago
                    </button>
                </div>
            </div>
            `;

            // Pintar badges
            const estadoEl = document.getElementById('estadoVenta');
            if (estadoEl) estadoEl.innerHTML = renderEstadoBadge(estado);

            const sunatEl = document.getElementById('estadoSunat');
            if (sunatEl) sunatEl.innerHTML = renderSunatBadge(v.estado_sunat);

        } catch (err) {
            console.error(err);
            contenido.innerHTML = `<div class="text-danger">Error al cargar detalle</div>`;
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('#form-vincular-sol');
        if (!form) return;
        event.preventDefault();

        const submit = form.querySelector('button[type="submit"]');
        const original = submit.innerHTML;
        submit.disabled = true;
        submit.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Vinculando...</span>';

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || '',
                },
                body: new FormData(form),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = Object.values(data.errors || {}).flat()[0]
                    || data.message
                    || 'No se pudo vincular la boleta oficial.';
                throw new Error(message);
            }

            if (window.Swal) {
                await Swal.fire({ icon: 'success', title: 'Boleta vinculada', text: data.message, confirmButtonText: 'Entendido' });
            }
            window.location.reload();
        } catch (error) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'No se pudo vincular', text: error.message });
            else alert(error.message);
            submit.disabled = false;
            submit.innerHTML = original;
        }
    });

    // ===== Recalcular vuelto EN VIVO (funciona para pendiente y crédito) =====
    document.addEventListener('input', (e) => {
        if (e.target.id !== 'cc_monto') return;

        const vueltoEl = document.getElementById('cc_vuelto');
        if (!vueltoEl) return;

        const recibido = Number(e.target.value || 0);
        const totalCobrar = window.__venta?.montoCobrar ?? 0;

        const vuelto = recibido - totalCobrar;
        vueltoEl.innerText = (vuelto > 0) ? vuelto.toFixed(2) : '0.00';
    });

    // Exponer helpers por si los usas en otros lados
    window.__toast = { toastSuccess, toastWarn, toastError };
});

/* ===========================
   FUNCIONES GLOBALES
=========================== */

function mostrarCobro() {
    const det = document.getElementById('panel-detalle');
    const cob = document.getElementById('panel-cobro');
    if (!det || !cob) return;

    det.style.display = 'none';
    cob.style.display = 'block';

    // poner 0 por defecto para que el usuario ingrese
    const inputMonto = document.getElementById('cc_monto');
    const vueltoEl = document.getElementById('cc_vuelto');
    if (inputMonto) inputMonto.value = '0';
    if (vueltoEl) vueltoEl.innerText = '0.00';
}

function mostrarPagoCompra() {
    document.getElementById('panel-compra-detalle').style.display = 'none';
    document.getElementById('panel-compra-pago').style.display = 'block';
}

function volverCompraDetalle() {
    document.getElementById('panel-compra-pago').style.display = 'none';
    document.getElementById('panel-compra-detalle').style.display = 'block';
}

async function confirmarPagoCompra() {
    const compra = window.__compra;
    const monto = Number(document.getElementById('cp_monto')?.value || 0);
    const metodo = document.getElementById('cp_metodo')?.value;
    if (!compra || monto <= 0 || monto > Number(compra.saldo) || !metodo) {
        window.__toast?.toastWarn('Revisa el monto y selecciona el medio de pago.');
        return;
    }

    try {
        const res = await fetch(compra.pago_url || `/movimientos/compras/${compra.movimiento_id}/pagos`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                monto,
                fecha: document.getElementById('cp_fecha')?.value,
                metodo_pago: metodo,
                numero_operacion: document.getElementById('cp_operacion')?.value || null,
                observacion: document.getElementById('cp_observacion')?.value || null
            })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'No se pudo registrar el pago');
        window.__toast?.toastSuccess(data.message);
        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        window.__toast?.toastError(error.message);
    }
}

function volverDetalle() {
    const det = document.getElementById('panel-detalle');
    const cob = document.getElementById('panel-cobro');
    if (!det || !cob) return;

    cob.style.display = 'none';
    det.style.display = 'block';
}

async function confirmarCobro() {

    const v = window.__venta;
    if (!v) return;

    const inputMonto = document.getElementById('cc_monto');
    const metodoEl = document.getElementById('cc_metodo');

    const montoIngresado = Number(inputMonto?.value || 0);
    const metodo = metodoEl?.value || '';

    const toast = (icon, text) => {
        if (typeof Swal === 'undefined') { alert(text); return; }
        Swal.fire({
            toast: true,
            position: 'top',
            icon,
            title: text,
            showConfirmButton: false,
            timer: 2200,
            timerProgressBar: true
        });
    };

    if (!montoIngresado || montoIngresado <= 0) {
        toast('warning', 'Ingrese un monto válido');
        return;
    }

    if (!metodo) {
        toast('warning', 'Seleccione método');
        return;
    }

    const totalCobrar = Number(v.montoCobrar || 0);

    if (montoIngresado < totalCobrar) {
        if (v.estado === 'credito') {
            toast('warning', 'En crédito, el monto no puede ser menor al saldo pendiente');
        } else {
            toast('warning', 'En una venta pendiente debe pagar como mínimo el total');
        }
        return;
    }

    const montoAEnviar = totalCobrar;

    const url = (v.estado === 'credito')
        ? `/ventas/${v.id}/pagar-credito`
        : `/ventas/${v.id}/cerrar-pendiente`;

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                monto_pagado: montoAEnviar,
                metodo_pago: metodo
            })
        });

        const text = await res.text();
        let data;
        try { data = JSON.parse(text); }
        catch {
            console.error('Respuesta no JSON:', text);
            toast('error', 'Error del servidor');
            return;
        }

        if (!res.ok || !data.success) {
            toast('error', data.message || 'Error al cobrar');
            return;
        }

        toast('success', 'Deuda pagada con éxito');
        setTimeout(() => location.reload(), 900);

    } catch (err) {
        console.error(err);
        toast('error', 'Error al cobrar');
    }
}

function menuPdf(url) {

    url = fixUrlHost(url);
    if (!url) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'No hay PDF generado' });
        } else {
            alert('No hay PDF generado');
        }
        return;
    }

    if (typeof Swal === 'undefined') {
        // fallback simple
        window.open(url, '_blank');
        return;
    }

    Swal.fire({
        title: 'Comprobante PDF',
        text: '¿Qué deseas hacer?',
        icon: 'info',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: 'Imprimir',
        denyButtonText: 'Descargar',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#0f172a'
    }).then((r) => {
        if (r.isConfirmed) {
            imprimirPdf(url);
        } else if (r.isDenied) {
            descargarPdf(url);
        }
    });
}

function imprimirPdf(url) {

    url = fixUrlHost(url);
    const w = window.open(url, '_blank');
    if (!w) return;

    // Intento de auto-print (en algunos navegadores no se permite; igual abre el PDF)
    const timer = setInterval(() => {
        try {
            if (w.document && w.document.readyState === 'complete') {
                clearInterval(timer);
                w.focus();
                w.print();
            }
        } catch (e) {
            // Si el navegador bloquea acceso al documento del PDF, no pasa nada.
            // El PDF queda abierto y el usuario imprime manual.
        }
    }, 300);

    setTimeout(() => clearInterval(timer), 5000);
}

function descargarPdf(url) {

    url = fixUrlHost(url);
    const a = document.createElement('a');
    a.href = url;
    a.download = ''; // deja que el servidor/navegador defina nombre
    document.body.appendChild(a);
    a.click();
    a.remove();
}

function fixUrlHost(url) {
    if (!url) return '';

    try {
        const currentOrigin = window.location.origin; // ej: http://192.168.1.50:8000
        const u = new URL(url, currentOrigin);

        // Si viene con localhost, lo cambiamos por el origin actual
        if (u.hostname === 'localhost' || u.hostname === '127.0.0.1') {
            const fixed = new URL(currentOrigin);
            u.protocol = fixed.protocol;
            u.host = fixed.host; // incluye puerto
        }

        return u.toString();
    } catch (e) {
        // si es una ruta relativa tipo /comprobantes/xx.pdf
        if (url.startsWith('/')) return window.location.origin + url;
        return url;
    }
}
