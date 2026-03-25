// ===============================
// PRODUCTOS / GRILLA / BÚSQUEDA
// ===============================

document.addEventListener("DOMContentLoaded", () => {


    // ============================
    // FUNCIÓN DE FORMATO DINÁMICO (NUEVA)
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


    // ============================
    // ELEMENTOS
    // ============================
    const buscarInput   = document.getElementById("buscar_producto");
    const resultadosDiv = document.getElementById("resultados-busqueda");
    const esEscritorioPOS = window.matchMedia("(hover: hover) and (pointer: fine)").matches;

    function enfocarBuscadorPOS() {
        if (!buscarInput || !esEscritorioPOS) return;

        window.setTimeout(() => {
            buscarInput.focus();
            buscarInput.select?.();
        }, 150);
    }

    function normalizarTexto(valor) {
        return String(valor || "")
            .trim()
            .toLowerCase();
    }

    function normalizarCodigo(valor) {
        return String(valor || "")
            .replace(/[^0-9A-Za-z]/g, "")
            .trim();
    }

    // ============================
    // CACHE DE PRODUCTOS
    // ============================
    window.productosCache = new Map(); // id => producto

    function cacheProductos(lista) {
        if (!Array.isArray(lista)) return;
        lista.forEach(p => {
            if (p && p.id != null) productosCache.set(Number(p.id), p);
        });
    }

    // ============================
    // CARD PRODUCTO
    // ============================
    function crearCardProducto(prod) {

    let nombreImagen = String(prod.imagen || "").trim();
    if (
        nombreImagen.includes("<") ||
        nombreImagen.includes(">") ||
        nombreImagen.includes("=") ||
        nombreImagen.includes('"') ||
        nombreImagen.includes("'")
    ) {
        nombreImagen = "";
    }

    const imgSrc = nombreImagen
        ? `/uploads/productos/${nombreImagen}`
        : "/img/sin-imagen.png";

    // 🔥 STOCK VIENE DEL BACKEND (SUMA DE LOTES)
    const disponible = Number(prod.stock || 0);

    const stockText = disponible > 0
        ? `${disponible} disponibles`
        : "Sin stock";

    // 🔥 PRECIO VIENE DEL BACKEND (LOTE FEFO)
    let precioBase = 0;

    if (Array.isArray(prod.lotes_fifo) && prod.lotes_fifo.length > 0) {
        precioBase = parseFloat(prod.lotes_fifo[0].precio_unidad || 0);
    }

    const precioFinal = calcularPrecioFinal(precioBase);

    // 👇 AHORA USA LA FUNCIÓN DINÁMICA
    const precioFormateado = formatPrecioDinamico(precioFinal);

    // 🔥 TEXTO DE PRECIO PARA LA GRILLA
    const precioLabel = disponible > 0
        ? `S/ ${precioFormateado}`
        : "Sin stock";

    const v = ventaActiva();
    const enCarrito = (v.productos || [])
        .some(it => Number(it.id) === Number(prod.id));

    return `
        <div class="col-6 col-md-4 col-xl-3 mb-3">
            <div class="product-card agregar-carrito
                ${disponible <= 0 ? "agotado" : ""}
                ${enCarrito ? "en-carrito" : ""}"
                data-id="${prod.id}">

                <div class="product-img-wrapper">
                    <img src="${imgSrc}" alt="${prod.nombre}" class="product-img">
                    <span class="product-price-badge">
                        ${precioLabel}
                    </span>
                </div>

                <div class="product-info">
                    ${IGV_PERCENT > 0
                        ? `<small class="text-success fw-bold d-block" style="font-size:12px;">Incl. IGV</small>`
                        : ""
                    }

                    <div class="product-name" title="${prod.nombre}">
                        ${prod.nombre}
                    </div>

                    <div class="product-desc" title="${prod.descripcion || ""}">
                        ${cortar(prod.descripcion, 35) || "&nbsp;"}
                    </div>

                    <div class="product-stock ${disponible > 0 ? "stock-ok" : "stock-low"}">
                        <i class="fas fa-box-open"></i> ${stockText}
                    </div>
                </div>
            </div>
        </div>
    `;
}


    // ============================
    // RENDER GRILLA
    // ============================
    function renderGrillaProductos(lista) {
        if (!resultadosDiv) return;

        resultadosDiv.classList.remove("d-none");
        resultadosDiv.innerHTML = "";

        // tarjeta crear producto (admin)
        if (window.USUARIO_ES_ADMIN) {
            resultadosDiv.insertAdjacentHTML("beforeend", `
                <div class="col-6 col-md-4 col-xl-3 mb-3">
                    <div class="product-card crear-producto-card">
                        <div class="crear-producto-center">
                            <div class="crear-producto-icon">+</div>
                            <span>Crear producto</span>
                        </div>
                    </div>
                </div>
            `);

            const cardCrear = resultadosDiv.querySelector(".crear-producto-card");
            if (cardCrear) {
                cardCrear.classList.add("show");
                cardCrear.addEventListener("click", () =>
                    window.location.href = "/productos/create"
                );
            }
        }

        if (!lista || lista.length === 0) {
            resultadosDiv.insertAdjacentHTML("beforeend", `
                <div class="col-12 text-center text-muted py-3">
                    No se encontraron productos
                </div>
            `);
            return;
        }

        cacheProductos(lista);

        lista.forEach((prod, idx) => {
            resultadosDiv.insertAdjacentHTML("beforeend", crearCardProducto(prod));
            const col = resultadosDiv.lastElementChild;
            const card = col?.querySelector(".product-card");
            if (card) {
                card.style.transitionDelay = (idx * 0.02) + "s";
                requestAnimationFrame(() => card.classList.add("show"));
            }
        });

        // ============================
        // CLICK AGREGAR AL CARRITO
        // ============================
        resultadosDiv
            .querySelectorAll(".product-card.agregar-carrito")
            .forEach(card => {

                card.addEventListener("click", () => {
                    const id = Number(card.dataset.id);
                    const prod = productosCache.get(id);

                    if (!prod) {
                        return mostrarAlerta(
                            "No se pudo obtener la información del producto."
                        );
                    }

                    if (Number(prod.stock || 0) <= 0) {
                        return mostrarAlerta(
                            `No hay stock para "${prod.nombre}".`
                        );
                    }

                    const v = ventaActiva();
                    if (v.productos.some(it => Number(it.id) === id)) {
                        return mostrarAlerta(
                            `El producto "${prod.nombre}" ya está en la canasta.`
                        );
                    }

                    agregarProductoAVentaActiva(prod);
                });
            });
    }

    async function buscarProductosApi(searchTerm) {
        const response = await fetch(`/buscar-producto?search=${encodeURIComponent(searchTerm)}`);
        if (!response.ok) {
            throw new Error("Error al buscar productos");
        }

        const list = await response.json();
        cacheProductos(list);
        return list;
    }

    async function agregarProductoDesdeBusqueda(prod) {
        if (!prod) {
            return { added: false, reason: "missing" };
        }

        if (Number(prod.stock || 0) <= 0) {
            mostrarAlerta(`No hay stock para "${prod.nombre}".`);
            enfocarBuscadorPOS();
            return { added: false, reason: "out_of_stock" };
        }

        const v = ventaActiva();
        if ((v.productos || []).some(it => Number(it.id) === Number(prod.id))) {
            mostrarAlerta(`El producto "${prod.nombre}" ya está en la canasta.`);
            enfocarBuscadorPOS();
            return { added: false, reason: "duplicate" };
        }

        await agregarProductoAVentaActiva(prod);

        const carritoLista = document.getElementById("carrito-lista");
        carritoLista?.scrollTo({ top: 0, behavior: "smooth" });
        buscarInput.value = "";
        enfocarBuscadorPOS();
        renderGrillaProductos(window.PRODUCTOS_SNAPSHOT || []);
        return { added: true, reason: "added", product: prod };
    }

    async function resolverBusquedaPOS(searchTerm, options = {}) {
        const { render = true } = options;
        const term = String(searchTerm || "").trim();

        if (!term) {
            if (render) {
                renderGrillaProductos(window.PRODUCTOS_SNAPSHOT || []);
            }
            enfocarBuscadorPOS();
            return { added: false, reason: "empty" };
        }

        const list = await buscarProductosApi(term);

        if (render) {
            renderGrillaProductos(list);
        }

        if (!list.length) {
            mostrarAlerta("No se encontraron productos");
            enfocarBuscadorPOS();
            return { added: false, reason: "not_found" };
        }

        const codigoNormalizado = normalizarCodigo(term);
        const nombreNormalizado = normalizarTexto(term);

        const coincidenciaExacta = list.find(prod =>
            normalizarCodigo(prod.codigo_barras) === codigoNormalizado ||
            normalizarTexto(prod.nombre) === nombreNormalizado
        );

        const candidato = coincidenciaExacta || (list.length === 1 ? list[0] : null);

        if (!candidato) {
            enfocarBuscadorPOS();
            return { added: false, reason: "multiple_matches", results: list };
        }

        return agregarProductoDesdeBusqueda(candidato);
    }

    // ============================
    // ACTUALIZAR STOCK DESDE BACKEND
    // ============================
    function actualizarProductosStock() {
        fetch("/productos/iniciales")
            .then(res => res.json())
            .then(lista => {
                if (Array.isArray(lista)) {
                    window.PRODUCTOS_INICIALES = lista;
                    window.PRODUCTOS_SNAPSHOT = [...lista]; // 🔥 CLAVE
                    renderGrillaProductos(lista);
                }
            });
    }

    // ============================
    // FILTRO CATEGORÍAS
    // ============================
    const botonesCategorias =
        document.querySelectorAll(".btn-filtro-categoria");

    if (botonesCategorias.length) {
        botonesCategorias.forEach(btn => {
            btn.addEventListener("click", () => {

                botonesCategorias.forEach(b =>
                    b.classList.remove("active")
                );
                btn.classList.add("active");

                const catID = Number(btn.dataset.cat);

                if (
                    !window.PRODUCTOS_INICIALES ||
                    !Array.isArray(window.PRODUCTOS_INICIALES)
                ) return;

                if (catID === 0) {
                    return renderGrillaProductos(window.PRODUCTOS_SNAPSHOT);
                }

                const filtrados =
                    window.PRODUCTOS_SNAPSHOT.filter(
                        p => Number(p.categoria_id) === catID
                    );

                renderGrillaProductos(filtrados);
            });
        });
    }

    // ============================
    // BUSCAR PRODUCTO (AJAX)
    // ============================
    if (buscarInput) {
        buscarInput.addEventListener("input", () => {

            const q = buscarInput.value.trim();

            if (!q) {
                renderGrillaProductos(window.PRODUCTOS_SNAPSHOT || []);
                return;
            }

            buscarProductosApi(q)
                .then(list => renderGrillaProductos(list))
                .catch(() =>
                    mostrarAlerta("Error al buscar productos")
                );
        });

        buscarInput.addEventListener("keydown", async (event) => {
            if (event.key !== "Enter") return;

            event.preventDefault();

            try {
                await resolverBusquedaPOS(buscarInput.value, { render: true });
            } catch (error) {
                console.error(error);
                mostrarAlerta("Error al buscar productos");
            }
        });
    }

    // ============================
    // INICIAL
    // ============================
    actualizarProductosStock();
    enfocarBuscadorPOS();

    // ============================
    // EXPONER
    // ============================
    window.renderGrillaProductos = renderGrillaProductos;
    window.actualizarProductosStock = actualizarProductosStock;
    window.buscarProductosPOS = buscarProductosApi;
    window.posResolverYAgregarProducto = resolverBusquedaPOS;
    window.posAgregarProductoDesdeBusqueda = agregarProductoDesdeBusqueda;

});
