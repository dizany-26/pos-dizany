(() => {
    if (!('serviceWorker' in navigator)) return;

    let installPrompt = window.__dizanyInstallPrompt || null;
    let installContainer = null;
    let installButton = null;
    const INSTALLED_KEY = 'dizany-catalog-pwa-installed';

    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    if (isStandalone()) {
        document.documentElement.classList.add('catalog-pwa-standalone');
    }

    // Una instalación anterior puede haber sido eliminada sin que la página lo
    // sepa. El modo standalone es la única señal fiable para ocultar el botón.
    const installationHidden = () => isStandalone();

    const hidePrompt = () => {
        if (installContainer) installContainer.hidden = true;
    };

    const ensureInstallPrompt = () => {
        if (installContainer || installationHidden()) return;

        installContainer = document.createElement('div');
        installContainer.className = 'pwa-install-prompt';
        installContainer.hidden = true;

        installButton = document.createElement('button');
        installButton.type = 'button';
        installButton.className = 'pwa-install-button';
        installButton.innerHTML = '<span aria-hidden="true">↓</span><span>Instalar Catálogo DIZANY</span>';
        installButton.addEventListener('click', async () => {
            installPrompt = installPrompt || window.__dizanyInstallPrompt;
            if (!installPrompt) return;
            installPrompt.prompt();
            const choice = await installPrompt.userChoice;
            installPrompt = null;
            if (choice.outcome === 'accepted') localStorage.setItem(INSTALLED_KEY, '1');
            hidePrompt();
        });

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'pwa-install-dismiss';
        closeButton.setAttribute('aria-label', 'Cerrar invitación para instalar el catálogo');
        closeButton.textContent = '×';
        closeButton.addEventListener('click', () => {
            hidePrompt();
        });

        installContainer.append(installButton, closeButton);
        document.body.appendChild(installContainer);
    };

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        installPrompt = event;
        ensureInstallPrompt();
        if (installContainer && !installationHidden()) installContainer.hidden = false;
    });

    window.addEventListener('dizany-install-ready', () => {
        installPrompt = window.__dizanyInstallPrompt || installPrompt;
        ensureInstallPrompt();
        if (installContainer && !installationHidden()) installContainer.hidden = false;
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        localStorage.setItem(INSTALLED_KEY, '1');
        hidePrompt();
    });

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(error => {
            console.error('No se pudo activar la PWA del catálogo DIZANY.', error);
        });
        installPrompt = installPrompt || window.__dizanyInstallPrompt;
        if (installPrompt) {
            ensureInstallPrompt();
            if (installContainer && !installationHidden()) installContainer.hidden = false;
        }
    });
})();
