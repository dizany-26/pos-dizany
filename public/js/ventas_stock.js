// ===============================
// STOCK Y UNIDADES
// ===============================
function unidadesRealesDeItem(item) {
    const cantidad = parseInt(item.cantidad) || 0;
    if (cantidad <= 0) return 0;

    if (item.tipo_venta === "unidad") return cantidad;

    if (item.tipo_venta === "paquete") {
        return cantidad * (parseInt(item.unidades_por_paquete) || 0);
    }

    if (item.tipo_venta === "caja") {
        const unidadesCaja = parseInt(item.unidades_por_caja) || 0;
        if (unidadesCaja > 0) return cantidad * unidadesCaja;

        const pxc = parseInt(item.paquetes_por_caja) || 0;
        const upp = parseInt(item.unidades_por_paquete) || 0;
        if (pxc > 0 && upp > 0) return cantidad * pxc * upp;
        if (upp > 0) return cantidad * upp;
    }

    return 0;
}

function stockRealProducto(it) {
    const prod = productosCache.get(Number(it.producto_id || it.id));
    return prod
        ? (parseInt(prod.stock) || 0)
        : (parseInt(it.stock_lote || it.stock) || 0);
}

function stockDisponible(prod) {
    return parseInt(prod.stock) || 0;
}
