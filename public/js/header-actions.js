document.addEventListener("DOMContentLoaded", () => {
  const btnMore = document.getElementById("btnHeaderMore");
  const panel = document.getElementById("headerMobilePanel");
  const overlay = document.getElementById("headerMobileOverlay");

  if (!btnMore || !panel) return;

  let desktopActions = null;
  let placeholder = null;
  let moved = false;
  let detachedPanel = null;
  let detachedPlaceholder = null;

  function findDesktopNodes() {
    if (!moved) {
      desktopActions = document.querySelector("#header .header-right-actions");
    }
  }

  function hasActions() {
    findDesktopNodes();
    return Boolean(desktopActions?.children.length);
  }

  function addMobileLabels() {
    if (!desktopActions) return;

    desktopActions.querySelectorAll("button, a").forEach(action => {
      if (action.querySelector(".btn-text, .header-mobile-action-label")) return;

      const labels = {
        "btn-ordenar": "Ordenar productos",
        "btn-pos-espera": "Ventas en espera"
      };
      const text = labels[action.id]
        || action.getAttribute("aria-label")
        || action.getAttribute("title")
        || action.dataset.tooltip;

      if (!text) return;
      const label = document.createElement("span");
      label.className = "header-mobile-action-label";
      label.textContent = text;
      action.appendChild(label);
    });
  }

  function moveActionsToMobile() {
    findDesktopNodes();
    if (!desktopActions) return;

    if (!moved) {
      placeholder = document.createComment("header-actions-placeholder");
      desktopActions.parentNode.insertBefore(placeholder, desktopActions);
      panel.replaceChildren(desktopActions);
      moved = true;
    }
    addMobileLabels();
  }

  function restoreActionsToDesktop() {
    if (!moved || !placeholder?.parentNode || !desktopActions) return;
    placeholder.parentNode.insertBefore(desktopActions, placeholder);
    placeholder.remove();
    placeholder = null;
    moved = false;
  }

  function restoreDetachedPanel() {
    if (!detachedPanel || !detachedPlaceholder?.parentNode) return;
    detachedPanel.classList.remove("mobile-detached-action-panel", "show");
    detachedPanel.classList.add("d-none");
    detachedPlaceholder.parentNode.insertBefore(detachedPanel, detachedPlaceholder);
    detachedPlaceholder.remove();
    detachedPlaceholder = null;
    detachedPanel = null;
  }

  function isMobile() {
    return window.matchMedia("(max-width: 768px)").matches;
  }

  function positionPanel() {
    const rect = btnMore.getBoundingClientRect();
    panel.style.top = `${Math.round(rect.bottom + 8)}px`;
    // El botón de tres puntos no está pegado al borde derecho porque después
    // están notificaciones, tema y usuario. Si alineamos el panel con el botón,
    // un dropdown de 300 px puede salir por el lado izquierdo en pantallas
    // estrechas. Se ancla al viewport para mantenerlo siempre completo.
    panel.style.right = "12px";
    panel.style.left = "auto";
  }

  function openPanel() {
    if (!isMobile() || !hasActions()) return;
    restoreDetachedPanel();
    moveActionsToMobile();
    positionPanel();
    panel.classList.add("show");
    btnMore.setAttribute("aria-expanded", "true");
  }

  function closePanel() {
    panel.classList.remove("show");
    btnMore.setAttribute("aria-expanded", "false");
  }

  function updateMoreButton() {
    const visible = isMobile() && hasActions();
    btnMore.hidden = !visible;
    btnMore.disabled = !visible;
    if (!visible) closePanel();
  }

  function detachSecondaryPanel(action) {
    if (action.id !== "btn-pos-espera") return;
    const waitingPanel = action.closest(".pos-espera-wrapper")?.querySelector(".pos-espera-panel");
    if (!waitingPanel) return;

    detachedPlaceholder = document.createComment("secondary-action-placeholder");
    waitingPanel.parentNode.insertBefore(detachedPlaceholder, waitingPanel);
    document.body.appendChild(waitingPanel);
    waitingPanel.classList.add("mobile-detached-action-panel");
    detachedPanel = waitingPanel;
  }

  // Toggle ⋮
  btnMore.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();

    if (panel.classList.contains("show")) closePanel();
    else openPanel();
  });

  document.addEventListener("click", (e) => {
    if (!panel.classList.contains("show")) return;
    if (panel.contains(e.target) || btnMore.contains(e.target)) return;
    closePanel();
  });

  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    closePanel();
    restoreDetachedPanel();
    btnMore.focus();
  });

  panel.addEventListener("click", (e) => {
    const action = e.target.closest("button, a");
    if (!action || action.disabled) return;

    // Dejamos que el manejador propio abra su modal o panel y luego cerramos
    // el dropdown de acciones para que no quede superpuesto.
    window.setTimeout(() => {
      detachSecondaryPanel(action);
      closePanel();
    }, 0);
  }, true);

  window.addEventListener("resize", () => {
    if (!isMobile()) {
      closePanel();
      restoreDetachedPanel();
      restoreActionsToDesktop();
    } else {
      if (panel.classList.contains("show")) {
        moveActionsToMobile();
        positionPanel();
      }
    }
    updateMoreButton();
  });

  btnMore.setAttribute("aria-controls", "headerMobilePanel");
  btnMore.setAttribute("aria-haspopup", "menu");
  btnMore.setAttribute("aria-expanded", "false");
  panel.setAttribute("role", "menu");
  overlay?.classList.remove("show");
  updateMoreButton();
});


document.addEventListener("DOMContentLoaded", () => {
  const root = document.documentElement;
  const toggle = document.getElementById("themeToggle");

  function applyTheme(theme) {
    root.setAttribute("data-theme", theme);
    document.body.classList.remove("theme-light", "theme-dark");
    document.body.classList.add(theme === "dark" ? "theme-dark" : "theme-light");
    localStorage.setItem("dizany-theme", theme);
    if (toggle) {
      const isDark = theme === "dark";
      toggle.setAttribute("aria-pressed", String(isDark));
      toggle.setAttribute("title", isDark ? "Cambiar a modo claro" : "Cambiar a modo oscuro");
    }
  }

  const savedTheme = localStorage.getItem("dizany-theme");
  const initialTheme = savedTheme === "dark" ? "dark" : "light";
  applyTheme(initialTheme);

  if (toggle) {
    toggle.addEventListener("click", () => {
      const current = root.getAttribute("data-theme") === "dark" ? "dark" : "light";
      applyTheme(current === "dark" ? "light" : "dark");
    });
  }
});
