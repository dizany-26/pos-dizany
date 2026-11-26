// ===============================
// PERSISTENCIA POS
// ===============================
const POS_STORE_KEY = "dizany_pos_store_v3";
const POS_DRAFTS_URL = "/ventas/borradores";
let posServerTimer = null;
let posServerSyncInFlight = false;
let posServerSyncPending = false;
let posServerLoadInFlight = false;
const posKnownServerIds = new Set();

function csrfTokenPOS() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function borradoresSincronizables() {
    return Object.values(POS.ventas || {})
        .filter(venta => Array.isArray(venta.productos) && venta.productos.length > 0)
        .map(venta => ({ id: String(venta.id), datos: venta }));
}

async function sincronizarPOSConServidor() {
    if (posServerSyncInFlight) {
        posServerSyncPending = true;
        return;
    }

    posServerSyncInFlight = true;
    try {
        const borradores = borradoresSincronizables();
        const idsLocales = new Set(borradores.map(borrador => borrador.id));
        const idsEliminados = [...posKnownServerIds].filter(id => !idsLocales.has(id));

        const response = await fetch(POS_DRAFTS_URL, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfTokenPOS()
            },
            body: JSON.stringify({ borradores })
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        borradores.forEach(borrador => posKnownServerIds.add(borrador.id));
        for (const id of idsEliminados) {
            await eliminarBorradorDelServidor(id);
        }
    } catch (error) {
        // localStorage continúa siendo respaldo si se pierde la conexión.
        console.warn('No se pudieron sincronizar las ventas en espera:', error);
    } finally {
        posServerSyncInFlight = false;
        if (posServerSyncPending) {
            posServerSyncPending = false;
            programarSincronizacionServidor(100);
        }
    }
}

function programarSincronizacionServidor(ms = 500) {
    clearTimeout(posServerTimer);
    posServerTimer = setTimeout(sincronizarPOSConServidor, ms);
}

async function cargarBorradoresDelServidor() {
    if (posServerLoadInFlight) return;
    posServerLoadInFlight = true;
    try {
        const response = await fetch(POS_DRAFTS_URL, {
            headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        for (const borrador of data.borradores || []) {
            if (!borrador?.id) continue;
            if (borrador.eliminado) {
                delete POS.ventas[borrador.id];
                posKnownServerIds.delete(borrador.id);
                continue;
            }
            if (!borrador.datos) continue;
            posKnownServerIds.add(borrador.id);
            // Se conserva el carrito local si ya estaba siendo editado en este equipo.
            if (!POS.ventas[borrador.id]) {
                POS.ventas[borrador.id] = borrador.datos;
            }
        }

        asegurarVentaActiva();
        posSave(POS);
        window.actualizarContadorVentasEspera?.();
        window.renderVentasEsperaPanel?.();
        programarSincronizacionServidor(100);
    } catch (error) {
        console.warn('No se pudieron cargar las ventas en espera:', error);
    } finally {
        posServerLoadInFlight = false;
    }
}

async function eliminarBorradorDelServidor(id) {
    if (!id) return;
    try {
        const response = await fetch(`${POS_DRAFTS_URL}/${encodeURIComponent(id)}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfTokenPOS()
            }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        posKnownServerIds.delete(String(id));
    } catch (error) {
        console.warn('No se pudo eliminar la venta en espera sincronizada:', error);
    }
}

function posLoad() {
    try {
        return JSON.parse(localStorage.getItem(POS_STORE_KEY)) || null;
    } catch {
        return null;
    }
}

function posSave(data) {
    localStorage.setItem(POS_STORE_KEY, JSON.stringify(data));
    programarSincronizacionServidor();
}

function posClear() {
    localStorage.removeItem(POS_STORE_KEY);
}

let posTimer = null;
function posSaveDebounced(getSnapshot, ms = 250) {
    clearTimeout(posTimer);
    posTimer = setTimeout(() => {
        try {
            posSave(getSnapshot());
        } catch {}
    }, ms);
}

// ===============================
// ESTADO POS (ÚNICA FUENTE DE VERDAD)
// ===============================
let POS = posLoad() || {
    version: 3,
    ventaActivaId: null,
    ventas: {}
};

// ===============================
// SNAPSHOT (ESTO FALTABA)
// ===============================
function snapshotPOS() {
    return POS;
}

window.snapshotPOS = snapshotPOS;

// ===============================
// GUARDADO INMEDIATO (CRÍTICO)
// ===============================
function guardarPOSAhora() {
    try {
        posSave(snapshotPOS());
        programarSincronizacionServidor(50);
    } catch (e) {
        console.error("Error guardando POS:", e);
    }
}

window.guardarPOSAhora = guardarPOSAhora;
window.cargarBorradoresDelServidor = cargarBorradoresDelServidor;
window.eliminarBorradorDelServidor = eliminarBorradorDelServidor;

// ===============================
// HELPERS POS
// ===============================
function crearVentaVacia(id) {
    return {
        id,
        fase: 1,
        tipo_comprobante: "boleta",
        cliente_modo: "sin_documento",
        tipo_documento: "dni",
        informacion_adicional: "",
        estado_pago: "pagado",
        cliente: {
            documento: "",
            razon: "Público General",
            direccion: "",
            no_guardado: false,
            sin_documento: true
        },
        metodo_pago: "",
        pagos: [],
        formato_pdf: "a4",
        efectivo_recibido: "",
        credit_due_date: "",
        productos: []
    };
}

// Cuando el carrito queda sin productos, la operación anterior terminó.
// Se reemplaza el borrador completo para que ningún dato de esa venta
// (cliente, comprobante, observaciones, pagos o fase) pase a la siguiente.
function reiniciarVentaAbandonada(id = POS.ventaActivaId) {
    if (!id || !POS.ventas?.[id]) return false;

    POS.ventas[id] = crearVentaVacia(id);
    guardarPOSAhora();

    if (POS.ventaActivaId === id) {
        window.limpiarFlujoVentaConfirmada?.();
        window.renderTodo?.();
    }

    return true;
}

function asegurarVentaActiva() {
    const ids = Object.keys(POS.ventas || {});
    if (!ids.length) {
        const id = uidVenta();
        POS.ventas[id] = crearVentaVacia(id);
        POS.ventaActivaId = id;
        guardarPOSAhora();
    }

    if (!POS.ventaActivaId || !POS.ventas[POS.ventaActivaId]) {
        POS.ventaActivaId = Object.keys(POS.ventas)[0];
        guardarPOSAhora();
    }
}

function ventaActiva() {
    asegurarVentaActiva();
    return POS.ventas[POS.ventaActivaId];
}

// ===============================
// ALIAS DE VENTA (CLIENTE)
// ===============================
function actualizarAliasVentaDesdeCliente() {
    const v = ventaActiva();
    if (!v || !v.cliente) return;

    const nombre = (v.cliente.razon || "").trim();
    if (!nombre) return;

    v.alias = nombre;

    posSaveDebounced(snapshotPOS, 50);
}

// ===============================
// EXPONER GLOBAL
// ===============================
window.POS = POS;
window.ventaActiva = ventaActiva;
window.asegurarVentaActiva = asegurarVentaActiva;
window.crearVentaVacia = crearVentaVacia;
window.reiniciarVentaAbandonada = reiniciarVentaAbandonada;
window.actualizarAliasVentaDesdeCliente = actualizarAliasVentaDesdeCliente;
