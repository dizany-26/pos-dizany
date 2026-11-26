// ===============================
// BOOTSTRAP / INICIALIZACIÓN FINAL
// ===============================

document.addEventListener("DOMContentLoaded", () => {

    window.cargarBorradoresDelServidor?.();
    window.setInterval(() => {
        if (!document.hidden) window.cargarBorradoresDelServidor?.();
    }, 15000);

    // ============================
    // RENDER GENERAL
    // ============================
    function renderTodo() {

        posSaveDebounced(snapshotPOS, 10);

        if (typeof renderVentasEsperaPanel === "function") {
            renderVentasEsperaPanel();
        }

        if (typeof renderCarritoTreinta === "function") {
            renderCarritoTreinta();
        }

        if (typeof actualizarResumen === "function") {
            actualizarResumen();
        }

        if (typeof actualizarBotonCarrito === "function") {
            actualizarBotonCarrito();
        }

        if (
            window.PRODUCTOS_INICIALES &&
            Array.isArray(window.PRODUCTOS_INICIALES) &&
            typeof renderGrillaProductos === "function"
        ) {
            renderGrillaProductos(window.PRODUCTOS_INICIALES);
        }
    }

    // ============================
    // HEADER ACTIONS (DESKTOP + MOBILE)
    // ============================
    function reactivarHeaderActions(scope, mobilePanel) {

        // Ventas en espera (mobile)
        scope.querySelectorAll("#btn-pos-espera").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (!mobilePanel) return;

                if (typeof window.renderVentasEsperaPanel === "function") {
                    window.renderVentasEsperaPanel();
                }

                if (mobilePanel.classList.contains("d-none")) {
                    mobilePanel.classList.remove("d-none");
                    requestAnimationFrame(() =>
                        mobilePanel.classList.add("show")
                    );
                } else {
                    mobilePanel.classList.remove("show");
                    setTimeout(() =>
                        mobilePanel.classList.add("d-none"), 180
                    );
                }
            });
        });

        // Ordenar (modal)
        scope.querySelectorAll("#btn-ordenar").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                e.stopPropagation();

                const modal = document.getElementById("modalOrdenar");
                if (modal && window.bootstrap) {
                    bootstrap.Modal
                        .getOrCreateInstance(modal)
                        .show();
                }
            });
        });
    }

    // ============================
    // INICIALIZACIÓN POS
    // ============================
    asegurarVentaActiva();

    if (typeof window.restaurarVentaActivaEnUI === "function") {
        window.restaurarVentaActivaEnUI();
    }


    if (
        window.PRODUCTOS_INICIALES &&
        Array.isArray(window.PRODUCTOS_INICIALES)
    ) {
        renderGrillaProductos(window.PRODUCTOS_INICIALES);
    }

    renderTodo();

    // ============================
    // BEFORE UNLOAD
    // ============================
    window.addEventListener("beforeunload", () => {
        try {
            window.volcarUIaVentaActiva?.();
            posSave(snapshotPOS());
        } catch {}
    });

    // ============================
    // EXPONER
    // ============================
    window.renderTodo = renderTodo;
    window.reactivarHeaderActions = reactivarHeaderActions;

});
