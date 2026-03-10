/* ===========================
   movimientos.js (COMPLETO FE)
   Requiere: Bootstrap + SweetAlert2
=========================== */

document.addEventListener('DOMContentLoaded', () => {

    const panel = document.getElementById('offcanvasDetalle');
    const contenido = document.getElementById('detalleContenido');
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
        if (tipoRef !== 'venta') return;

        offcanvas.show();
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

            // Monto REAL a cobrar en el panel:
            // - credito => saldo
            // - pendiente => total
            const montoCobrar = (estado === 'credito') ? saldo : total;

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
                        estado === 'credito'
                        ? `<div class="text-danger fw-bold mt-1">
                                Saldo pendiente: ${money(saldo)}
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