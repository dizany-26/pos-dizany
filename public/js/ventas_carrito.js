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


// ============================
// TOTALES / CÁLCULOS GLOBALES
// ============================

function calcularSubtotal() {
    const v = ventaActiva();
    return (v.productos || []).reduce(
        (s, it) =>
            s +
            (parseFloat(it.precio_unitario || 0) *
             (parseInt(it.cantidad) || 0)),
        0
    );
}

function obtenerIGVPercent() {
    const el = document.getElementById("igv-config");
    const val = el ? parseFloat(el.value) : 0;
    return isNaN(val) ? 0 : val;
}

function calcularTotal() {
    const subtotal = calcularSubtotal();
    const igvPercent = obtenerIGVPercent();
    const igv = subtotal * igvPercent / 100;

    return {
        subtotal,
        igv,
        total: subtotal + igv,
        igvPercent
    };
}


function getProdId(it) {
  return Number(it.producto_id || it.id);
}

function recolectarGrupoFIFO(items, indexBase) {
  const base = items[indexBase];
  if (!base) return null;

  const pid = getProdId(base);
  const tipo = base.tipo_venta;

  const idxs = [];
  let total = 0;

  for (let k = 0; k < items.length; k++) {
    const it = items[k];
    if (getProdId(it) === pid && it.tipo_venta === tipo) {
      idxs.push(k);
      total += (parseInt(it.cantidad) || 0);
    }
  }

  return { pid, tipo, idxs, total };
}

function factorPresentacion(it) {
    if (it.tipo_venta === "paquete") {
        return parseInt(it.unidades_por_paquete) || 0;
    }
    if (it.tipo_venta === "caja") {
        const uc = parseInt(it.unidades_por_caja) || 0;
        if (uc > 0) return uc;

        const up = parseInt(it.unidades_por_paquete) || 0;
        const pc = parseInt(it.paquetes_por_caja) || 0;
        return up * pc;
    }
    return 1; // unidad
}


// 👉 EXPONER TOTALES
window.calcularTotal = calcularTotal;
// ===============================
// CARRITO / ITEMS / CANTIDADES
// ===============================

document.addEventListener("DOMContentLoaded", () => {

    // ============================
    // ESTILOS INPUT CANTIDAD (SIN FLECHAS)
    // ============================
    (function injectInputCantidadStyles() {
        if (document.getElementById("input-cantidad-style")) return;

        const st = document.createElement("style");
        st.id = "input-cantidad-style";
        st.innerHTML = `
            /* Quitar flechas de input number */
            input[type=number]::-webkit-inner-spin-button,
            input[type=number]::-webkit-outer-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            input[type=number] {
                -moz-appearance: textfield;
            }
        `;
        document.head.appendChild(st);
    })();


    // ============================
    // ELEMENTOS
    // ============================
    const carritoLista = document.getElementById("carrito-lista");
    const buscarInput  = document.getElementById("buscar_producto");
    const btnIrStep2   = document.getElementById("btn-ir-step2");

    async function descomponerFIFO(producto, cantidadPresentaciones, tipoVenta) {
        const res = await fetch(`/ventas/stock-fifo/${producto.id}`);
        const lotes = await res.json();

        if (!res.ok || !Array.isArray(lotes) || !lotes.length) {
            throw new Error("No hay stock disponible");
        }

        let factor = 1;
        if (tipoVenta === "paquete") {
            factor = parseInt(producto.unidades_por_paquete) || 0;
        } else if (tipoVenta === "caja") {
            factor = parseInt(producto.unidades_por_caja) || 0;
            if (factor <= 0) {
                const up = parseInt(producto.unidades_por_paquete) || 0;
                const pc = parseInt(producto.paquetes_por_caja) || 0;
                factor = up * pc;
            }
        }

        if (factor <= 0) {
            throw new Error("Presentación inválida. Revisa la configuración del producto.");
        }

        const cantidad = parseInt(cantidadPresentaciones) || 0;
        let unidadesRestantes = cantidad * factor;
        let subtotal = 0;
        const tramosPrecio = [];

        for (const lote of lotes) {
            if (unidadesRestantes <= 0) break;

            const stockUnidades = parseInt(lote.stock || 0);
            if (stockUnidades <= 0) continue;

            const unidadesTomadas = Math.min(stockUnidades, unidadesRestantes);
            const precioPresentacion = parseFloat(
                tipoVenta === "paquete"
                    ? lote.precio_paquete
                    : tipoVenta === "caja"
                        ? lote.precio_caja
                        : lote.precio_unidad
            ) || 0;

            if (precioPresentacion <= 0) {
                throw new Error(`El lote ${lote.numero_lote || lote.id} no tiene precio para ${tipoVenta}.`);
            }

            const subtotalTramo = Math.round(
                unidadesTomadas * (precioPresentacion / factor) * 100
            ) / 100;

            subtotal += subtotalTramo;
            tramosPrecio.push({
                lote_id: Number(lote.id),
                numero_lote: lote.numero_lote || lote.id,
                unidades: unidadesTomadas,
                precio_presentacion: precioPresentacion,
                subtotal: subtotalTramo
            });
            unidadesRestantes -= unidadesTomadas;
        }

        if (unidadesRestantes > 0) {
            throw new Error("Stock insuficiente");
        }

        const primerLote = lotes[0];

        return [{
            id: Number(producto.id),
            producto_id: Number(producto.id),
            lote_id: Number(primerLote.id),
            nombre: producto.nombre,
            imagen: producto.imagen,
            descripcion: producto.descripcion,
            tipo_venta: tipoVenta,
            cantidad,
            precio_unitario: subtotal / cantidad,
            precio_venta: parseFloat(primerLote.precio_unidad || 0),
            precio_paquete: parseFloat(primerLote.precio_paquete || 0),
            precio_caja: parseFloat(primerLote.precio_caja || 0),
            stock_lote: lotes.reduce(
                (total, lote) => total + (parseInt(lote.stock) || 0),
                0
            ),
            unidades_por_paquete: producto.unidades_por_paquete || 0,
            paquetes_por_caja: producto.paquetes_por_caja || 0,
            unidades_por_caja: producto.unidades_por_caja || 0,
            tramos_precio: tramosPrecio
        }];
    }
    // ============================
    // AGREGAR PRODUCTO A VENTA ACTIVA
    // ============================
    async function agregarProductoAVentaActiva(producto) {

    const v = ventaActiva();

    // ✅ pedir lotes FIFO (con precios por presentación)
    const res = await fetch(`/ventas/stock-fifo/${producto.id}`);
    const lotes = await res.json();

    const loteFIFO = Array.isArray(lotes) ? lotes[0] : null;
    if (!loteFIFO) {
        return mostrarAlerta(`No hay lotes con stock para "${producto.nombre}".`);
    }

    const item = {
        // 🔹 identificación correcta
        id: Number(producto.id),
        producto_id: Number(producto.id),   // 🔥 CLAVE para agrupar
        lote_id: Number(loteFIFO.id),        // 🔥 FIFO

        nombre: producto.nombre,
        imagen: producto.imagen || "",
        descripcion: producto.descripcion || "",

        // 🔥 stock del LOTE (no del producto)
        stock_lote: parseInt(loteFIFO.stock || 0),

        cantidad: 1,
        tipo_venta: "unidad",

        // ✅ precios vienen del LOTE (FIFO)
        precio_venta: parseFloat(loteFIFO.precio_unidad || 0),
        precio_paquete: parseFloat(loteFIFO.precio_paquete || 0),
        precio_caja: parseFloat(loteFIFO.precio_caja || 0),

        // ✅ precio unitario inicial
        precio_unitario: parseFloat(loteFIFO.precio_unidad || 0),

        // presentaciones (del producto)
        unidades_por_paquete: producto.unidades_por_paquete
            ? parseInt(producto.unidades_por_paquete)
            : 0,

        paquetes_por_caja: producto.paquetes_por_caja
            ? parseInt(producto.paquetes_por_caja)
            : 0,
        unidades_por_caja: producto.unidades_por_caja
            ? parseInt(producto.unidades_por_caja)
            : 0
    };


    // Validación stock (tu lógica actual)
    const prodActual = productosCache.get(item.id) || producto;
    if (stockDisponible(prodActual) < unidadesRealesDeItem(item)) {
        return mostrarAlerta("No hay stock suficiente.");
    }

    v.productos.push(item);

    posSaveDebounced(snapshotPOS, 10);
    actualizarContadorVentasEspera();
    renderCarritoTreinta();
}

    // ============================
    // COLOR BADGE STOCK
    // ============================
    function getStockBadgeColor(stock) {
        if (stock >= 20) return "bg-success";
        if (stock >= 6) return "bg-warning";
        return "bg-danger";
    }


    //___________________________________
    function buildBaseProductoFromItem(it) {
  return {
    id: Number(it.producto_id || it.id),
    nombre: it.nombre,
    imagen: it.imagen,
    descripcion: it.descripcion,
    unidades_por_paquete: it.unidades_por_paquete || 0,
    paquetes_por_caja: it.paquetes_por_caja || 0,
    unidades_por_caja: it.unidades_por_caja || 0
  };
}

/**
 * Recalcula y reemplaza TODAS las filas del grupo (mismo producto + tipo_venta)
 * usando descomponerFIFO (FEFO real por presentación).
 */
async function recalcularYReemplazarGrupo(items, indexBase, totalDeseado, nuevoTipo) {
  const grupo = recolectarGrupoFIFO(items, indexBase);
  if (!grupo) return;

  const baseItem = items[indexBase];
  const baseProducto = buildBaseProductoFromItem(baseItem);

  const nuevosItems = await descomponerFIFO(baseProducto, totalDeseado, nuevoTipo);

  // borrar filas antiguas del grupo
  grupo.idxs.sort((a, b) => b - a).forEach(idx => items.splice(idx, 1));

  // insertar el grupo recalculado
  const insertAt = Math.min(...grupo.idxs);
  items.splice(insertAt, 0, ...nuevosItems);
}

    // ============================
    // RENDER CARRITO
    // ============================
    function renderCarritoTreinta() {
        if (!carritoLista) return;

        const v = ventaActiva();
        const items = v.productos || [];

        carritoLista.innerHTML = "";

        if (!items.length) {
            const btnActivo = document.querySelector(".btn-filtro-categoria.active");
            const nombreCat = btnActivo ? btnActivo.textContent.trim() : "productos";
            let textoCategoria = "tu catálogo";
            if (nombreCat && nombreCat.toLowerCase() !== "todos") textoCategoria = `la categoría ${nombreCat.toLowerCase()}`;

            carritoLista.innerHTML = `
                <div class="empty-cart-premium text-center py-5">
                    
                    <!-- ILUSTRACIÓN SVG -->
                    <div class="empty-illustration mb-3">
                        <svg viewBox="0 0 140 100" class="empty-svg">
                            <!-- Fondo círculo -->
                            <defs>
                                <linearGradient id="gradCart" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#4A90E2"/>
                                    <stop offset="100%" stop-color="#6C5CE7"/>
                                </linearGradient>
                            </defs>
                            <circle cx="70" cy="40" r="36" fill="url(#gradCart)" opacity="0.15"/>

                            <!-- Carrito -->
                            <rect x="30" y="32" width="60" height="26" rx="6" ry="6" fill="#ffffff" stroke="#4A90E2" stroke-width="2"/>
                            <path d="M32 32 L26 20" stroke="#4A90E2" stroke-width="2" stroke-linecap="round"/>
                            <path d="M88 32 L96 20" stroke="#4A90E2" stroke-width="2" stroke-linecap="round"/>

                            <!-- Cajas dentro -->
                            <rect x="38" y="26" width="12" height="10" rx="2" fill="#4A90E2" opacity="0.9"/>
                            <rect x="54" y="24" width="12" height="12" rx="2" fill="#6C5CE7" opacity="0.9"/>
                            <rect x="70" y="27" width="12" height="9"  rx="2" fill="#00B894" opacity="0.9"/>

                            <!-- Ruedas -->
                            <circle cx="44" cy="62" r="5" fill="#ffffff" stroke="#4A90E2" stroke-width="2"/>
                            <circle cx="76" cy="62" r="5" fill="#ffffff" stroke="#4A90E2" stroke-width="2"/>

                            <!-- Brillito -->
                            <path d="M96 25 Q104 20 108 26" stroke="#4A90E2" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
                        </svg>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Tu carrito está vacío</h5>
                    <p class="text-muted small mb-2">
                        Agrega productos desde <strong>${textoCategoria}</strong> para iniciar tu venta.
                    </p>

                    <button type="button" class="btn btn-primary btn-sm shadow-sm btn-empezar-compra">
                        <i class="fas fa-search"></i> Empezar a buscar productos
                    </button>
                </div>
            `;

            // Opcional: cuando hacen clic en el botón, enfocar el buscador
            const btnBuscar = carritoLista.querySelector(".btn-empezar-compra");
            if (btnBuscar && buscarInput) {
                btnBuscar.addEventListener("click", () => {
                    buscarInput.focus();
                    buscarInput.scrollIntoView({ behavior: "smooth", block: "center" });
                });
            }

            actualizarResumen();
            actualizarBotonCarrito();
            return;
        }

        items.forEach((p, index) => {
            const imgSrc = p.imagen ? `/uploads/productos/${p.imagen}` : "/img/sin-imagen.png";

            const precioUnitario = parseFloat(p.precio_unitario || 0); // FIJO
            const subtotal = precioUnitario * (parseInt(p.cantidad) || 0);


            const unidades = unidadesRealesDeItem(p);
            
            // 👇 USA LA FUNCIÓN DINÁMICA
            const subtotalFormateado = formatPrecioDinamico(subtotal);

            // ===============================
            // 🔥 STOCK POR LOTE (FIFO)
            // ===============================
            let stockMostrar = 0;
            let stockClase = "bg-success";

            const stockLote = parseInt(p.stock_lote || 0);
            const unidadesConsumidas = unidadesRealesDeItem(p);
            const queda = Math.max(0, stockLote - unidadesConsumidas);

            stockMostrar = queda;

            if (queda <= 0) stockClase = "bg-danger";
            else if (queda <= 5) stockClase = "bg-warning";

            const card = `
                <div class="carrito-item border-bottom pb-3 mb-3" data-index="${index}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-start gap-2">
                            <img src="${imgSrc}" alt="${p.nombre}" class="carrito-thumb">
                            <div>
                                <div class="d-flex justify-content-between align-items-center" style="min-width:200px;">
                                    <span class="fw-semibold small">${p.nombre}</span>
                                    <span class="badge ${stockClase} ms-2">
                                        Quedará: ${stockMostrar}
                                    </span>

                                </div>
                                <div class="text-muted extra-small">${p.descripcion || ""}</div>
                            </div>
                        </div>
                        <button class="btn btn-outline-danger btn-sm rounded-circle eliminar-item" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>

                    <div class="d-flex align-items-center mt-2 gap-2">
                        <div class="flex-grow-1">
                            <span class="d-block extra-small text-muted mb-1">Tipo venta</span>
                            <select class="form-select form-select-sm tipo-venta" data-index="${index}">
                                <option value="unidad" ${p.tipo_venta === "unidad" ? "selected" : ""}>Unidad</option>
                                ${
                                    p.unidades_por_paquete > 0 && p.precio_paquete > 0
                                        ? `<option value="paquete" ${p.tipo_venta === "paquete" ? "selected" : ""}>Paquete (${p.unidades_por_paquete})</option>`
                                        : ""
                                }
                                ${
                                    p.precio_caja > 0
                                        ? (() => {
                                            let texto = "Caja";
                                            if (p.unidades_por_caja > 0) {
                                                texto = `Caja (${p.unidades_por_caja} und.)`;
                                            } else if (p.paquetes_por_caja > 0 && p.unidades_por_paquete > 0) {
                                                texto = `Caja (${p.paquetes_por_caja * p.unidades_por_paquete} und.)`;
                                            } else if (p.unidades_por_paquete > 0) {
                                                texto = `Caja (${p.unidades_por_paquete} und.)`;
                                            }
                                            return `<option value="caja" ${p.tipo_venta === "caja" ? "selected" : ""}>${texto}</option>`;
                                        })()
                                        : ""
                                }
                            </select>
                        </div>

                        <div class="d-flex align-items-center gap-1">
                            <button class="btn btn-light btn-sm btn-restar" data-index="${index}">−</button>
                            <input type="number" min="1" class="form-control form-control-sm text-center cambiar-cantidad"
                                data-index="${index}" value="${p.cantidad}">
                            <button class="btn btn-light btn-sm btn-sumar" data-index="${index}">+</button>
                        </div>

                        <div class="text-end" style="width:90px;">
                            <div class="fw-semibold small">
                                S/ ${formatPrecioDinamico(precioUnitario)}
                            </div>
                        </div>
                    </div>

                    <div class="mt-2 small">
                        <span class="text-muted">Precio por <strong>${unidades}</strong> unidades:</span>
                        <span class="fw-semibold"> S/ ${subtotalFormateado}</span>
                    </div>
                </div>
            `;
            carritoLista.insertAdjacentHTML("beforeend", card);
        });

        actualizarResumen();
        actualizarBotonCarrito();
    }

    // ============================
    // Carrito eventos (delegación) + validación stock con reservas
    // ============================
    if (carritoLista) {

       carritoLista.addEventListener("change", async (e) => {
            if (!e.target.classList.contains("tipo-venta")) return;

            const v = ventaActiva();
            const i = Number(e.target.dataset.index);
            const it = v.productos[i];
            if (!it) return;

            const tipoAnterior = it.tipo_venta;
            const nuevoTipo = e.target.value;

            try {
                // 🔢 calcular factor de presentación
                let factor = 1;
                if (nuevoTipo === "paquete") factor = it.unidades_por_paquete || 0;
                if (nuevoTipo === "caja") {
                    factor = it.unidades_por_caja || 0;
                    if (factor <= 0) {
                        factor = (it.unidades_por_paquete || 0) * (it.paquetes_por_caja || 0);
                    }
                }

                if (factor <= 0) {
                    throw new Error("Presentación inválida");
                }

                // Recalcular con el stock total disponible y distribución FEFO.
                await recalcularYReemplazarGrupo(v.productos, i, 1, nuevoTipo);
                renderCarritoTreinta();

            } catch (err) {
                mostrarAlerta(err.message || "Stock insuficiente para esta presentación");
                e.target.value = tipoAnterior;
                it.tipo_venta = tipoAnterior;
                renderCarritoTreinta();
            }
        });


        carritoLista.addEventListener("input", async (e) => {

    if (!e.target.classList.contains("cambiar-cantidad")) return;

    const v = ventaActiva();
    const i = Number(e.target.dataset.index);
    const it = v.productos[i];
    if (!it) return;

    let cant = parseInt(e.target.value);

    if (isNaN(cant) || cant < 1) {
        it.cantidad = 1;
        return;
    }

    // 🔥 actualizar modelo inmediatamente
    it.cantidad = cant;

});
carritoLista.addEventListener("blur", async (e) => {

    if (!e.target.classList.contains("cambiar-cantidad")) return;

    const v = ventaActiva();
    const i = Number(e.target.dataset.index);
    const it = v.productos[i];
    if (!it) return;

    let cant = parseInt(e.target.value);
    if (isNaN(cant) || cant < 1) cant = 1;

    try {
        await recalcularYReemplazarGrupo(v.productos, i, cant, it.tipo_venta);
        renderCarritoTreinta();
    } catch (err) {
        mostrarAlerta(err.message || "Stock insuficiente");
        renderCarritoTreinta();
    }

}, true);



        carritoLista.addEventListener("click", async (e) => {
            const v = ventaActiva();

            const btnSumar = e.target.closest(".btn-sumar");
            const btnRestar = e.target.closest(".btn-restar");
            const btnEliminar = e.target.closest(".eliminar-item");

            if (btnSumar) {
                const i = Number(btnSumar.dataset.index);
                const it = v.productos[i];
                if (!it) return;

                const grupo = recolectarGrupoFIFO(v.productos, i);
                if (!grupo) return;

                // 🔥 incremento real según presentación
                const factor = factorPresentacion(it);
                const totalDeseado = grupo.total + 1;

                // 🔥 validar contra STOCK TOTAL del producto (en UNIDADES)
                const pid = Number(it.producto_id || it.id);
                const prod = productosCache.get(pid);
                const stockTotal = prod ? parseInt(prod.stock) || 0 : 0;

                const unidadesTotalesDeseadas = totalDeseado * factor;

                if (unidadesTotalesDeseadas > stockTotal) {
                    mostrarAlerta("Cantidad excede el stock disponible");
                    return;
                }

                try {
                    const baseProducto = {
                        id: grupo.pid,
                        nombre: it.nombre,
                        imagen: it.imagen,
                        descripcion: it.descripcion,
                        unidades_por_paquete: it.unidades_por_paquete,
                        paquetes_por_caja: it.paquetes_por_caja,
                        unidades_por_caja: it.unidades_por_caja
                    };

                    const nuevosItems = await descomponerFIFO(
                        baseProducto,
                        totalDeseado,
                        grupo.tipo
                    );

                    // borrar filas antiguas del grupo
                    grupo.idxs.sort((a, b) => b - a).forEach(idx =>
                        v.productos.splice(idx, 1)
                    );

                    // insertar el grupo recalculado
                    const insertAt = Math.min(...grupo.idxs);
                    v.productos.splice(insertAt, 0, ...nuevosItems);

                    renderCarritoTreinta();
                    return;

                } catch (err) {
                    mostrarAlerta(err.message || "Stock insuficiente");
                    return;
                }
            }

           if (btnRestar) {
            const i = Number(btnRestar.dataset.index);
            const it = v.productos[i];
            if (!it) return;

            const grupo = recolectarGrupoFIFO(v.productos, i);
            if (!grupo) return;

            const totalDeseado = Math.max(1, (grupo.total || 1) - 1);

            try {
                await recalcularYReemplazarGrupo(v.productos, i, totalDeseado, it.tipo_venta);
                renderCarritoTreinta();
            } catch (err) {
                mostrarAlerta(err.message || "No se pudo ajustar");
            }
            return;
            }


            if (btnEliminar) {
                const i = Number(btnEliminar.dataset.index);
                v.productos.splice(i, 1);
                actualizarContadorVentasEspera();
                renderCarritoTreinta();

                return;
            }
        });

        // 👇 AQUÍ MISMO PEGAS ESTO
        carritoLista.addEventListener("keydown", (e) => {
            if (!e.target.classList.contains("cambiar-cantidad")) return;

            const teclasPermitidas = [
                "Backspace",
                "Delete",
                "ArrowLeft",
                "ArrowRight",
                "Tab"
            ];

            // permitir control básico
            if (teclasPermitidas.includes(e.key)) return;

            // permitir solo números
            if (!/^[0-9]$/.test(e.key)) {
                e.preventDefault(); // 🔥 no cambia el valor actual
            }
        });

    }

    // Vaciar canasta (solo venta activa)
    const btnVaciarCanasta = document.getElementById("vaciar-canasta");
    if (btnVaciarCanasta) {
        btnVaciarCanasta.addEventListener("click", (e) => {
            e.preventDefault();

            const v = ventaActiva();
            if (!v.productos.length) return;

            Swal.fire({
                icon: "question",
                title: "Vaciar canasta",
                text: "¿Seguro que deseas eliminar todos los productos?",
                showCancelButton: true,
                confirmButtonText: "Sí, vaciar",
                cancelButtonText: "Cancelar"
            }).then(r => {
                if (!r.isConfirmed) return;
                v.productos = [];
                actualizarContadorVentasEspera();
                renderCarritoTreinta();
            });
        });
    }
    // ============================
    // EXPONER
    // ============================
    window.agregarProductoAVentaActiva = agregarProductoAVentaActiva;
    window.renderCarritoTreinta = renderCarritoTreinta;

});

function validarStockVentaActiva() {
    const v = ventaActiva();
    if (!v || !v.productos) return true;

    // agrupar por producto + tipo_venta
    const resumen = {};

    for (const it of v.productos) {
        const pid = Number(it.producto_id || it.id);
        const key = `${pid}_${it.tipo_venta}`;

        if (!resumen[key]) {
            resumen[key] = {
                nombre: it.nombre,
                totalSolicitado: 0
            };
        }

        resumen[key].totalSolicitado += (parseInt(it.cantidad) || 0) * factorPresentacion(it);
    }

    // validar contra stock TOTAL del producto (cache)
    for (const key in resumen) {
        const pid = Number(key.split("_")[0]);
        const prod = productosCache.get(pid);

        if (!prod) continue;

        const stockTotal = parseInt(prod.stock) || 0;
        const solicitado = resumen[key].totalSolicitado;

        if (solicitado > stockTotal) {
            mostrarAlerta(
                `El producto "${resumen[key].nombre}" no tiene stock suficiente.`
            );
            return false;
        }
    }

    return true;
}
