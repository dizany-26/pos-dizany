// ===============================
// ESTADO GLOBAL DEL CLIENTE
// ===============================
window.estadoCliente = "ninguno";
// ninguno | nuevo_no_guardado | ok

document.addEventListener('DOMContentLoaded', function () {

    // ===============================
    // ELEMENTOS DOM
    // ===============================
    const inputDocumento  = document.getElementById('documento');
    const inputRazon      = document.getElementById('razon_social');
    const inputDireccion  = document.getElementById('direccion');
    const estadoRUC       = document.getElementById('estado_ruc');

    const btnAccion  = document.getElementById('btn-cliente-accion');
    const iconoPlus  = document.getElementById('icono-plus');
    const iconoSave  = document.getElementById('icono-save');

    const modalEl = document.getElementById('clientModal');
    const modalCliente = (modalEl && window.bootstrap)
        ? new bootstrap.Modal(modalEl)
        : null;

    if (!inputDocumento || !inputRazon || !inputDireccion || !btnAccion || !iconoPlus || !iconoSave) {
        console.warn("[ventas_dniruc] Faltan elementos del DOM");
        return;
    }

    // ===============================
    // HELPERS VISUALES
    // ===============================
    function mostrarIconoGuardar() {
        iconoPlus.classList.add("d-none");
        iconoSave.classList.remove("d-none");

        iconoSave.classList.add("icono-animado");
        setTimeout(() => iconoSave.classList.remove("icono-animado"), 600);
    }

    function mostrarIconoAgregar() {
        iconoSave.classList.add("d-none");
        iconoPlus.classList.remove("d-none");

        iconoPlus.classList.add("icono-animado");
        setTimeout(() => iconoPlus.classList.remove("icono-animado"), 600);
    }

    function limpiarCliente() {
        inputRazon.value = "";
        inputDireccion.value = "";
        estadoRUC.textContent = "";

        window.estadoCliente = "ninguno";
        mostrarIconoAgregar();

        // 🧠 limpiar cliente de la venta activa
        const v = window.ventaActiva?.();
        if (v) v.cliente = null;
    }

    // ===============================
    // SINCRONIZAR CLIENTE → VENTA
    // ===============================
    function setClienteVenta(cliente) {
        const v = window.ventaActiva?.();
        if (!v) return;

        v.cliente = cliente;

        if (window.actualizarAliasVentaDesdeCliente) {
            actualizarAliasVentaDesdeCliente();
        }
    }

    // ===============================
    // CONSULTAS
    // ===============================
    function buscarEnBD(dniRuc) {
        return fetch(`/buscar-cliente/${dniRuc}`).then(r => r.json());
    }

    function consultarDNI(dni) {
        return fetch(`/consulta-documento/dni/${dni}`).then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'DNI no encontrado');
            return data;
        });
    }

    function consultarRUC(ruc) {
        return fetch(`/consulta-documento/ruc/${ruc}`).then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'RUC no encontrado');
            return data;
        });
    }

    // ===============================
    // INPUT DNI / RUC
    // ===============================
    let ultimaConsulta = null;
    let apiFallida = false;

    inputDocumento.addEventListener('input', () => {

        const valor = inputDocumento.value.trim();

        if (valor !== ultimaConsulta) apiFallida = false;

        if (valor.length < 8) {
            limpiarCliente();
            ultimaConsulta = null;
            return;
        }

        if (valor === ultimaConsulta) return;
        ultimaConsulta = valor;

        // 1️⃣ BUSCAR EN BD
        buscarEnBD(valor).then(res => {

            if (res.encontrado) {

                inputRazon.value = res.nombre;
                inputDireccion.value = res.direccion;
                estadoRUC.textContent = "";

                window.estadoCliente = "ok";
                mostrarIconoAgregar();

                setClienteVenta({
                    tipo: valor.length === 8 ? 'DNI' : 'RUC',
                    documento: valor,
                    razon: res.nombre,
                    direccion: res.direccion
                });

                return;
            }

            if (apiFallida) {
                estadoRUC.textContent = "❌ Documento no encontrado";
                return;
            }

            // 2️⃣ DNI API
            if (valor.length === 8) {
                consultarDNI(valor)
                    .then(data => {
                        const razon = data.nombre || "";

                        inputRazon.value = razon;
                        inputDireccion.value = "No disponible";
                        estadoRUC.textContent = "";

                        window.estadoCliente = "nuevo_no_guardado";
                        mostrarIconoGuardar();

                        setClienteVenta({
                            tipo: 'DNI',
                            documento: valor,
                            razon: razon,
                            direccion: "No disponible"
                        });
                    })
                    .catch(() => {
                        apiFallida = true;
                        limpiarCliente();
                        estadoRUC.textContent = "❌ DNI no encontrado";
                    });
            }

            // 3️⃣ RUC API
            if (valor.length === 11) {
                consultarRUC(valor)
                    .then(data => {
                        const razon = data.nombre || "";
                        const direccion = data.direccion || "Sin dirección";

                        inputRazon.value = razon;
                        inputDireccion.value = direccion;
                        estadoRUC.textContent = `✔️ ${data.estado || ""}`;

                        window.estadoCliente = "nuevo_no_guardado";
                        mostrarIconoGuardar();

                        setClienteVenta({
                            tipo: 'RUC',
                            documento: valor,
                            razon: razon,
                            direccion: direccion
                        });
                    })
                    .catch(() => {
                        apiFallida = true;
                        limpiarCliente();
                        estadoRUC.textContent = "❌ RUC no encontrado";
                    });
            }

        });
    });

    // ===============================
    // BOTÓN + / 💾
    // ===============================
    btnAccion.addEventListener("click", () => {

        // 💾 GUARDAR CLIENTE
        if (!iconoSave.classList.contains("d-none")) {

            const dniRuc    = inputDocumento.value.trim();
            const razon     = inputRazon.value.trim();
            const direccion = inputDireccion.value.trim();

            if (!dniRuc || !razon) {
                Swal.fire("Error", "No hay datos para guardar", "error");
                return;
            }

            fetch('/guardar-cliente', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .content
                },
                body: JSON.stringify({
                    dni_ruc: dniRuc,
                    razon_social: razon,
                    direccion: direccion
                })
            })
            .then(r => r.json())
            .then(res => {
                if (!res.exito) {
                    Swal.fire("Error", res.mensaje || "Error al guardar", "error");
                    return;
                }

                // ✅ alerta OK
                Swal.fire({
                    icon: "success",
                    title: "Cliente guardado",
                    timer: 1500,
                    showConfirmButton: false
                });

                window.estadoCliente = "ok";
                mostrarIconoAgregar();

                // 🔥🔥🔥 LO IMPORTANTE (SIN ALIAS, SIN EVENTOS, SIN MAGIA)
                const v = window.ventaActiva?.();
                if (v) {
                    if (!v.cliente) v.cliente = {};
                    v.cliente.documento = dniRuc;
                    v.cliente.razon = razon;
                    v.cliente.direccion = direccion;
                    v.cliente.no_guardado = false;
                }

                // 🔄 refrescar panel de ventas en espera
                if (window.renderVentasEsperaPanel) {
                    window.renderVentasEsperaPanel();
                }
            });

            return;
        }

        // ➕ MODAL MANUAL
        if (modalCliente) modalCliente.show();
    });

});
