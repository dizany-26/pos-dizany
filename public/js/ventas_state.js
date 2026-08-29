// ===============================
// PERSISTENCIA POS
// ===============================
const POS_STORE_KEY = "dizany_pos_store_v3";

function posLoad() {
    try {
        return JSON.parse(localStorage.getItem(POS_STORE_KEY)) || null;
    } catch {
        return null;
    }
}

function posSave(data) {
    localStorage.setItem(POS_STORE_KEY, JSON.stringify(data));
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
    } catch (e) {
        console.error("Error guardando POS:", e);
    }
}

window.guardarPOSAhora = guardarPOSAhora;

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
        cliente: {
            documento: "",
            razon: "Público General",
            direccion: "",
            no_guardado: false,
            sin_documento: true
        },
        metodo_pago: "",
        productos: []
    };
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
window.actualizarAliasVentaDesdeCliente = actualizarAliasVentaDesdeCliente;
