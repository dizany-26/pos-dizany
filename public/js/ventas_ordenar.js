// ===============================
// ORDENAR PRODUCTOS / MODAL
// ===============================

document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // MODAL ORDENAR
    // ============================
    const modalOrdenarEl = document.getElementById("modalOrdenar");
    const modalOrdenar =
        (window.bootstrap && modalOrdenarEl)
            ? new bootstrap.Modal(modalOrdenarEl)
            : null;

    const btnOrdenar = document.getElementById("btn-ordenar");
    let ordenSeleccionada = null;

    if (btnOrdenar && modalOrdenar) {
        btnOrdenar.addEventListener("click", () => modalOrdenar.show());
    }

    // ============================
    // BOTONES DE ORDEN
    // ============================
    const ordenBtns = document.querySelectorAll(".orden-btn");

    if (ordenBtns.length) {
        ordenBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                ordenBtns.forEach(b => b.classList.remove("active"));
                btn.classList.add("active");
                ordenSeleccionada = btn.dataset.type;
            });
        });
    }

    // ============================
    // ORDENAR PRODUCTOS
    // ============================
    function datoProducto(producto, campo) {
        const completo = (window.PRODUCTOS_SNAPSHOT || [])
            .find(p => Number(p.id) === Number(producto.id));
        return producto[campo] ?? completo?.[campo];
    }

    function valorOrden(producto, tipo) {
        switch (tipo) {
            case "az":
            case "za":
                return String(datoProducto(producto, "nombre") || "");
            case "precio_asc":
            case "precio_desc":
                return Number(producto.lotes_fifo?.[0]?.precio_unidad
                    ?? (window.PRODUCTOS_SNAPSHOT || []).find(p => Number(p.id) === Number(producto.id))?.lotes_fifo?.[0]?.precio_unidad
                    ?? producto.precio_venta ?? 0);
            case "stock_asc":
            case "stock_desc":
                return Number(datoProducto(producto, "stock") || 0);
            case "menos_vendidos":
            case "mas_vendidos":
                return Number(datoProducto(producto, "total_vendido_30d") || 0);
            case "fecha_asc":
            case "fecha_desc":
                return Date.parse(datoProducto(producto, "created_at") || "") || 0;
            default:
                return 0;
        }
    }

    function aplicarOrden(lista) {
        if (!ordenSeleccionada || !Array.isArray(lista)) return lista;
        const descendente = ["za", "precio_desc", "stock_desc", "mas_vendidos", "fecha_desc"]
            .includes(ordenSeleccionada);
        return [...lista].sort((a, b) => {
            const primero = valorOrden(a, ordenSeleccionada);
            const segundo = valorOrden(b, ordenSeleccionada);
            const diferencia = typeof primero === "string"
                ? primero.localeCompare(segundo, "es", { sensitivity: "base" })
                : primero - segundo;
            return descendente ? -diferencia : diferencia;
        });
    }

    function ordenarProductos(tipo) {
        ordenSeleccionada = tipo;
        renderGrillaProductos(window.obtenerListaVisiblePOS?.() || []);
    }

    window.aplicarOrdenProductosPOS = aplicarOrden;

    // ============================
    // LIMPIAR ORDEN
    // ============================
    const btnLimpiarOrden = document.getElementById("btn-limpiar-orden");

    if (btnLimpiarOrden) {
        btnLimpiarOrden.addEventListener("click", () => {

            ordenSeleccionada = null;

            document
                .querySelectorAll(".orden-btn")
                .forEach(b => b.classList.remove("active"));

            renderGrillaProductos(window.obtenerListaVisiblePOS?.() || []);

            if (modalOrdenar) modalOrdenar.hide();
        });
    }

    // ============================
    // APLICAR ORDEN
    // ============================
    const btnAplicarOrden = document.getElementById("btn-aplicar-orden");

    if (btnAplicarOrden) {
        btnAplicarOrden.addEventListener("click", () => {
            if (ordenSeleccionada) {
                ordenarProductos(ordenSeleccionada);
            }
            if (modalOrdenar) modalOrdenar.hide();
        });
    }

    // ============================
    // EXPONER
    // ============================
    window.ordenarProductos = ordenarProductos;

});
