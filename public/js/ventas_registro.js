// ===============================
// REGISTRO DE VENTA / BACKEND
// ===============================

document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // ELEMENTOS
    // ============================
    const tipoComprobanteSelect = document.getElementById("tipo_comprobante");
    const estadoPagoSelect      = document.getElementById("estado_pago");
    const formatoSelect         = document.getElementById("formato_pdf");

    const inputPaga  = document.getElementById("vuelto-paga");
    const inputTotal = document.getElementById("vuelto-total-venta");

    const btnConfirmar3 = document.getElementById("btn-confirmar-venta");
    const btnConfirmarDirecto =
        document.getElementById("btn-confirmar-venta-directo");

    const btnImprimir  = document.getElementById("btnImprimir");
    const btnDescargar = document.getElementById("btn-descargar");
    const btnNuevaVenta = document.getElementById("btnNuevaVenta");

    const modalVentaExitosaElement =
        document.getElementById("modalVentaExitosa");

    let modalVentaExitosa = null;
    let ventaEnProceso = false;
    let ventaPendienteDeReinicio = false;

    if (modalVentaExitosaElement && window.bootstrap) {
        modalVentaExitosa =
            bootstrap.Modal.getOrCreateInstance(modalVentaExitosaElement);
    }

    function mostrarProcesandoVenta(procesando) {
        ventaEnProceso = procesando;

        [btnConfirmar3, btnConfirmarDirecto].forEach(button => {
            if (button) button.disabled = procesando;
        });

        if (procesando) {
            Swal.fire({
                title: "Procesando venta",
                html: "Registrando productos, actualizando stock y generando el comprobante...",
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });
        } else if (Swal.isVisible()) {
            Swal.close();
        }
    }

    // ============================
    // BOTONES COMPROBANTE
    // ============================
    function configurarBotonesComprobante(data) {

        if (btnImprimir && data.pdf_url) {
            btnImprimir.href = data.pdf_url;
            btnImprimir.target = "_blank";
        }

        if (btnDescargar) {
            if (data.nombre_archivo) {
                btnDescargar.href =
                    `/storage/comprobantes/${data.nombre_archivo}`;
                btnDescargar.download = data.nombre_archivo;
            } else if (data.pdf_url) {
                btnDescargar.href = data.pdf_url;
                btnDescargar.download = "";
            }
        }
    }

    // ============================
    // REGISTRAR VENTA
    // ============================
    function registrarVenta() {

        if (ventaEnProceso) return;

        if (typeof window.volcarUIaVentaActiva === "function") {
            window.volcarUIaVentaActiva();
        }

        const v = ventaActiva();

        const { total } = calcularTotal();

        if (!v.productos.length) {
            return mostrarAlerta("No hay productos en la venta.");
        }

        const tipoComprobante =
            tipoComprobanteSelect?.value || "boleta";

        const documento = v.cliente?.documento || "";
        const clienteModo = v.cliente_modo || (documento ? "con_documento" : "sin_documento");
        const tipoDocumento = v.tipo_documento || (documento.length === 11 ? "ruc" : "dni");
        const informacionAdicional = (v.informacion_adicional || "").trim();
        const ahora = new Date();
        const fecha = `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, "0")}-${String(ahora.getDate()).padStart(2, "0")}`;
        const hora  = document.getElementById("hora_actual")?.value;

        const estadoPago =
            estadoPagoSelect?.value || "pagado";

        const metodoPago = v.metodo_pago || "";
        const formato = formatoSelect?.value || "a4";

        if (tipoComprobante === "factura" && (tipoDocumento !== "ruc" || documento.length !== 11 || !v.cliente?.id)) {
            return mostrarAlerta("La factura requiere seleccionar un cliente registrado con RUC de 11 dígitos.");
        }

        if (clienteModo === "con_documento" && (!documento || !v.cliente?.id)) {
            return mostrarAlerta("Consulta y guarda el cliente antes de confirmar la venta.");
        }

        // ============================
        // MONTO PAGADO
        // ============================
        let montoPagado = 0;
        let pagos = [];

        if (estadoPago === "pagado") {
            if (metodoPago === "mixto") {
                try {
                    pagos = window.obtenerPagosMixtos?.() || [];
                } catch (error) {
                    return mostrarAlerta(error.message);
                }
                montoPagado = pagos.reduce((sum, pago) => sum + Number(pago.monto || 0), 0);
            } else if (metodoPago === "efectivo") {
                const efectivoTexto = String(inputPaga?.value || "").trim();
                const efectivoRecibido = efectivoTexto === ""
                    ? total
                    : parseFloat(efectivoTexto);
                if (!Number.isFinite(efectivoRecibido) || efectivoRecibido < total) {
                    return mostrarAlerta(
                        "El efectivo recibido no puede ser menor al total de la venta."
                    );
                }
                montoPagado = total;
                pagos = [{ metodo_pago: "efectivo", monto: total, efectivo_recibido: efectivoRecibido }];
            } else {
                montoPagado = total;
                pagos = [{ metodo_pago: metodoPago, monto: total, efectivo_recibido: null }];
            }

            if (metodoPago === "efectivo" && pagos[0].efectivo_recibido < total) {
                return mostrarAlerta(
                    "El efectivo recibido no puede ser menor al total de la venta."
                );
            }
        } else if (estadoPago === "credito") {
            montoPagado = parseFloat(inputPaga?.value || 0);
            if (montoPagado > 0) {
                pagos = [{
                    metodo_pago: metodoPago,
                    monto: Math.min(montoPagado, total),
                    efectivo_recibido: metodoPago === "efectivo" ? montoPagado : null,
                }];
            }
        }
        // pendiente => 0

        if (montoPagado > 0 && !metodoPago) {
            return mostrarAlerta(
                "Debes seleccionar un método de pago."
            );
        }

        const productosEnviar = v.productos.map(it => {
            if (!it.lote_id) {
                throw new Error(`El producto ${it.nombre} no tiene lote asignado`);
            }

            const factor =
                it.tipo_venta === "paquete"
                    ? it.unidades_por_paquete
                    : it.tipo_venta === "caja"
                        ? (
                            it.unidades_por_caja ||
                            it.unidades_por_paquete * it.paquetes_por_caja
                        )
                        : 1;

            const cantidad = parseInt(it.cantidad);
            if (!cantidad || cantidad <= 0) {
                throw new Error(`Cantidad inválida para ${it.nombre}`);
            }

            return {
                producto_id: it.producto_id ?? it.id,
                lote_id: it.lote_id,
                cantidad: cantidad,
                unidades: cantidad * factor,
                presentacion: it.tipo_venta
            };
        });

        mostrarProcesandoVenta(true);

        fetch("/ventas/registrar", {    
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN":
            document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        tipo_comprobante: tipoComprobante,
        cliente_id: v.cliente?.id || null,
        documento: documento,
        cliente_modo: clienteModo,
        tipo_documento: tipoDocumento,
        informacion_adicional: informacionAdicional,
        fecha: fecha,
        hora: hora,
        monto_pagado: montoPagado,
        efectivo_recibido: pagos.find(pago => pago.metodo_pago === "efectivo")?.efectivo_recibido ?? null,
        metodo_pago: metodoPago,
        estado_pago: estadoPago,
        pagos: pagos,
        productos: productosEnviar,
        formato: formato,
        credit_due_date: ["credito", "pendiente"].includes(estadoPago) ? document.getElementById("credito-vencimiento")?.value : null
    })
})
.then(async res => {

    const data = await res.json();

    // 🔥 SI EL BACKEND DEVUELVE 422 U OTRO ERROR
    if (!res.ok) {
        throw data;
    }

    return data;
})
.then(data => {

    if (!data.success) {
        throw new Error(data.message || "Error al registrar venta.");
    }

    mostrarProcesandoVenta(false);
    configurarBotonesComprobante(data);

    // 🔥 LIMPIEZA CORRECTA POS
    const idConfirmada = POS.ventaActivaId;
    delete POS.ventas[idConfirmada];
    asegurarVentaActiva();

    guardarPOSAhora();

    if (typeof snapshotPOS === "function") {
        posSaveDebounced(snapshotPOS, 0);
    }

    actualizarProductosStock();

    ventaPendienteDeReinicio = true;
    if (modalVentaExitosa) {
        modalVentaExitosa.show();
    } else {
        reiniciarDespuesDeVenta();
    }

    try {
        const sonidoExito = new Audio("/sonidos/success.mp3");
        sonidoExito.play().catch(() => {});
    } catch {}

    renderTodo();
})
.catch(error => {

  mostrarProcesandoVenta(false);

  // 👇 si vino como string o Error normal, normalizamos
  const type = error?.type;
  const validationMessage = error?.errors
      ? Object.values(error.errors).flat().find(Boolean)
      : null;
  const msg = validationMessage || error?.message || "No se pudo registrar la venta. Inténtalo nuevamente.";

 if (type === "stock") {
    Swal.fire({
        icon: "warning",
        title: "Stock insuficiente",
        html: `
            <b>${error.producto_nombre}</b><br>
            Lote: <b>${error.lote ?? '-'}</b><br>
            Disponible: <b>${error.disponible}</b><br>
            Solicitado: ${error.solicitado}
        `,

        confirmButtonText: "OK",
        confirmButtonColor: "#d33",
        allowOutsideClick: false,
        allowEscapeKey: false
        
    }).then(() => {

        // 🔄 refrescar productos visualmente
        if (typeof actualizarProductosStock === "function") {
            actualizarProductosStock();
        }

        // 🔄 volver a renderizar carrito
        if (typeof renderCarritoTreinta === "function") {
            renderCarritoTreinta();
        }

        // 🎯 foco en buscador
        document.getElementById("buscar_producto")?.focus();

    });

    return;
}

  if (type === "client_not_found") {
    Swal.fire({
        icon: "warning",
        title: "Revisa el cliente",
        text: msg,
        confirmButtonText: "Volver y revisar",
        confirmButtonColor: "#2563eb"
    }).then(() => {
        if (typeof showStep === "function") showStep(2);
        document.getElementById("documento")?.focus();
    });

    return;
  }

  if (validationMessage) {
    Swal.fire({
        icon: "warning",
        title: "Completa los datos requeridos",
        text: msg,
        confirmButtonText: "Revisar venta",
        confirmButtonColor: "#2563eb"
    });

    return;
  }

  const alertasVenta = {
    product_not_found: {
        icon: "warning",
        title: "Producto no disponible",
        confirmButtonText: "Actualizar productos"
    },
    business_rule: {
        icon: "warning",
        title: "Revisa los datos de la venta",
        confirmButtonText: "Corregir"
    },
    server_error: {
        icon: "error",
        title: "No se pudo registrar la venta",
        confirmButtonText: "Entendido"
    }
  };

  if (alertasVenta[type]) {
    const alerta = alertasVenta[type];
    Swal.fire({
        icon: alerta.icon,
        title: alerta.title,
        text: msg,
        confirmButtonText: alerta.confirmButtonText,
        confirmButtonColor: type === "server_error" ? "#dc3545" : "#2563eb"
    }).then(() => {
        if (type === "product_not_found" && typeof actualizarProductosStock === "function") {
            actualizarProductosStock();
        }
    });

    return;
  }


  // otros errores
  mostrarAlerta(msg);
});


}
    // ============================
    // CONTINUAR VENDIENDO
    // ============================
   function continuarVendiendo() {

    if (modalVentaExitosa) {
        modalVentaExitosa.hide();
    } else {
        reiniciarDespuesDeVenta();
    }
}

function reiniciarDespuesDeVenta() {
    if (!ventaPendienteDeReinicio) return;
    ventaPendienteDeReinicio = false;

    const id = POS.ventaActivaId || uidVenta();
    POS.ventas[id] = crearVentaVacia(id);
    POS.ventaActivaId = id;
    POS.ventas[id].metodo_pago = "efectivo";
    POS.ventas[id].fase = 1;
    window.cerrarCarritoMovil?.();

    // Guardar estado real
    if (typeof snapshotPOS === "function") {
        posSaveDebounced(snapshotPOS, 0);
    }

    // Restaurar UI
    if (typeof restaurarVentaActivaEnUI === "function") {
        restaurarVentaActivaEnUI();
    }

    // 🔥 LIMPIAR ESTADO VISUAL DEL RUC
    const estadoRuc = document.getElementById("estado_ruc");
    if (estadoRuc) {
        estadoRuc.textContent = "";
        estadoRuc.classList.remove("text-success", "text-danger");
    }

    // refrescar correlativo
    if (tipoComprobanteSelect) {
        tipoComprobanteSelect.dispatchEvent(new Event("change"));
    }

    renderTodo();

    // 🔥 foco para vender rápido (ENTER)
    setTimeout(() => {
        document.getElementById("buscar_producto")?.focus();
    }, 150);
}


    // ============================
    // EVENTOS
    // ============================
    btnConfirmar3?.addEventListener("click", registrarVenta);
    btnConfirmarDirecto?.addEventListener("click", registrarVenta);
    btnNuevaVenta?.addEventListener("click", continuarVendiendo);
    modalVentaExitosaElement?.addEventListener(
        "hidden.bs.modal",
        reiniciarDespuesDeVenta
    );

    // ============================
    // EXPONER
    // ============================
    window.registrarVenta = registrarVenta;
    window.continuarVendiendo = continuarVendiendo;

});
