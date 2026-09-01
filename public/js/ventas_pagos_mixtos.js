// Pagos divididos de una venta. Mantiene el flujo simple intacto y solo se
// activa cuando el cajero elige "Mixto".
(function () {
    const dinero = valor => `S/ ${Number(valor || 0).toFixed(2)}`;
    const numero = valor => {
        const parsed = Number.parseFloat(valor);
        return Number.isFinite(parsed) ? Math.round(parsed * 100) / 100 : 0;
    };

    function elementos() {
        return {
            wrap: document.getElementById("pago-mixto-wrap"),
            legacyPago: document.getElementById("step3-pago-wrap"),
            legacyResultado: document.getElementById("step3-resultado-wrap"),
            inputs: Array.from(document.querySelectorAll(".pago-mixto-monto")),
            efectivo: document.getElementById("pago-mixto-efectivo"),
            efectivoRecibido: document.getElementById("pago-mixto-efectivo-recibido"),
            efectivoRecibidoWrap: document.getElementById("pago-mixto-efectivo-recibido-wrap"),
            asignado: document.getElementById("pago-mixto-asignado"),
            diferencia: document.getElementById("pago-mixto-diferencia"),
            diferenciaLabel: document.getElementById("pago-mixto-diferencia-label"),
            vuelto: document.getElementById("pago-mixto-vuelto"),
            vueltoRow: document.getElementById("pago-mixto-vuelto-row"),
        };
    }

    function totalVenta() {
        return numero(typeof calcularTotal === "function" ? calcularTotal().total : 0);
    }

    function leerPagos() {
        const ui = elementos();
        const recibidoTexto = String(ui.efectivoRecibido?.value || "").trim();
        const recibido = recibidoTexto === "" ? null : numero(recibidoTexto);

        return ui.inputs.map(input => {
            const monto = numero(input.value);
            if (monto <= 0) return null;
            return {
                metodo_pago: input.dataset.metodo,
                monto,
                efectivo_recibido: input.dataset.metodo === "efectivo"
                    ? recibido
                    : null,
                efectivo_recibido_confirmado: input.dataset.metodo === "efectivo"
                    ? ui.efectivoRecibido?.dataset.confirmado === "1"
                    : false,
            };
        }).filter(Boolean);
    }

    function guardarEnVenta() {
        if (typeof ventaActiva !== "function") return;
        const venta = ventaActiva();
        if (!venta) return;
        venta.pagos = leerPagos();
        window.guardarPOSAhora?.();
    }

    function recalcular() {
        const ui = elementos();
        const total = totalVenta();
        const pagos = leerPagos();
        const asignado = Math.round(pagos.reduce((sum, pago) => sum + pago.monto, 0) * 100) / 100;
        const diferencia = Math.round((total - asignado) * 100) / 100;
        const parteEfectivo = numero(ui.efectivo?.value);
        const recibidoTexto = String(ui.efectivoRecibido?.value || "").trim();
        const recibido = recibidoTexto === "" ? null : numero(recibidoTexto);

        ui.efectivoRecibidoWrap?.classList.toggle("d-none", parteEfectivo <= 0);
        const vuelto = recibido === null
            ? 0
            : Math.max(0, Math.round((recibido - parteEfectivo) * 100) / 100);
        if (ui.asignado) ui.asignado.textContent = dinero(asignado);
        if (ui.diferencia) {
            ui.diferencia.textContent = dinero(Math.abs(diferencia));
            ui.diferencia.classList.toggle("text-success", Math.abs(diferencia) < 0.01);
            ui.diferencia.classList.toggle("text-danger", Math.abs(diferencia) >= 0.01);
        }
        if (ui.diferenciaLabel) {
            ui.diferenciaLabel.textContent = diferencia < 0 ? "Exceso asignado" : (diferencia > 0 ? "Falta distribuir" : "Distribución completa");
        }
        ui.vueltoRow?.classList.toggle("d-none", parteEfectivo <= 0);
        if (ui.vuelto) ui.vuelto.textContent = dinero(vuelto);
        guardarEnVenta();
    }

    window.prepararPagoMixto = function (total) {
        const ui = elementos();
        document.getElementById("vuelto-total-venta").value = Number(total || 0).toFixed(2);
        document.getElementById("step3-titulo").textContent = "Distribuye el pago de la venta";
        ui.legacyPago?.classList.add("d-none");
        ui.legacyResultado?.classList.add("d-none");
        ui.wrap?.classList.remove("d-none");
        document.getElementById("credito-vencimiento-wrap")?.classList.add("d-none");

        const guardados = typeof ventaActiva === "function" ? (ventaActiva().pagos || []) : [];
        ui.inputs.forEach(input => {
            const pago = guardados.find(item => item.metodo_pago === input.dataset.metodo);
            input.value = pago?.monto > 0 ? Number(pago.monto).toFixed(2) : "";
        });
        const pagoEfectivo = guardados.find(item => item.metodo_pago === "efectivo");
        if (ui.efectivoRecibido) {
            const confirmado = pagoEfectivo?.efectivo_recibido_confirmado === true;
            ui.efectivoRecibido.dataset.confirmado = confirmado ? "1" : "0";
            ui.efectivoRecibido.value = confirmado && pagoEfectivo?.efectivo_recibido > 0
                ? Number(pagoEfectivo.efectivo_recibido).toFixed(2)
                : "";
        }
        recalcular();
    };

    window.obtenerPagosMixtos = function () {
        const pagos = leerPagos();
        const total = totalVenta();
        const asignado = Math.round(pagos.reduce((sum, pago) => sum + pago.monto, 0) * 100) / 100;

        if (pagos.length < 2) {
            throw new Error("Selecciona por lo menos dos métodos para un pago mixto.");
        }
        if (Math.abs(asignado - total) > 0.009) {
            throw new Error(`La distribución debe completar exactamente ${dinero(total)}.`);
        }
        const efectivo = pagos.find(pago => pago.metodo_pago === "efectivo");
        if (efectivo && efectivo.efectivo_recibido === null) {
            throw new Error("Ingresa el efectivo recibido del cliente.");
        }
        if (efectivo && efectivo.efectivo_recibido < efectivo.monto) {
            throw new Error("El efectivo recibido no puede ser menor que la parte pagada en efectivo.");
        }
        return pagos;
    };

    document.addEventListener("DOMContentLoaded", () => {
        const ui = elementos();
        ui.inputs.forEach(input => input.addEventListener("input", recalcular));
        ui.efectivoRecibido?.addEventListener("input", () => {
            ui.efectivoRecibido.dataset.confirmado = "1";
            recalcular();
        });
    });
})();
