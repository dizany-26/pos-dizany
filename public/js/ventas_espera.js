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
            .pos-espera-item .delete{
                border:none; background: rgba(220,53,69,.1);
                color:#dc3545; width:34px; height:34px;
                border-radius:10px; cursor:pointer;
            }
            .pos-espera-empty{
                padding:14px; text-align:center;
                color:#777; font-size:13px;
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
            :root[data-theme='dark'] .pos-espera-item .delete{
                background: rgba(239,68,68,.2);
                color:#ff8e9b;
            }
            :root[data-theme='dark'] .pos-espera-empty{ color:#b7cae7; }
        `;
        document.head.appendChild(st);
    })();

    function totalVentaRapido(v) {
        return (v.productos || []).reduce(
            (s, it) => s + (parseFloat(it.precio_unitario || 0) * (parseInt(it.cantidad) || 0)),
            0
        );
    }

    function nombreVenta(v) {
        if (v.cliente?.razon && v.cliente.razon.trim() !== "") {
            return v.cliente.razon.trim();
        }
        return `Venta ${v.id.slice(-4)}`;
    }

    function eliminarVenta(id) {
        if (!POS.ventas[id]) return;
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

        document.querySelectorAll("#pos-espera-count").forEach(el => {
            el.innerText = ventasConItems.length;
        });
    }


    function renderVentasEsperaPanel() {
        if (!posEsperaPanel || !posEsperaCount) return;

        const ventasConItems = Object.values(POS.ventas || {})
            .filter(v => (v.productos || []).length > 0);

        document.querySelectorAll("#pos-espera-count").forEach(el => {
            el.innerText = ventasConItems.length;
        });

        if (ventasConItems.length === 0) {
            posEsperaPanel.innerHTML = `<div class="pos-espera-empty">No hay ventas en espera</div>`;
            return;
        }

        posEsperaPanel.innerHTML = "";

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
                    <strong>${nombreVenta(v)}</strong>
                    <span>S/ ${totalFormateado} • ${cantidad} ${label}</span>
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

                Swal.fire({
                    title: "Eliminar venta",
                    text: "Se perderán los productos reservados",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Eliminar",
                    cancelButtonText: "Cancelar"
                }).then(r => {
                    if (!r.isConfirmed) return;
                    eliminarVenta(v.id);
                    renderVentasEsperaPanel();
                });
            });

            posEsperaPanel.appendChild(item);
        });

        const add = document.createElement("button");
        add.type = "button";
        add.className = "btn btn-sm btn-primary w-100 mt-2";
        add.innerHTML = `<i class="fas fa-plus-circle me-1"></i> Nueva venta`;
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
        if (posEsperaPanel.classList.contains("d-none")) abrirPanelEspera();
        else cerrarPanelEspera();
    });

    document.addEventListener("click", () => cerrarPanelEspera());
    posEsperaPanel?.addEventListener("click", e => e.stopPropagation());

    // EXPONER
    window.renderVentasEsperaPanel = renderVentasEsperaPanel;
    window.actualizarContadorVentasEspera = actualizarContadorVentasEspera;

});
