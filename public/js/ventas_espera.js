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
// VENTAS EN ESPERA / MULTI-VENTA
// ===============================

document.addEventListener("DOMContentLoaded", () => {

    const btnPosEspera   = document.getElementById("btn-pos-espera");
    const posEsperaCount = document.getElementById("pos-espera-count");
    const posEsperaPanel = document.getElementById("pos-espera-panel");

    (function injectStyles() {
        if (document.getElementById("pos-espera-style")) return;
        const st = document.createElement("style");
        st.id = "pos-espera-style";
        st.innerHTML = `
            .pos-espera-wrapper{ position:relative; }
            .pos-espera-panel{
                position:absolute; top: calc(100% + 8px); right: 0;
                width: 340px; max-height: 420px; overflow:auto;
                border-radius: 14px; background: #fff;
                box-shadow: 0 18px 40px rgba(0,0,0,.18);
                transform: translateY(-8px); opacity: 0;
                transition: .18s ease; z-index: 9999; padding: 10px;
            }
            .pos-espera-panel.show{ transform: translateY(0); opacity: 1; }
            .pos-espera-item{
                display:flex; align-items:center; justify-content:space-between;
                gap:10px; padding:10px; border-radius:12px;
                border:1px solid rgba(0,0,0,.06); margin-bottom:8px;
            }
            .pos-espera-item.active{
                border-color: rgba(0,123,255,.35);
                background: rgba(0,123,255,.06);
            }
            .pos-espera-item .info{ cursor:pointer; flex:1; }
            .pos-espera-item .info strong{ display:block; font-size:13px; }
            .pos-espera-item .info span{ color:#666; font-size:12px; }
            .pos-espera-origin{
                display:inline-flex; margin-top:5px; padding:2px 7px;
                border-radius:999px; background:#e8f2ff; color:#1469c9 !important;
                font-size:10px !important; font-weight:800; letter-spacing:.06em;
            }
            .pos-espera-item .delete{
                border:none; background: rgba(220,53,69,.1);
                color:#dc3545; width:34px; height:34px;
                border-radius:10px; cursor:pointer;
            }
            .pos-espera-empty{
                padding:14px; text-align:center;
                color:#777; font-size:13px;
            }
            .pos-espera-add-btn{
                display:inline-flex; align-items:center; justify-content:center;
                min-width: 152px; margin: 10px auto 2px;
                padding: 8px 14px; font-size: 13px;
            }
            :root[data-theme='dark'] .pos-espera-panel{
                background: linear-gradient(180deg, #0f223f 0%, #0b1c35 100%);
                border: 1px solid #2d4e7a;
                color:#eaf2ff;
                box-shadow: 0 20px 38px rgba(2,8,22,.58);
            }
            :root[data-theme='dark'] .pos-espera-item{
                border-color: #355a8a;
                background: #122a4c;
            }
            :root[data-theme='dark'] .pos-espera-item.active{
                border-color: #5ea0ff;
                background: rgba(53,118,232,.26);
            }
            :root[data-theme='dark'] .pos-espera-item .info strong{ color:#f1f6ff; }
            :root[data-theme='dark'] .pos-espera-item .info span{ color:#adc7ea; }
            :root[data-theme='dark'] .pos-espera-origin{
                background:rgba(37,99,235,.25); color:#8fc2ff !important;
            }
            :root[data-theme='dark'] .pos-espera-item .delete{
                background: rgba(239,68,68,.2);
                color:#ff8e9b;
            }
            :root[data-theme='dark'] .pos-espera-empty{ color:#b7cae7; }
            :root[data-theme='dark'] .pos-espera-add-btn{
                background: rgba(37,99,235,.22) !important;
                color:#bfdbfe !important;
            }
        `;
        document.head.appendChild(st);
    })();

    let pedidosCatalogo = [];

    function escaparHtml(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, caracter => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[caracter]);
    }

    function idsCatalogoImportados() {
        return new Set(Object.values(POS.ventas || {})
            .map(venta => Number(venta.pedido_catalogo_id || 0))
            .filter(Boolean));
    }

    function pedidosCatalogoDisponibles() {
        const importados = idsCatalogoImportados();
        return pedidosCatalogo.filter(pedido => !importados.has(Number(pedido.id)));
    }

    async function cargarPedidosCatalogo(renderizar = false) {
        try {
            const response = await fetch('/ventas/pedidos-catalogo', {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('No se pudieron consultar los pedidos del catálogo.');
            pedidosCatalogo = await response.json();
            actualizarContadorVentasEspera();
            if (renderizar && !posEsperaPanel?.classList.contains('d-none')) {
                renderVentasEsperaPanel();
            }
        } catch (error) {
            console.error('Pedidos del catálogo:', error);
        }
    }

    async function importarPedidoCatalogo(pedido) {
        const existente = Object.values(POS.ventas || {})
            .find(venta => Number(venta.pedido_catalogo_id) === Number(pedido.id));
        if (existente) {
            POS.ventaActivaId = existente.id;
        } else {
            const response = await fetch('/productos/iniciales', {
                headers: { 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error('No se pudo cargar el catálogo del POS.');
            const productos = await response.json();
            const mapa = new Map(productos.map(producto => [Number(producto.id), producto]));
            const lineas = [];

            for (const item of pedido.items || []) {
                const producto = mapa.get(Number(item.producto_id));
                if (!producto) throw new Error(`El producto ${item.nombre || item.producto_id} ya no está disponible.`);
                const partes = await window.descomponerFIFO(
                    producto,
                    Number(item.cantidad),
                    item.presentacion
                );
                lineas.push(...partes);
            }

            const id = uidVenta();
            const venta = crearVentaVacia(id);
            venta.productos = lineas;
            venta.alias = `${pedido.cliente_nombre} · ${pedido.codigo}`;
            venta.cliente.razon = pedido.cliente_nombre;
            venta.metodo_pago = 'efectivo';
            venta.origen = 'catalogo';
            venta.pedido_catalogo_id = Number(pedido.id);
            venta.pedido_catalogo_codigo = pedido.codigo;
            venta.pedido_catalogo_telefono = pedido.cliente_telefono;
            venta.informacion_adicional = [
                `Pedido del catálogo ${pedido.codigo}`,
                `Teléfono: ${pedido.cliente_telefono}`,
                pedido.tipo_entrega === 'domicilio'
                    ? `Entrega a domicilio: ${pedido.direccion || ''}`
                    : 'Recoger en tienda'
            ].join(' | ');
            POS.ventas[id] = venta;
            POS.ventaActivaId = id;
        }

        guardarPOSAhora();
        window.restaurarVentaActivaEnUI?.();
        window.renderTodo?.();
        renderVentasEsperaPanel();
        cerrarPanelEspera();
    }

    function totalVentaRapido(v) {
        return (v.productos || []).reduce(
            (s, it) => s + (parseFloat(it.precio_unitario || 0) * (parseInt(it.cantidad) || 0)),
            0
        );
    }

    function nombreVenta(v) {
        if (v.alias) return v.alias;
        if (v.cliente?.razon && v.cliente.razon.trim() !== "") {
            return v.cliente.razon.trim();
        }
        return `Venta ${v.id.slice(-4)}`;
    }

    async function cancelarPedidoCatalogo(id) {
        const response = await fetch(`/ventas/pedidos-catalogo/${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo cancelar el pedido.');
        }
        pedidosCatalogo = pedidosCatalogo.filter(pedido => Number(pedido.id) !== Number(id));
    }

    async function eliminarVenta(id) {
        if (!POS.ventas[id]) return;
        const venta = POS.ventas[id];
        if (venta.pedido_catalogo_id) {
            await cancelarPedidoCatalogo(venta.pedido_catalogo_id);
        }
        delete POS.ventas[id];

        guardarPOSAhora();   // 🔥 CLAVE
        asegurarVentaActiva();
        renderTodo();


        if (typeof window.restaurarVentaActivaEnUI === "function") {
            window.restaurarVentaActivaEnUI();
        }
        if (typeof window.renderTodo === "function") {
            window.renderTodo();
        }
    }

    function actualizarContadorVentasEspera() {
        if (!posEsperaCount) return;

        const ventasConItems = Object.values(POS.ventas || {})
            .filter(v => (v.productos || []).length > 0);

        const totalEspera = ventasConItems.length + pedidosCatalogoDisponibles().length;

        document.querySelectorAll("#pos-espera-count").forEach(el => {
            el.innerText = totalEspera;
        });
    }


    function renderVentasEsperaPanel() {
        if (!posEsperaPanel || !posEsperaCount) return;

        const ventasConItems = Object.values(POS.ventas || {})
            .filter(v => (v.productos || []).length > 0);
        const pedidosDisponibles = pedidosCatalogoDisponibles();

        document.querySelectorAll("#pos-espera-count").forEach(el => {
            el.innerText = ventasConItems.length + pedidosDisponibles.length;
        });

        if (ventasConItems.length === 0 && pedidosDisponibles.length === 0) {
            posEsperaPanel.innerHTML = `<div class="pos-espera-empty">No hay ventas en espera</div>`;
            return;
        }

        posEsperaPanel.innerHTML = "";

        pedidosDisponibles.forEach(pedido => {
            const cantidad = (pedido.items || []).length;
            const item = document.createElement('div');
            item.className = 'pos-espera-item';
            item.innerHTML = `
                <div class="info">
                    <strong>${escaparHtml(pedido.cliente_nombre)} · ${escaparHtml(pedido.codigo)}</strong>
                    <span>S/ ${formatPrecioDinamico(Number(pedido.total || 0))} • ${cantidad} ${cantidad === 1 ? 'producto' : 'productos'}</span>
                    <span class="pos-espera-origin"><i class="fas fa-shopping-cart"></i>&nbsp; CATÁLOGO</span>
                </div>
                <button class="delete" type="button" title="Cancelar pedido">
                    <i class="fas fa-trash"></i>
                </button>`;
            item.querySelector('.info').addEventListener('click', async () => {
                item.style.pointerEvents = 'none';
                try {
                    await importarPedidoCatalogo(pedido);
                } catch (error) {
                    item.style.pointerEvents = '';
                    Swal.fire({
                        icon: 'warning',
                        title: 'No se pudo cargar el pedido',
                        text: error.message || 'Actualiza el stock e inténtalo nuevamente.'
                    });
                }
            });
            item.querySelector('.delete').addEventListener('click', event => {
                event.stopPropagation();
                Swal.fire({
                    title: 'Cancelar pedido del catálogo',
                    text: 'El pedido desaparecerá de Ventas en espera.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cancelar pedido',
                    cancelButtonText: 'Volver'
                }).then(async result => {
                    if (!result.isConfirmed) return;
                    try {
                        await cancelarPedidoCatalogo(pedido.id);
                        renderVentasEsperaPanel();
                    } catch (error) {
                        Swal.fire('No se pudo cancelar', error.message, 'error');
                    }
                });
            });
            posEsperaPanel.appendChild(item);
        });

        ventasConItems.forEach(v => {
            const total = totalVentaRapido(v);
            const cantidad = (v.productos || []).length;
            const label = cantidad === 1 ? "producto" : "productos";
            
            // 👇 DEFINIMOS la variable totalFormateado usando tu función
            const totalFormateado = formatPrecioDinamico(total);

            const item = document.createElement("div");
            // 👇 Eliminamos esta línea que no usaba variables definidas
            // const precioFormateado = formatPrecioDinamico(precioFinal); 
            item.className = "pos-espera-item" + (v.id === POS.ventaActivaId ? " active" : "");

            item.innerHTML = `
                <div class="info">
                    <strong>${escaparHtml(nombreVenta(v))}</strong>
                    <span>S/ ${totalFormateado} • ${cantidad} ${label}</span>
                    ${v.origen === 'catalogo'
                        ? `<span class="pos-espera-origin"><i class="fas fa-shopping-cart"></i>&nbsp; CATÁLOGO</span>`
                        : ''}
                </div>
                <button class="delete" type="button" title="Eliminar venta">
                    <i class="fas fa-trash"></i>
                </button>
            `;

            item.querySelector(".info").addEventListener("click", () => {
                POS.ventaActivaId = v.id;

                if (typeof window.restaurarVentaActivaEnUI === "function") {
                    window.restaurarVentaActivaEnUI();
                }
                if (typeof window.renderTodo === "function") {
                    window.renderTodo();
                }
                cerrarPanelEspera();
            });

            item.querySelector(".delete").addEventListener("click", (e) => {
                e.stopPropagation();

                const esCatalogo = Boolean(v.pedido_catalogo_id);
                Swal.fire({
                    title: esCatalogo ? 'Cancelar pedido del catálogo' : "Eliminar venta",
                    text: esCatalogo
                        ? 'El pedido desaparecerá de Ventas en espera.'
                        : "Se perderán los productos reservados",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: esCatalogo ? 'Sí, cancelar pedido' : "Eliminar",
                    cancelButtonText: "Cancelar"
                }).then(async r => {
                    if (!r.isConfirmed) return;
                    try {
                        await eliminarVenta(v.id);
                        renderVentasEsperaPanel();
                    } catch (error) {
                        Swal.fire('No se pudo cancelar', error.message, 'error');
                    }
                });
            });

            posEsperaPanel.appendChild(item);
        });

        const add = document.createElement("button");
        add.type = "button";
        add.className = "btn-soft btn-soft-primary pos-espera-add-btn";
        add.innerHTML = `<i class="fas fa-plus-circle"></i> Nueva venta`;
        add.addEventListener("click", () => {

        const id = uidVenta();
        POS.ventas[id] = crearVentaVacia(id);
        POS.ventaActivaId = id;
        POS.ventas[id].metodo_pago = "efectivo";

        // 🔥 GUARDAR ESTADO REAL DEL POS (CLAVE)
        if (typeof window.guardarPOSAhora === "function") {
            window.guardarPOSAhora();
        }

        if (typeof window.restaurarVentaActivaEnUI === "function") {
            window.restaurarVentaActivaEnUI();
        }
        if (typeof window.renderTodo === "function") {
            window.renderTodo();
        }

        cerrarPanelEspera();
    });

        posEsperaPanel.appendChild(add);
    }

    function abrirPanelEspera() {
        if (!posEsperaPanel) return;
        posEsperaPanel.classList.remove("d-none");
        requestAnimationFrame(() => posEsperaPanel.classList.add("show"));
    }
    function cerrarPanelEspera() {
        if (!posEsperaPanel) return;
        posEsperaPanel.classList.remove("show");
        setTimeout(() => posEsperaPanel.classList.add("d-none"), 180);
    }

    btnPosEspera?.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();

        renderVentasEsperaPanel();
        cargarPedidosCatalogo(true);
        if (posEsperaPanel.classList.contains("d-none")) abrirPanelEspera();
        else cerrarPanelEspera();
    });

    document.addEventListener("click", () => cerrarPanelEspera());
    posEsperaPanel?.addEventListener("click", e => e.stopPropagation());

    // EXPONER
    window.renderVentasEsperaPanel = renderVentasEsperaPanel;
    window.actualizarContadorVentasEspera = actualizarContadorVentasEspera;

    cargarPedidosCatalogo();
    window.setInterval(() => cargarPedidosCatalogo(true), 15000);

});
