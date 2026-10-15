// Navegación opcional por teclado para el POS. Esta capa reutiliza los clics,
// cambios y validaciones existentes; no registra ventas ni modifica cálculos.
(function () {
    "use strict";

    let productoActivo = -1;
    let carritoActivo = -1;

    const $ = (selector, contexto = document) => contexto.querySelector(selector);
    const $$ = (selector, contexto = document) => Array.from(contexto.querySelectorAll(selector));
    const visible = elemento => Boolean(
        elemento &&
        !elemento.hidden &&
        elemento.getClientRects().length &&
        getComputedStyle(elemento).display !== "none" &&
        getComputedStyle(elemento).visibility !== "hidden"
    );
    const pasoActivo = () => $(".step-panel.is-active")?.id || "step-1";
    const escribiendo = elemento => Boolean(
        elemento?.matches?.("input, textarea, select, [contenteditable='true']")
    );
    const modalVisible = () => $$(".modal.show, .swal2-container").some(visible);

    function enfocar(elemento, seleccionar = false) {
        if (!visible(elemento)) return false;
        elemento.focus({ preventScroll: true });
        if (seleccionar && typeof elemento.select === "function") elemento.select();
        elemento.scrollIntoView({ block: "nearest", inline: "nearest" });
        return true;
    }

    function tarjetasProducto() {
        return $$("#resultados-busqueda .product-card.agregar-carrito:not(.agotado)");
    }

    function marcarProducto(indice) {
        const tarjetas = tarjetasProducto();
        if (!tarjetas.length) return;
        productoActivo = Math.max(0, Math.min(indice, tarjetas.length - 1));
        tarjetas.forEach((tarjeta, i) => {
            const activa = i === productoActivo;
            tarjeta.classList.toggle("keyboard-active", activa);
            tarjeta.tabIndex = activa ? 0 : -1;
            tarjeta.setAttribute("role", "button");
            tarjeta.setAttribute("aria-label", `Agregar ${tarjeta.querySelector(".product-name")?.textContent?.trim() || "producto"}`);
        });
        enfocar(tarjetas[productoActivo]);
    }

    function limpiarProductoActivo() {
        productoActivo = -1;
        tarjetasProducto().forEach(tarjeta => tarjeta.classList.remove("keyboard-active"));
    }

    function itemsCarrito() {
        return $$("#carrito-lista .carrito-item");
    }

    function marcarCarrito(indice) {
        const items = itemsCarrito();
        if (!items.length) return false;
        carritoActivo = Math.max(0, Math.min(indice, items.length - 1));
        items.forEach((item, i) => {
            const activo = i === carritoActivo;
            item.classList.toggle("keyboard-active", activo);
            item.tabIndex = activo ? 0 : -1;
        });
        enfocar(items[carritoActivo]);
        return true;
    }

    function prepararProductos() {
        const tarjetas = tarjetasProducto();
        tarjetas.forEach((tarjeta, indice) => {
            tarjeta.tabIndex = indice === productoActivo ? 0 : -1;
            tarjeta.setAttribute("role", "button");
        });
        if (productoActivo >= tarjetas.length) productoActivo = tarjetas.length - 1;
    }

    function prepararCarrito() {
        const items = itemsCarrito();
        if (carritoActivo >= items.length) carritoActivo = items.length - 1;
        items.forEach((item, indice) => {
            item.tabIndex = indice === carritoActivo ? 0 : -1;
            item.classList.toggle("keyboard-active", indice === carritoActivo && carritoActivo >= 0);
        });
    }

    function metodosVisibles() {
        return $$(".metodo-pago-item").filter(visible);
    }

    function prepararMetodos() {
        const metodos = metodosVisibles();
        const activo = metodos.findIndex(item => item.classList.contains("active"));
        metodos.forEach((item, indice) => {
            item.setAttribute("role", "radio");
            item.setAttribute("aria-checked", item.classList.contains("active") ? "true" : "false");
            item.tabIndex = indice === (activo >= 0 ? activo : 0) ? 0 : -1;
        });
    }

    function prepararOrdenFoco() {
        ["vuelto-total-venta", "vuelto-mostrar"].forEach(id => {
            const campo = document.getElementById(id);
            if (campo) campo.tabIndex = -1;
        });
        ["btn-volver-step1", "btn-volver-step2"].forEach(id => {
            const boton = document.getElementById(id);
            if (boton) boton.tabIndex = -1;
        });
        prepararProductos();
        prepararCarrito();
        prepararMetodos();
    }

    function enfocarPaso() {
        window.setTimeout(() => {
            const paso = pasoActivo();
            if (paso === "step-1") {
                enfocar(document.getElementById("buscar_producto"), true);
                return;
            }
            if (paso === "step-2") {
                enfocar(document.getElementById("tipo_comprobante"));
                return;
            }

            const mixto = document.getElementById("metodo_pago")?.value === "mixto";
            if (mixto) {
                enfocar($$(".pago-mixto-monto").find(visible));
                return;
            }

            if (!enfocar(document.getElementById("vuelto-paga"), true)) {
                enfocar(document.getElementById("credito-vencimiento"));
            }
        }, 80);
    }

    function seleccionarMetodo(valor) {
        const item = $(`.metodo-pago-item[data-value="${valor}"]`);
        if (!visible(item)) return false;
        item.click();
        prepararMetodos();
        enfocar(item);
        return true;
    }

    function seleccionarValor(select, valor) {
        if (!select || !Array.from(select.options).some(opcion => opcion.value === valor)) return false;
        select.value = valor;
        select.dispatchEvent(new Event("change", { bubbles: true }));
        enfocar(select);
        return true;
    }

    function ayuda() {
        if (!window.Swal) return;
        Swal.fire({
            title: "Atajos de venta",
            width: 620,
            html: `<div class="text-start small keyboard-help-grid">
                <strong>Generales</strong><span>F2 buscar · F3 carrito · F4 cliente · F8 continuar · F9 confirmar · Esc volver</span>
                <strong>Productos</strong><span>↑/↓ seleccionar · Enter agregar</span>
                <strong>Carrito</strong><span>↑/↓ recorrer · +/− cantidad · P presentación · D descuento · Supr eliminar</span>
                <strong>Venta</strong><span>B boleta · N nota · F factura · 1 pagado · 2 fiado · 3 crédito</span>
                <strong>Pago</strong><span>E efectivo · Y Yape · P Plin · T tarjeta · R transferencia · M mixto · O otro</span>
                <strong>Formato</strong><span>A A4 · 8 ticket 80 mm · 5 ticket 58 mm</span>
            </div>`,
            confirmButtonText: "Cerrar"
        });
    }

    function manejarF8(evento) {
        evento.preventDefault();
        const paso = pasoActivo();
        if (paso === "step-1") {
            document.getElementById("btn-ir-step2")?.click();
        } else if (paso === "step-2") {
            const continuar = document.getElementById("btn-ir-step3");
            const confirmarDirecto = document.getElementById("btn-confirmar-venta-directo");
            if (visible(continuar)) continuar.click();
            else enfocar(confirmarDirecto);
        }
    }

    function manejarF9(evento) {
        evento.preventDefault();
        const boton = pasoActivo() === "step-3"
            ? document.getElementById("btn-confirmar-venta")
            : document.getElementById("btn-confirmar-venta-directo");
        if (visible(boton) && !boton.disabled) boton.click();
    }

    function manejarCarrito(evento) {
        const item = evento.target.closest?.(".carrito-item.keyboard-active");
        if (!item || escribiendo(evento.target)) return false;
        const items = itemsCarrito();
        const indice = items.indexOf(item);

        if (evento.key === "ArrowDown" || evento.key === "ArrowUp") {
            evento.preventDefault();
            marcarCarrito(indice + (evento.key === "ArrowDown" ? 1 : -1));
            return true;
        }
        if (evento.key === "+" || evento.key === "Add") {
            evento.preventDefault();
            item.querySelector(".btn-sumar")?.click();
            return true;
        }
        if (evento.key === "-" || evento.key === "Subtract") {
            evento.preventDefault();
            item.querySelector(".btn-restar")?.click();
            return true;
        }
        if (evento.key === "Delete") {
            evento.preventDefault();
            item.querySelector(".eliminar-item")?.click();
            return true;
        }
        if (evento.key.toLowerCase() === "d") {
            evento.preventDefault();
            item.querySelector(".btn-descuento")?.click();
            return true;
        }
        if (evento.key.toLowerCase() === "p") {
            const select = item.querySelector("select.tipo-venta");
            if (!select || select.options.length < 2) return true;
            evento.preventDefault();
            select.selectedIndex = (select.selectedIndex + 1) % select.options.length;
            select.dispatchEvent(new Event("change", { bubbles: true }));
            return true;
        }
        return false;
    }

    document.addEventListener("keydown", evento => {
        const tecla = evento.key;
        const foco = document.activeElement;
        const ventaExitosa = document.getElementById("modalVentaExitosa")?.classList.contains("show");

        if (ventaExitosa) {
            if (!escribiendo(foco) && tecla.toLowerCase() === "i") {
                evento.preventDefault();
                document.getElementById("btnImprimir")?.click();
            }
            return;
        }

        if (tecla === "F1") {
            evento.preventDefault();
            if (!modalVisible()) ayuda();
            return;
        }
        if (modalVisible()) return;
        if (tecla === "F2") {
            evento.preventDefault();
            limpiarProductoActivo();
            enfocar(document.getElementById("buscar_producto"), true);
            return;
        }
        if (tecla === "F3") {
            evento.preventDefault();
            marcarCarrito(Math.max(0, carritoActivo));
            return;
        }
        if (tecla === "F4") {
            evento.preventDefault();
            if (pasoActivo() === "step-2") {
                document.getElementById("cliente-con-documento")?.click();
                enfocar(document.getElementById("documento"), true);
            }
            return;
        }
        if (tecla === "F8") return manejarF8(evento);
        if (tecla === "F9") return manejarF9(evento);

        if (manejarCarrito(evento)) return;

        if (foco?.id === "buscar_producto") {
            if (tecla === "ArrowDown" || tecla === "ArrowUp") {
                evento.preventDefault();
                evento.stopImmediatePropagation();
                marcarProducto(tecla === "ArrowDown" ? 0 : tarjetasProducto().length - 1);
            }
            return;
        }

        const tarjeta = foco?.closest?.(".product-card.agregar-carrito");
        if (tarjeta) {
            const tarjetas = tarjetasProducto();
            const indice = tarjetas.indexOf(tarjeta);
            if (tecla === "ArrowDown" || tecla === "ArrowRight" || tecla === "ArrowUp" || tecla === "ArrowLeft") {
                evento.preventDefault();
                const avance = tecla === "ArrowDown" || tecla === "ArrowRight" ? 1 : -1;
                marcarProducto(indice + avance);
            } else if (tecla === "Enter" || tecla === " ") {
                evento.preventDefault();
                tarjeta.click();
                limpiarProductoActivo();
            }
            return;
        }

        const metodo = foco?.closest?.(".metodo-pago-item");
        if (metodo && !escribiendo(foco)) {
            const metodos = metodosVisibles();
            const indice = metodos.indexOf(metodo);
            if (["ArrowLeft", "ArrowUp", "ArrowRight", "ArrowDown"].includes(tecla)) {
                evento.preventDefault();
                const avance = tecla === "ArrowRight" || tecla === "ArrowDown" ? 1 : -1;
                const siguiente = (indice + avance + metodos.length) % metodos.length;
                metodos.forEach((item, i) => item.tabIndex = i === siguiente ? 0 : -1);
                enfocar(metodos[siguiente]);
            } else if (tecla === "Enter" || tecla === " ") {
                evento.preventDefault();
                metodo.click();
                prepararMetodos();
            }
            return;
        }

        if (escribiendo(foco)) return;

        if (tecla === "Escape") {
            if (pasoActivo() === "step-3") document.getElementById("btn-volver-step2")?.click();
            else if (pasoActivo() === "step-2") document.getElementById("btn-volver-step1")?.click();
            return;
        }

        if (pasoActivo() !== "step-2") return;
        const key = tecla.toLowerCase();
        const comprobantes = { b: "boleta", n: "nota_venta", f: "factura" };
        const condiciones = { "1": "pagado", "2": "pendiente", "3": "credito" };
        const pagos = { e: "efectivo", y: "yape", p: "plin", t: "tarjeta", r: "transferencia", m: "mixto", o: "otro" };
        const formatos = { a: "a4", "8": "ticket_80", "5": "ticket_58" };

        if (comprobantes[key]) {
            evento.preventDefault();
            seleccionarValor(document.getElementById("tipo_comprobante"), comprobantes[key]);
        } else if (condiciones[key]) {
            evento.preventDefault();
            seleccionarValor(document.getElementById("estado_pago"), condiciones[key]);
        } else if (pagos[key]) {
            evento.preventDefault();
            seleccionarMetodo(pagos[key]);
        } else if (formatos[key]) {
            evento.preventDefault();
            seleccionarValor(document.getElementById("formato_pdf"), formatos[key]);
        } else if (key === "c") {
            evento.preventDefault();
            document.getElementById("cliente-con-documento")?.click();
            enfocar(document.getElementById("documento"), true);
        } else if (key === "s") {
            evento.preventDefault();
            document.getElementById("cliente-sin-documento")?.click();
        }
    }, true);

    document.addEventListener("pointerdown", evento => {
        const item = evento.target.closest?.(".carrito-item");
        if (item) marcarCarrito(itemsCarrito().indexOf(item));
    });

    document.addEventListener("DOMContentLoaded", () => {
        prepararOrdenFoco();

        const observer = new MutationObserver(() => prepararOrdenFoco());
        [document.getElementById("resultados-busqueda"), document.getElementById("carrito-lista"), document.querySelector(".venta-steps")]
            .filter(Boolean)
            .forEach(elemento => observer.observe(elemento, { childList: true, subtree: true, attributes: true, attributeFilter: ["class", "style"] }));

        document.querySelectorAll(".step-panel").forEach(panel => {
            new MutationObserver(cambios => {
                if (cambios.some(cambio => cambio.attributeName === "class") && panel.classList.contains("is-active")) {
                    prepararOrdenFoco();
                    enfocarPaso();
                }
            }).observe(panel, { attributes: true, attributeFilter: ["class"] });
        });

        const modalVenta = document.getElementById("modalVentaExitosa");
        modalVenta?.addEventListener("shown.bs.modal", () => enfocar(document.getElementById("btnNuevaVenta")));

        ["btn-ir-step2", "btn-ir-step3"].forEach(id => {
            const boton = document.getElementById(id);
            if (boton) boton.title = `${boton.title ? boton.title + " · " : ""}Atajo F8`;
        });
        ["btn-confirmar-venta", "btn-confirmar-venta-directo"].forEach(id => {
            const boton = document.getElementById(id);
            if (boton) boton.title = `${boton.title ? boton.title + " · " : ""}Atajo F9`;
        });
    });
})();
