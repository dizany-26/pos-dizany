/* ===========================
   movimientos.js (COMPLETO)
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

    // 👇 AGREGA ESTO AQUÍ
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

            // Render detalle completo
            contenido.innerHTML = `
            <div id="panel-detalle">

                <div class="card ui-card rounded-4 detalle-card mt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">${(v.tipo || v.tipo_comprobante || 'Venta')} • Valor total</span>
                        <div id="estadoVenta"></div>
                    </div>

                    <div class="detalle-total">
                        S/ ${total.toFixed(2)}
                    </div>

                    ${
                        estado === 'credito'
                        ? `<div class="text-danger fw-bold mt-1">
                                Saldo pendiente: S/ ${saldo.toFixed(2)}
                           </div>`
                        : ''
                    }

                    <hr>

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
                        <i class="fas fa-chart-line"></i>
                        <span>Ganancia</span>
                        <strong class="text-success">
                            S/ ${Number(v.ganancia || 0).toFixed(2)}
                        </strong>
                    </div>
                </div>

                <h6 class="mt-4 fw-semibold text-muted small text-uppercase">
                    Listado de productos
                </h6>

                <div class="listado-productos">
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
                                    S/ ${Number(p.subtotal || 0).toFixed(2)}
                                </div>
                            </div>
                        `).join('')
                        : `<div class="text-muted small">Sin productos</div>`
                    }
                </div>

                <div class="acciones-detalle sticky-actions">
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

                    <button class="btn-soft btn-soft-primary" type="button">
                        <i class="fas fa-print"></i>
                        <span>Imprimir</span>
                    </button>
                </div>
            </div>

            <div id="panel-cobro" style="display:none">
                <h6 class="fw-bold mt-3">Cobrar venta</h6>

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
                    <button type="button" class="accion-btn" onclick="volverDetalle()">
                        Volver
                    </button>

                    <button type="button" class="accion-btn success" onclick="confirmarCobro()">
                        Registrar pago
                    </button>
                </div>
            </div>
            `;

            // Badge estado
            const estadoEl = document.getElementById('estadoVenta');
            estadoEl.innerHTML = renderEstadoBadge(estado);

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

    // ===== Reglas =====
    // Pendiente: NO permitir menor. Permitir mayor (vuelto visual) y cobrar solo deuda.
    // Crédito:   NO permitir menor al saldo. Permitir mayor (vuelto visual) y cobrar solo saldo.
    const totalCobrar = Number(v.montoCobrar || 0);

    if (montoIngresado < totalCobrar) {
        if (v.estado === 'credito') {
            toast('warning', 'En crédito, el monto no puede ser menor al saldo pendiente');
        } else {
            toast('warning', 'En una venta pendiente debe pagar como mínimo el total');
        }
        return;
    }

    // Si paga más, backend solo debe registrar el monto real (deuda),
    // el vuelto es solo visual.
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

        // ✅ SweetAlert éxito (sin OK)
        toast('success', 'Deuda pagada con éxito');

        // recargar bonito luego del toast
        setTimeout(() => location.reload(), 900);

    } catch (err) {
        console.error(err);
        toast('error', 'Error al cobrar');
    }
}
