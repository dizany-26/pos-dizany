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

    const opEl   = document.getElementById("resumen-op-gravadas");
    const igvEl  = document.getElementById("resumen-igv-monto");
    const totEl  = document.getElementById("resumen-total");
    const igvPEl = document.getElementById("resumen-igv-porcentaje");

    if (opEl)   opEl.innerText   = "S/ " + subtotal.toFixed(2);
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

    if (metodo === "efectivo") {
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

    const estado = (estadoPagoSelect?.value || "pagado").toLowerCase();

    const labelVuelto = Array.from(
        document.querySelectorAll("label.form-label")
        ).find(l => l.textContent.trim().toLowerCase() === "vuelto");


    items.forEach(i => i.classList.remove("d-none", "active"));

    if (btnIrStep3) {
        btnIrStep3.style.display = "";
        btnIrStep3.innerHTML = `Continuar venta <i class="fas fa-arrow-right ms-2"></i>`;
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
        });
        // ✅ efectivo activo por defecto
        activarEfectivoPorDefecto(items, hiddenMetodoPago);
                // ❌ NO forzar método
        

        // botón continuar
        if (btnIrStep3) {
            btnIrStep3.style.display = "";
            btnIrStep3.innerHTML = `Continuar venta <i class="fas fa-arrow-right ms-2"></i>`;
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

    btnIrStep2?.addEventListener("click", () => {
        const v = ventaActiva();
        if (!v.productos.length) return mostrarAlerta("Agrega al menos un producto antes de continuar.");
        // 🔥 VALIDAR STOCK ANTES DE CONTINUAR
        if (!validarStockVentaActiva()) {
            return; // 🚫 no avanzar
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
