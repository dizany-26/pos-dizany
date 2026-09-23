// ============================
// TOTALES / CÁLCULOS GLOBALES
// ============================

/**
 * Formatea un número a 2 o 3 decimales dependiendo si el tercer decimal es cero.
 * Usa toLocaleString para el formato de moneda peruano (comas/puntos).
 */
function formatPrecioDinamico(precio) {
    // Verificar si el precio tiene un tercer decimal distinto de cero
    const precioRedondeadoA2 = Math.round(precio * 100) / 100;
    const usaTresDecimales = Math.abs(precio - precioRedondeadoA2) > 0.0001; // Un pequeño margen de error

    if (usaTresDecimales) {
        // Formato con 3 decimales: 0.125 -> 0,125
        return precio.toLocaleString('es-PE', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
    } else {
        // Formato con 2 decimales: 1.5 -> 1,50 | 1.0 -> 1,00
        return precio.toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function productosParaAutorizarDescuento(venta) {
    return (venta?.productos || [])
        .filter(item => Number(item.descuento_valor || 0) > 0)
        .map(item => ({
            producto_id: Number(item.producto_id || item.id),
            cantidad: parseInt(item.cantidad) || 0,
            presentacion: item.tipo_venta || "unidad",
            descuento_tipo: item.descuento_tipo || "monto",
            descuento_valor: Number(item.descuento_valor || 0),
            descuento_motivo: String(item.descuento_motivo || "").trim(),
            nombre: item.nombre || "Producto",
            precio_original: calcularPrecioFinal(Number(item.precio_unitario || 0))
        }));
}

function firmaDescuentosVenta(venta) {
    return JSON.stringify(productosParaAutorizarDescuento(venta).map(item => ({
        producto_id: item.producto_id,
        cantidad: item.cantidad,
        presentacion: item.presentacion,
        descuento_tipo: item.descuento_tipo,
        descuento_valor: Number(item.descuento_valor).toFixed(4),
        descuento_motivo: item.descuento_motivo
    })));
}

function escaparHtmlDescuento(valor) {
    return String(valor ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

async function solicitarAutorizacionDescuentos(venta) {
    const productos = productosParaAutorizarDescuento(venta);
    if (!productos.length) return true;

    const filas = productos.map(item => {
        const descuentoUnitario = item.descuento_tipo === "porcentaje"
            ? item.precio_original * item.descuento_valor / 100
            : item.descuento_valor / Math.max(1, item.cantidad);
        const descuentoTotal = item.descuento_tipo === "porcentaje"
            ? descuentoUnitario * item.cantidad
            : item.descuento_valor;
        const precioFinal = Math.max(0, item.precio_original - descuentoUnitario);
        const etiqueta = item.descuento_tipo === "porcentaje"
            ? `${formatPrecioDinamico(item.descuento_valor)}%`
            : `S/ ${formatPrecioDinamico(item.descuento_valor)} total`;

        return `<div class="border rounded-3 p-2 mb-2 text-start">
            <div class="d-flex justify-content-between gap-2 fw-bold small">
                <span>${escaparHtmlDescuento(item.nombre)}</span>
                <span class="text-success text-nowrap">− S/ ${formatPrecioDinamico(descuentoTotal)}</span>
            </div>
            <div class="small text-muted mt-1">${item.cantidad} ${escaparHtmlDescuento(item.presentacion)} · S/ ${formatPrecioDinamico(item.precio_original)} → S/ ${formatPrecioDinamico(precioFinal)} · ${etiqueta}</div>
            <div class="small mt-1">Motivo: ${escaparHtmlDescuento(item.descuento_motivo)}</div>
        </div>`;
    }).join("");

    const totalDescuento = productos.reduce((total, item) => {
        const unitario = item.descuento_tipo === "porcentaje"
            ? item.precio_original * item.descuento_valor / 100
            : 0;
        return total + (item.descuento_tipo === "porcentaje"
            ? unitario * item.cantidad
            : item.descuento_valor);
    }, 0);

    const resultado = await Swal.fire({
        icon: "warning",
        title: "Autorizar descuentos",
        width: 520,
        html: `<div class="text-start">
            <p class="small mb-2">Revisa los descuentos preparados por el vendedor. La clave aprobará únicamente esta lista.</p>
            <div style="max-height:220px;overflow:auto" class="pe-1">${filas}</div>
            <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2 mb-3"><span>Descuento total</span><span class="text-success">− S/ ${formatPrecioDinamico(totalDescuento)}</span></div>
            <label class="form-label small fw-bold" for="swal-admin-usuario">Usuario o correo del administrador</label>
            <input type="text" tabindex="-1" aria-hidden="true" autocomplete="username" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
            <input type="password" tabindex="-1" aria-hidden="true" autocomplete="current-password" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
            <input id="swal-admin-usuario" name="discount-admin-identity" class="form-control mb-2" autocomplete="one-time-code" autocapitalize="none" spellcheck="false" data-lpignore="true" data-1p-ignore="true" maxlength="100" value="" readonly>
            <label class="form-label small fw-bold" for="swal-admin-clave">Contraseña</label>
            <div class="position-relative">
                <input id="swal-admin-clave" name="discount-admin-secret" class="form-control pe-5" type="password" autocomplete="one-time-code" data-lpignore="true" data-1p-ignore="true" maxlength="255" value="" readonly>
                <button id="swal-admin-ver-clave" class="btn position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent shadow-none text-muted me-1" type="button" tabindex="-1" title="Mostrar contraseña" aria-label="Mostrar contraseña">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
            </div>
        </div>`,
        showCancelButton: true,
        confirmButtonText: "Autorizar y continuar",
        cancelButtonText: "Cancelar",
        showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        didOpen: () => {
            const usuario = document.getElementById("swal-admin-usuario");
            const clave = document.getElementById("swal-admin-clave");
            const botonVer = document.getElementById("swal-admin-ver-clave");

            usuario.value = "";
            clave.value = "";
            const habilitarEscritura = (campo) => {
                campo.addEventListener("focus", () => campo.removeAttribute("readonly"), { once: true });
                campo.addEventListener("pointerdown", () => campo.removeAttribute("readonly"), { once: true });
            };
            habilitarEscritura(usuario);
            habilitarEscritura(clave);

            botonVer.addEventListener("click", () => {
                const visible = clave.type === "text";
                clave.type = visible ? "password" : "text";
                botonVer.title = visible ? "Mostrar contraseña" : "Ocultar contraseña";
                botonVer.setAttribute("aria-label", botonVer.title);
                botonVer.querySelector("i")?.classList.toggle("fa-eye", visible);
                botonVer.querySelector("i")?.classList.toggle("fa-eye-slash", !visible);
                clave.focus();
            });
        },
        preConfirm: async () => {
            const usuario = document.getElementById("swal-admin-usuario")?.value.trim() || "";
            const clave = document.getElementById("swal-admin-clave")?.value || "";
            if (!usuario || !clave) {
                return Swal.showValidationMessage("Ingresa el usuario o correo y la contraseña del administrador.");
            }

            try {
                const response = await fetch("/ventas/autorizar-descuentos", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content || ""
                    },
                    body: JSON.stringify({ usuario, clave, productos })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || "No se pudo autorizar el descuento.");
                }
                return data;
            } catch (error) {
                return Swal.showValidationMessage(error.message || "No se pudo validar al administrador.");
            }
        }
    });

    if (!resultado.isConfirmed) return false;

    venta.descuento_autorizacion_token = resultado.value.token;
    venta.descuento_autorizacion_firma = firmaDescuentosVenta(venta);
    venta.descuento_autorizado_por_nombre = resultado.value.admin;
    guardarPOSAhora();
    return true;
}

// ===============================
// UI / STEPS / CLIENTE / PAGO / VUELTO / SERIE-CORRELATIVO
// ===============================

// ============================
// showStep (GLOBAL)
// ============================
function showStep(n) {
    document.querySelectorAll(".step-panel").forEach(p => p.classList.remove("is-active"));
    document.getElementById("step-" + n)?.classList.add("is-active");

    const v = ventaActiva();
    if (v) v.fase = n;

    if (typeof snapshotPOS === "function") {
        posSaveDebounced(snapshotPOS, 10);
    }
}

// ============================
// HELPERS: estado cliente no guardado
// ============================
function leerEstadoClienteNoGuardado() {
    const iconoSave = document.getElementById("icono-save");
    return iconoSave ? !iconoSave.classList.contains("d-none") : false;
}

// ============================
// Volcar UI -> Venta activa (GLOBAL)
// ============================
function volcarUIaVentaActiva() {
    const v = ventaActiva();
    if (!v) return;

    const documentoInput = document.getElementById("documento");
    const razonInput     = document.getElementById("razon_social");
    const direccionInput = document.getElementById("direccion");
    const hiddenMetodoPago = document.getElementById("metodo_pago");
    const tipoComprobante = document.getElementById("tipo_comprobante");
    const tipoDocumento = document.getElementById("tipo_documento_cliente");
    const informacionAdicional = document.getElementById("informacion_adicional");
    const clienteModo = document.querySelector('input[name="cliente_documento_modo"]:checked');

    if (!v.cliente) {
        v.cliente = { documento: "", razon: "", direccion: "", no_guardado: false };
    }

    v.cliente.documento   = documentoInput?.value || "";
    v.cliente.razon       = razonInput?.value || "";
    v.cliente.direccion   = direccionInput?.value || "";
    v.cliente.no_guardado = leerEstadoClienteNoGuardado();
    v.cliente.sin_documento = clienteModo?.value === "sin_documento";

    v.tipo_comprobante = tipoComprobante?.value || "boleta";
    v.cliente_modo = clienteModo?.value || "sin_documento";
    v.tipo_documento = tipoDocumento?.value || "dni";
    v.informacion_adicional = informacionAdicional?.value || "";

    v.metodo_pago = hiddenMetodoPago?.value || "";

    if (window.actualizarAliasVentaDesdeCliente) {
        actualizarAliasVentaDesdeCliente();
    }

    if (typeof snapshotPOS === "function") {
        posSaveDebounced(snapshotPOS, 50);
    }

    if (typeof window.renderVentasEsperaPanel === "function") {
        window.renderVentasEsperaPanel();
    }
}

// ============================
// Restaurar venta -> UI (GLOBAL)
// ============================
function restaurarVentaActivaEnUI() {
    const v = ventaActiva();
    if (!v) return;

    const documentoInput = document.getElementById("documento");
    const razonInput     = document.getElementById("razon_social");
    const direccionInput = document.getElementById("direccion");
    const hiddenMetodoPago = document.getElementById("metodo_pago");
    const tipoComprobante = document.getElementById("tipo_comprobante");
    const tipoDocumento = document.getElementById("tipo_documento_cliente");
    const informacionAdicional = document.getElementById("informacion_adicional");

    v.tipo_comprobante = v.tipo_comprobante || "boleta";
    v.cliente_modo = v.cliente_modo || (v.cliente?.documento ? "con_documento" : "sin_documento");
    v.tipo_documento = v.tipo_documento || (v.cliente?.documento?.length === 11 ? "ruc" : "dni");
    v.informacion_adicional = v.informacion_adicional || "";

    if (tipoComprobante) tipoComprobante.value = v.tipo_comprobante;
    if (tipoDocumento) tipoDocumento.value = v.tipo_documento;
    const modoRadio = document.querySelector(`input[name="cliente_documento_modo"][value="${v.cliente_modo}"]`);
    if (modoRadio) modoRadio.checked = true;

    if (documentoInput) documentoInput.value = v.cliente?.documento || "";
    if (razonInput)     razonInput.value     = v.cliente?.razon || "";
    if (direccionInput) direccionInput.value = v.cliente?.direccion || "";
    if (informacionAdicional) informacionAdicional.value = v.informacion_adicional;

    if (typeof window.actualizarFlujoClienteComprobante === "function") {
        window.actualizarFlujoClienteComprobante(true);
    }

    if (hiddenMetodoPago) hiddenMetodoPago.value = v.metodo_pago || "";

    document.querySelectorAll(".metodo-pago-item").forEach(item => {
        item.classList.toggle("active", (v.metodo_pago || "") === item.dataset.value);
    });

    if (!v.metodo_pago) {
        const efectivo = document.querySelector('.metodo-pago-item[data-value="efectivo"]');
        if (efectivo && hiddenMetodoPago) {
            efectivo.classList.add("active");
            hiddenMetodoPago.value = "efectivo";
            v.metodo_pago = "efectivo";
        }
    }

    // La venta restaurada puede tener un método distinto al que estaba visible
    // anteriormente. Recalcular siempre el flujo evita mostrar "Confirmar venta"
    // mientras la interfaz indica Efectivo.
    actualizarBotonesSegunMetodoPagado();

    showStep(v.fase || 1);
}

// ============================
// Totales / resumen (usa calcularTotal del carrito)
// ============================
function actualizarResumen() {
    if (typeof calcularTotal !== "function") return;

    const { subtotal, igv, total, igvPercent } = calcularTotal();
    const descuento = typeof descuentoTotalCarrito === "function" ? descuentoTotalCarrito() : 0;

    const opEl   = document.getElementById("resumen-op-gravadas");
    const igvEl  = document.getElementById("resumen-igv-monto");
    const totEl  = document.getElementById("resumen-total");
    const igvPEl = document.getElementById("resumen-igv-porcentaje");

    if (opEl)   opEl.innerText   = "S/ " + subtotal.toFixed(2);
    const descuentoEl = document.getElementById("resumen-descuento-monto");
    const descuentoRow = document.getElementById("resumen-descuento-row");
    if (descuentoEl) descuentoEl.innerText = "- S/ " + descuento.toFixed(2);
    if (descuentoRow) descuentoRow.style.display = descuento > 0 ? "flex" : "none";
    if (igvEl)  igvEl.innerText  = "S/ " + igv.toFixed(2);
    if (totEl)  totEl.innerText  = "S/ " + total.toFixed(2);
    if (igvPEl) igvPEl.innerText = igvPercent.toFixed(0) + "%";

    const totalFooter = document.getElementById("total-general-footer");
    if (totalFooter) totalFooter.innerText = total.toFixed(2);

    const opGravadasInput = document.querySelector('[name="op_gravadas"]');
    const totalInput      = document.querySelector('[name="total"]');
    const montoPagadoInput = document.querySelector('[name="monto_pagado"]');

    if (opGravadasInput) opGravadasInput.value = subtotal.toFixed(2);
    if (totalInput) totalInput.value = total.toFixed(2);
    if (montoPagadoInput) montoPagadoInput.value = total.toFixed(2);
}

// ============================
// Botón carrito (step2)
// ============================
function actualizarBotonCarrito() {
    const btnIrStep2 = document.getElementById("btn-ir-step2");
    if (!btnIrStep2) return;

    const v = ventaActiva();
    const cantidad = (v.productos || []).length;
    const { total } = calcularTotal();
    const totalFormateado = formatPrecioDinamico(total);
    const mobileCount = document.getElementById("mobile-cart-count");
    const mobileTotal = document.getElementById("mobile-cart-total");
    const mobilePanelCount = document.getElementById("mobile-cart-panel-count");
    const mobileLauncher = document.getElementById("btn-carrito-movil");

    if (mobileCount) mobileCount.textContent = cantidad;
    if (mobileTotal) mobileTotal.textContent = `S/ ${totalFormateado}`;
    if (mobilePanelCount) {
        mobilePanelCount.textContent = `${cantidad} ${cantidad === 1 ? "producto" : "productos"}`;
    }
    mobileLauncher?.classList.toggle("has-items", cantidad > 0);

    if (cantidad === 0) {
        btnIrStep2.innerHTML = `0 Continuar`;
        btnIrStep2.disabled = true;
        return;
    }

    btnIrStep2.disabled = false;
    btnIrStep2.innerHTML = `
        <span class="badge bg-dark me-2">${cantidad}</span>
        <span class="flex-grow-1 text-start">Continuar</span>
        <span class="fw-semibold">S/ ${totalFormateado}</span>
        <i class="fas fa-arrow-right ms-2"></i>
    `;
}

// ============================
// Preparar fase 3: PAGADO (vuelto)
// ============================
function prepararFase3() {
    const inputTotalVenta = document.getElementById("vuelto-total-venta");
    const inputPaga       = document.getElementById("vuelto-paga");
    const inputVuelto     = document.getElementById("vuelto-mostrar");

    const { total } = calcularTotal();

    if (document.getElementById("metodo_pago")?.value === "mixto") {
        window.prepararPagoMixto?.(total);
        return;
    }

    document.getElementById("pago-mixto-wrap")?.classList.add("d-none");

    if (inputTotalVenta) inputTotalVenta.value = formatPrecioDinamico(total);
    if (inputPaga) inputPaga.value = "";
    if (inputVuelto) inputVuelto.value = "";

    document.getElementById("step3-titulo").textContent = "Calcula el cambio de tu venta";
    document.getElementById("step3-pago-wrap")?.classList.remove("d-none");
    document.getElementById("step3-resultado-wrap")?.classList.remove("d-none");
    document.getElementById("step3-pago-label").textContent = "Efectivo recibido";
    document.getElementById("step3-resultado-label").textContent = "Vuelto";
    const dueWrap = document.getElementById("credito-vencimiento-wrap");
    const dueInput = document.getElementById("credito-vencimiento");
    // Una venta pagada no genera saldo ni cuotas.
    dueWrap?.classList.add("d-none");
    if (dueInput) {
        dueInput.required = false;
        dueInput.value = "";
    }
}

// ============================
// Preparar fase 3: CRÉDITO (adelanto)
// ============================
function prepararFase3Credito() {
    const inputTotalVenta = document.getElementById("vuelto-total-venta");
    const inputPaga       = document.getElementById("vuelto-paga");
    const inputVuelto     = document.getElementById("vuelto-mostrar");

    const { total } = calcularTotal();
    const estado = (document.getElementById("estado_pago")?.value || "pendiente").toLowerCase();

    if (inputTotalVenta) inputTotalVenta.value = formatPrecioDinamico(total);

    document.getElementById("step3-titulo").textContent = estado === "pendiente"
        ? "Confirmar venta fiada"
        : "Configurar venta al crédito";

    const pagoWrap = document.getElementById("step3-pago-wrap");
    const resultadoWrap = document.getElementById("step3-resultado-wrap");
    const pagoLabel = document.getElementById("step3-pago-label");
    const resultadoLabel = document.getElementById("step3-resultado-label");

    pagoWrap?.classList.toggle("d-none", estado === "pendiente");
    resultadoWrap?.classList.toggle("d-none", estado === "pendiente");

    if (inputPaga) {
        inputPaga.value = "";
        inputPaga.placeholder = "Ingresa el adelanto";
    }
    if (pagoLabel) pagoLabel.textContent = "Adelanto recibido";
    if (resultadoLabel) resultadoLabel.textContent = "Saldo pendiente";

    if (inputVuelto) inputVuelto.value = "";

    const dueWrap = document.getElementById("credito-vencimiento-wrap");
    const dueInput = document.getElementById("credito-vencimiento");
    dueWrap?.classList.remove("d-none");
    if (dueInput) {
        dueInput.required = true;
        if (!dueInput.value) dueInput.value = new Date().toISOString().slice(0, 10);
    }
}

// ============================
function activarEfectivoPorDefecto(items, hiddenMetodoPago) {
    items.forEach(i => {
        i.classList.remove("active");
        if (i.dataset.value === "efectivo") {
            i.classList.add("active");
        }
    });

    if (hiddenMetodoPago) {
        hiddenMetodoPago.value = "efectivo";
    }
}
function actualizarBotonesSegunMetodoPagado() {
    const estado = document.getElementById("estado_pago")?.value?.toLowerCase();
    const metodo = document.getElementById("metodo_pago")?.value;

    const btnIrStep3 = document.getElementById("btn-ir-step3");
    const btnConfirmarDirecto = document.getElementById("btn-confirmar-venta-directo");

    if (estado !== "pagado") return;

    if (metodo === "efectivo" || metodo === "mixto") {
        if (btnIrStep3) btnIrStep3.style.display = "";
        if (btnConfirmarDirecto) btnConfirmarDirecto.style.display = "none";
    } else if (metodo) {
        if (btnIrStep3) btnIrStep3.style.display = "none";
        if (btnConfirmarDirecto) btnConfirmarDirecto.style.display = "block";
    }
}


// Estado de venta: dinámica pago
// ============================
function manejarEstadoVenta() {
    const estadoPagoSelect = document.getElementById("estado_pago");
    const hiddenMetodoPago = document.getElementById("metodo_pago");
    const items = document.querySelectorAll(".metodo-pago-item");
    const btnIrStep3 = document.getElementById("btn-ir-step3");
    const btnConfirmarDirecto = document.getElementById("btn-confirmar-venta-directo");
    const opcionesMetodoPago = document.querySelector(".metodo-pago-opciones");

    const estado = (estadoPagoSelect?.value || "pagado").toLowerCase();
    opcionesMetodoPago?.classList.toggle("is-pending", estado === "pendiente");

    const labelVuelto = Array.from(
        document.querySelectorAll("label.form-label")
        ).find(l => l.textContent.trim().toLowerCase() === "vuelto");


    items.forEach(i => i.classList.remove("d-none", "active"));

    if (btnIrStep3) {
        btnIrStep3.style.display = "";
        btnIrStep3.innerHTML = `Continuar <i class="fas fa-arrow-right ms-2"></i>`;
    }

    if (btnConfirmarDirecto) btnConfirmarDirecto.style.display = "none";

    if (hiddenMetodoPago) hiddenMetodoPago.value = "";

    // 🟡 PENDIENTE
    if (estado === "pendiente") {
        items.forEach(i => {
            if (i.dataset.value !== "otro") i.classList.add("d-none");
            else i.classList.add("active");
        });

        if (hiddenMetodoPago) hiddenMetodoPago.value = "otro";
        if (btnIrStep3) {
            btnIrStep3.style.display = "";
            btnIrStep3.innerHTML = `Continuar <i class="fas fa-arrow-right ms-2"></i>`;
        }
        // El paso siguiente solo mostrará total y vencimiento.
        if (labelVuelto) {
            labelVuelto.classList.remove("d-none");
        }

        if (btnConfirmarDirecto) btnConfirmarDirecto.style.display = "none";
        return;
    }

    // 🔵 CRÉDITO
    if (estado === "credito") {

        // ✅ MOSTRAR TODOS LOS MÉTODOS DE PAGO
        items.forEach(i => {
            i.classList.remove("d-none");
            i.classList.remove("active");
            if (i.dataset.value === "mixto") i.classList.add("d-none");
        });
        // ✅ efectivo activo por defecto
        activarEfectivoPorDefecto(items, hiddenMetodoPago);
                // ❌ NO forzar método
        

        // botón continuar
        if (btnIrStep3) {
            btnIrStep3.style.display = "";
            btnIrStep3.innerHTML = `Continuar <i class="fas fa-arrow-right ms-2"></i>`;
        }
        // 🔥 OCULTAR TEXTO "Vuelto" EN CRÉDITO
        if (labelVuelto) {
            labelVuelto.classList.add("d-none");
        }

        if (btnConfirmarDirecto) btnConfirmarDirecto.style.display = "none";
        return;
    }

    // 🟢 PAGADO (por defecto) -> todo visible

if (estado === "pagado") {

    // mostrar métodos
    items.forEach(i => i.classList.remove("d-none", "active"));

    // efectivo por defecto
    const efectivo = document.querySelector(
        '.metodo-pago-item[data-value="efectivo"]'
    );
    if (efectivo) {
        efectivo.classList.add("active");
        if (hiddenMetodoPago) hiddenMetodoPago.value = "efectivo";
    }

    // mostrar texto vuelto
    if (labelVuelto) {
        labelVuelto.classList.remove("d-none");
    }

    // 🔥 DECIDIR BOTÓN SEGÚN MÉTODO
    actualizarBotonesSegunMetodoPagado();
    return;
}

}

// ============================
// DOM
// ============================
document.addEventListener("DOMContentLoaded", () => {

    // Estado pago dinámica
    const estadoPagoSelect = document.getElementById("estado_pago");
    estadoPagoSelect?.addEventListener("change", manejarEstadoVenta);
    manejarEstadoVenta();

    // ============================
    // MÉTODOS DE PAGO - SELECCIÓN ÚNICA
    // ============================
    document.querySelectorAll(".metodo-pago-item").forEach(item => {
        item.addEventListener("click", () => {

            const hiddenMetodoPago = document.getElementById("metodo_pago");

            // 🔥 1. DESACTIVAR TODOS
            document.querySelectorAll(".metodo-pago-item")
                .forEach(i => i.classList.remove("active"));

            // 🔥 2. ACTIVAR SOLO EL CLICKEADO
            item.classList.add("active");

            // 🔥 3. GUARDAR VALOR REAL
            if (hiddenMetodoPago) {
                hiddenMetodoPago.value = item.dataset.value;
            }

            // 🔥 4. AJUSTAR BOTONES SEGÚN ESTADO + MÉTODO
            if (typeof actualizarBotonesSegunMetodoPagado === "function") {
                actualizarBotonesSegunMetodoPagado();
            }
        });
    });

    // Cliente/método pago -> volcar
    const documentoInput = document.getElementById("documento");
    const razonInput     = document.getElementById("razon_social");
    const direccionInput = document.getElementById("direccion");
    const hiddenMetodoPago = document.getElementById("metodo_pago");

    documentoInput?.addEventListener("input", () => volcarUIaVentaActiva());
    razonInput?.addEventListener("input", () => volcarUIaVentaActiva());
    direccionInput?.addEventListener("input", () => volcarUIaVentaActiva());

    document.querySelectorAll(".metodo-pago-item").forEach(item => {
        item.addEventListener("click", () => {
            document.querySelectorAll(".metodo-pago-item").forEach(i => i.classList.remove("active"));
            item.classList.add("active");
            if (hiddenMetodoPago) hiddenMetodoPago.value = item.dataset.value;
            volcarUIaVentaActiva();
        });
    });

    // Navegación steps
    const btnIrStep2 = document.getElementById("btn-ir-step2");
    const btnVolverStep1 = document.getElementById("btn-volver-step1") || document.getElementById("btn-volver-carrito");
    const btnIrStep3 = document.getElementById("btn-ir-step3");
    const btnVolverStep2 = document.getElementById("btn-volver-step2") || document.getElementById("btn-vuelto-atras");

    btnIrStep2?.addEventListener("click", async () => {
        const v = ventaActiva();
        if (!v.productos.length) return mostrarAlerta("Agrega al menos un producto antes de continuar.");
        // 🔥 VALIDAR STOCK ANTES DE CONTINUAR
        if (!validarStockVentaActiva()) {
            return; // 🚫 no avanzar
        }
        const descuentos = productosParaAutorizarDescuento(v);
        if (descuentos.length && !window.USUARIO_ES_ADMIN) {
            const firmaActual = firmaDescuentosVenta(v);
            const autorizacionVigente = v.descuento_autorizacion_token
                && v.descuento_autorizacion_firma === firmaActual;

            if (!autorizacionVigente && !await solicitarAutorizacionDescuentos(v)) {
                return;
            }
        } else if (!descuentos.length) {
            delete v.descuento_autorizacion_token;
            delete v.descuento_autorizacion_firma;
            delete v.descuento_autorizado_por_nombre;
        }

        showStep(2);
    });

    btnVolverStep1?.addEventListener("click", () => showStep(1));
    btnVolverStep2?.addEventListener("click", () => showStep(2));

    btnIrStep3?.addEventListener("click", (e) => {
        e.preventDefault();

        volcarUIaVentaActiva();

        const v = ventaActiva();
        const estado = (estadoPagoSelect?.value || "pagado").toLowerCase();

        const documento  = (v.cliente?.documento || "").trim();
        const razon      = (v.cliente?.razon || "").trim();
        const noGuardado = !!v.cliente?.no_guardado;
        const metodo     = (v.metodo_pago || "").trim();
        const comprobante = document.getElementById("tipo_comprobante")?.value || "boleta";
        const modoCliente = v.cliente_modo || "sin_documento";
        const tipoDoc = v.tipo_documento || "dni";

        if (comprobante === "factura" && (tipoDoc !== "ruc" || documento.length !== 11 || !razon)) {
            Swal.fire("RUC requerido", "La factura requiere seleccionar un cliente registrado con RUC de 11 dígitos.", "warning");
            return;
        }

        if (modoCliente === "con_documento" && (!documento || !razon)) {
            Swal.fire("Cliente requerido", "Consulta y selecciona un cliente antes de continuar.", "warning");
            return;
        }

        if (noGuardado) {
            Swal.fire("Cliente no guardado", "Debes guardar el cliente.", "warning");
            return;
        }

        if (!validarStockVentaActiva()) {
            return;
        }

        // pendiente -> fase 3 sin campos de pago ni vuelto
        if (estado === "pendiente") {
            prepararFase3Credito();
            showStep(3);
            return;
        }

        // crédito -> fase 3 con adelanto
        if (estado === "credito") {
            prepararFase3Credito();
            showStep(3);
            return;
        }

        // pagado -> requiere método
        if (!metodo) {
            Swal.fire("Método de pago", "Selecciona un método de pago.", "warning");
            return;
        }

        if (!validarStockVentaActiva()) {
            return;
        }

        prepararFase3();
        showStep(3);
    });

    // Vuelto / saldo
    const inputTotalVenta = document.getElementById("vuelto-total-venta");
    const inputPaga       = document.getElementById("vuelto-paga");
    const inputVuelto     = document.getElementById("vuelto-mostrar");

    inputPaga?.addEventListener("input", () => {
        const monto = parseFloat(inputPaga.value || 0);
        const total = parseFloat(inputTotalVenta?.value || 0);
        const estado = (estadoPagoSelect?.value || "pagado").toLowerCase();

        if (estado === "credito") {
            let saldo = total - monto;
            if (saldo < 0) saldo = 0;
            if (inputVuelto) inputVuelto.value = `Saldo pendiente: S/ ${formatPrecioDinamico(saldo)}`;
            return;
        }

        let vuelto = monto - total;
        if (vuelto < 0) vuelto = 0;
        if (inputVuelto) inputVuelto.value = `S/ ${formatPrecioDinamico(vuelto)}`;
    });

    // Inicial UI
    actualizarResumen();
    actualizarBotonCarrito();

});

// ============================
// EXPONER UI (OBLIGATORIO)
// ============================
window.showStep = showStep;
window.volcarUIaVentaActiva = volcarUIaVentaActiva;
window.restaurarVentaActivaEnUI = restaurarVentaActivaEnUI;
window.actualizarResumen = actualizarResumen;
window.actualizarBotonCarrito = actualizarBotonCarrito;
window.manejarEstadoVenta = manejarEstadoVenta;
window.prepararFase3 = prepararFase3;
window.prepararFase3Credito = prepararFase3Credito;
