document.addEventListener("DOMContentLoaded", () => {
    const launcher = document.getElementById("btn-carrito-movil");
    const panel = document.querySelector(".treinta-col.derecha");
    const closeButton = document.getElementById("btn-cerrar-carrito-movil");
    const mobileQuery = window.matchMedia("(max-width: 991.98px)");

    if (!launcher || !panel || !closeButton) return;

    let lastFocusedElement = null;

    function openCart() {
        if (!mobileQuery.matches) return;

        lastFocusedElement = document.activeElement;
        panel.classList.add("is-mobile-cart-open");
        document.body.classList.add("mobile-cart-open");
        launcher.setAttribute("aria-expanded", "true");

        window.setTimeout(() => closeButton.focus(), 180);
    }

    function closeCart({ restoreFocus = true } = {}) {
        panel.classList.remove("is-mobile-cart-open");
        document.body.classList.remove("mobile-cart-open");
        launcher.setAttribute("aria-expanded", "false");

        if (restoreFocus && mobileQuery.matches) {
            window.setTimeout(() => {
                (lastFocusedElement instanceof HTMLElement ? lastFocusedElement : launcher).focus();
            }, 180);
        }
    }

    launcher.addEventListener("click", openCart);
    closeButton.addEventListener("click", () => closeCart());

    document.addEventListener("keydown", event => {
        if (event.key === "Escape" && panel.classList.contains("is-mobile-cart-open")) {
            closeCart();
        }
    });

    mobileQuery.addEventListener("change", event => {
        if (!event.matches) closeCart({ restoreFocus: false });
    });

    window.abrirCarritoMovil = openCart;
    window.cerrarCarritoMovil = () => closeCart({ restoreFocus: false });
});
