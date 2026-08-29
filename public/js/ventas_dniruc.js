// Flujo de cliente del POS: Público General, DNI y RUC.
window.estadoCliente = "ninguno";

document.addEventListener("DOMContentLoaded", () => {
    const tipoComprobante = document.getElementById("tipo_comprobante");
    const modoSin = document.getElementById("cliente-sin-documento");
    const modoCon = document.getElementById("cliente-con-documento");
    const tipoDocumento = document.getElementById("tipo_documento_cliente");
    const documento = document.getElementById("documento");
    const razon = document.getElementById("razon_social");
    const direccion = document.getElementById("direccion");
    const estado = document.getElementById("estado_ruc");
    const btnAccion = document.getElementById("btn-cliente-accion");
    const iconoPlus = document.getElementById("icono-plus");
    const iconoSave = document.getElementById("icono-save");
    const informacion = document.getElementById("informacion_adicional");
    const modalEl = document.getElementById("clientModal");
    const modalCliente = modalEl && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

    if (!tipoComprobante || !tipoDocumento || !documento || !razon || !direccion) return;

    let ultimaConsulta = null;
    let consultaToken = 0;
    const ventaActual = () => typeof window.ventaActiva === "function" ? window.ventaActiva() : null;
    const modoActual = () => modoCon?.checked ? "con_documento" : "sin_documento";

    function iconoGuardar(guardar) {
        iconoPlus?.classList.toggle("d-none", guardar);
        iconoSave?.classList.toggle("d-none", !guardar);
    }

    function sincronizar(cliente = null) {
        const venta = ventaActual();
        if (!venta) return;
        venta.tipo_comprobante = tipoComprobante.value;
        venta.cliente_modo = modoActual();
        venta.tipo_documento = tipoDocumento.value;
        venta.informacion_adicional = (informacion?.value || "").trim();
        if (cliente) venta.cliente = cliente;
        window.actualizarAliasVentaDesdeCliente?.();
        if (typeof window.snapshotPOS === "function") posSaveDebounced(snapshotPOS, 30);
    }

    function clienteBase(valores = {}) {
        return {
            id: null,
            tipo: tipoDocumento.value.toUpperCase(),
            documento: "",
            razon: "",
            direccion: "",
            no_guardado: false,
            sin_documento: false,
            ...valores
        };
    }

    function establecerPublicoGeneral() {
        consultaToken++;
        ultimaConsulta = null;
        documento.value = "";
        razon.value = "Público General";
        direccion.value = "";
        estado.textContent = "Venta sin documento";
        estado.className = "text-muted small mb-1";
        window.estadoCliente = "ok";
        iconoGuardar(false);
        sincronizar(clienteBase({ razon: "Público General", sin_documento: true }));
    }

    function limpiarCliente() {
        consultaToken++;
        ultimaConsulta = null;
        documento.value = "";
        razon.value = "";
        direccion.value = "";
        estado.textContent = "";
        estado.className = "text-success small mb-1";
        window.estadoCliente = "ninguno";
        iconoGuardar(false);
        sincronizar(clienteBase());
    }

    function configurarDocumento() {
        const esRuc = tipoDocumento.value === "ruc";
        documento.maxLength = esRuc ? 11 : 8;
        documento.placeholder = esRuc ? "RUC (11 dígitos)" : "DNI (8 dígitos)";
    }

    function aplicarFlujo(preservar = false) {
        const comprobante = tipoComprobante.value;
        if (comprobante === "factura") {
            modoCon.checked = true;
            modoSin.disabled = true;
            tipoDocumento.value = "ruc";
            tipoDocumento.disabled = true;
        } else {
            modoSin.disabled = false;
            if (comprobante === "boleta") {
                tipoDocumento.value = "dni";
                tipoDocumento.disabled = true;
            } else {
                tipoDocumento.disabled = modoActual() === "sin_documento";
            }
        }

        const conDocumento = modoActual() === "con_documento";
        documento.disabled = !conDocumento;
        btnAccion.disabled = !conDocumento;
        configurarDocumento();

        if (!preservar) conDocumento ? limpiarCliente() : establecerPublicoGeneral();
        else if (!conDocumento) establecerPublicoGeneral();
        else sincronizar();
    }

    window.actualizarFlujoClienteComprobante = aplicarFlujo;
    window.setClienteVentaPOS = cliente => {
        const numero = cliente?.documento || "";
        modoCon.checked = true;
        tipoDocumento.value = numero.length === 11 ? "ruc" : "dni";
        documento.value = numero;
        razon.value = cliente?.razon || cliente?.nombre || "";
        direccion.value = cliente?.direccion || "";
        window.estadoCliente = "ok";
        iconoGuardar(false);
        aplicarFlujo(true);
        sincronizar(clienteBase({
            id: cliente?.id || null,
            documento: numero,
            razon: razon.value,
            direccion: direccion.value
        }));
    };

    async function buscarLocal(numero) {
        const response = await fetch(`/buscar-cliente/${encodeURIComponent(numero)}`);
        if (!response.ok) throw new Error("No se pudo consultar la base de clientes.");
        return response.json();
    }

    async function buscarApi(numero) {
        const response = await fetch(`/consulta-documento/${tipoDocumento.value}/${encodeURIComponent(numero)}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || "Documento no encontrado");
        return data;
    }

    async function resolverDocumento(numero) {
        const token = ++consultaToken;
        estado.textContent = "Buscando cliente...";
        estado.className = "text-muted small mb-1";
        try {
            const local = await buscarLocal(numero);
            if (token !== consultaToken) return;
            if (local.encontrado) {
                razon.value = local.nombre || "";
                direccion.value = local.direccion || "";
                estado.textContent = "Cliente registrado";
                estado.className = "text-success small mb-1";
                window.estadoCliente = "ok";
                iconoGuardar(false);
                sincronizar(clienteBase({ id: local.id, documento: numero, razon: razon.value, direccion: direccion.value }));
                return;
            }

            const remoto = await buscarApi(numero);
            if (token !== consultaToken) return;
            razon.value = remoto.nombre || "";
            direccion.value = remoto.direccion || (tipoDocumento.value === "dni" ? "No disponible" : "Sin dirección");
            estado.textContent = "Cliente encontrado. Guárdalo antes de continuar.";
            estado.className = "text-warning small mb-1";
            window.estadoCliente = "nuevo_no_guardado";
            iconoGuardar(true);
            sincronizar(clienteBase({ documento: numero, razon: razon.value, direccion: direccion.value, no_guardado: true }));
        } catch (error) {
            if (token !== consultaToken) return;
            razon.value = "";
            direccion.value = "";
            estado.textContent = error.message || "Documento no encontrado";
            estado.className = "text-danger small mb-1";
            window.estadoCliente = "ninguno";
            iconoGuardar(false);
            sincronizar(clienteBase({ documento: numero }));
        }
    }

    tipoComprobante.addEventListener("change", () => aplicarFlujo(false));
    modoSin?.addEventListener("change", () => aplicarFlujo(false));
    modoCon?.addEventListener("change", () => aplicarFlujo(false));
    tipoDocumento.addEventListener("change", () => {
        configurarDocumento();
        limpiarCliente();
        documento.focus();
    });

    documento.addEventListener("input", () => {
        const maximo = tipoDocumento.value === "ruc" ? 11 : 8;
        const numero = documento.value.replace(/\D/g, "").slice(0, maximo);
        documento.value = numero;
        if (numero !== ultimaConsulta) {
            razon.value = "";
            direccion.value = "";
            estado.textContent = numero.length < maximo ? `Completa los ${maximo} dígitos.` : "";
            window.estadoCliente = "ninguno";
            iconoGuardar(false);
        }
        sincronizar(clienteBase({ documento: numero }));
        if (numero.length === maximo && numero !== ultimaConsulta) {
            ultimaConsulta = numero;
            resolverDocumento(numero);
        }
    });

    informacion?.addEventListener("input", () => sincronizar());

    btnAccion?.addEventListener("click", async () => {
        if (modoActual() !== "con_documento") return;
        if (!iconoSave?.classList.contains("d-none")) {
            const numero = documento.value.trim();
            if (!numero || !razon.value.trim()) {
                Swal.fire("Datos incompletos", "Primero consulta un DNI o RUC válido.", "warning");
                return;
            }
            try {
                const response = await fetch("/guardar-cliente", {
                    method: "POST",
                    headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": window.document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ dni_ruc: numero, razon_social: razon.value.trim(), direccion: direccion.value.trim() })
                });
                const data = await response.json();
                if (!response.ok || !data.exito) throw new Error(data.mensaje || "No se pudo guardar el cliente.");
                window.estadoCliente = "ok";
                iconoGuardar(false);
                estado.textContent = "Cliente guardado";
                estado.className = "text-success small mb-1";
                sincronizar(clienteBase({ id: data.cliente?.id || null, documento: numero, razon: razon.value.trim(), direccion: direccion.value.trim() }));
                Swal.fire({ icon: "success", title: "Cliente guardado", timer: 1400, showConfirmButton: false });
            } catch (error) {
                Swal.fire("No se pudo guardar", error.message, "error");
            }
            return;
        }
        modalCliente?.show();
    });

    aplicarFlujo(false);
});
