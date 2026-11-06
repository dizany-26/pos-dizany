// ============================
// 🔥 DECLARACIONES GLOBALES
// (evita ReferenceError)
// ============================
window.carritoLista = null;
window.estadoPagoSelect = null;

window.posEsperaPanel = null;
window.posEsperaCount = null;

window.btnConfirmar3 = null;
window.hiddenMetodoPago = null;
window.btnIrStep2 = null;
window.btnIrStep3 = null;
window.btnVolverStep1 = null;
window.btnVolverStep2 = null;
window.inputPaga = null;
window.volcarUIaVentaActiva = null;

// snapshotPOS será asignado luego
window.snapshotPOS = null;

// ===============================
// IGV global desde configuración
// ===============================
const igvConfigInput = document.getElementById("igv-config");
const IGV_PERCENT = igvConfigInput
    ? parseFloat(igvConfigInput.value) || 0
    : 0;

// ===============================
// HELPERS CORE
// ===============================
function calcularPrecioFinal(precioBase) {
    const base = parseFloat(precioBase) || 0;
    if (IGV_PERCENT <= 0) return base;
    return base * (1 + IGV_PERCENT / 100);
}

function cortar(txt, n = 35) {
    if (!txt) return "";
    return txt.length > n ? txt.substring(0, n) + "..." : txt;
}

function uidVenta() {
    return (
        "V" +
        Date.now().toString(36) +
        Math.random().toString(36).slice(2, 6)
    );
}

// ===============================
// SONIDOS
// ===============================
const sonidoError = new Audio("/sonidos/error-alert.mp3");
const sonidoExito = new Audio("/sonidos/success.mp3");

// ===============================
// ALERTA GLOBAL
// ===============================
function mostrarAlerta(msg) {
    Swal.fire({
        icon: "warning",
        title: "¡Atención!",
        text: msg,
        timer: 2500,
        showConfirmButton: false
    });

    try {
        window.DizanySound?.play(sonidoError);
    } catch {}
}

// ============================
// EXPONER CORE (SOLO LO REAL)
// ============================
window.calcularPrecioFinal = calcularPrecioFinal;
window.cortar = cortar;
window.uidVenta = uidVenta;
window.mostrarAlerta = mostrarAlerta;
