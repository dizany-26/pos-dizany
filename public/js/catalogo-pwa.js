(() => {
    if (!('serviceWorker' in navigator)) return;

    let installPrompt = null;
    let installContainer = null;
    const INSTALLED_KEY = 'dizany-catalog-pwa-installed';
    const DISMISSED_KEY = 'dizany-catalog-pwa-install-dismissed-at';
    const DISMISS_TIME = 7 * 24 * 60 * 60 * 1000;

    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    if (isStandalone()) {
        document.documentElement.classList.add('catalog-pwa-standalone');
    }

    const installationHidden = () => {
        if (isStandalone() || localStorage.getItem(INSTALLED_KEY) === '1') return true;
        const dismissedAt = Number(localStorage.getItem(DISMISSED_KEY) || 0);
        return dismissedAt > 0 && Date.now() - dismissedAt < DISMISS_TIME;
    };

    const hidePrompt = () => {
        if (installContainer) installContainer.hidden = true;
    };

    const ensureInstallPrompt = () => {
        if (installContainer || installationHidden()) return;

        installContainer = document.createElement('div');
        installContainer.className = 'pwa-install-prompt';
        installContainer.hidden = true;

        const installButton = document.createElement('button');
        installButton.type = 'button';
        installButton.className = 'pwa-install-button';
        installButton.innerHTML = '<span aria-hidden="true">↓</span><span>Instalar Catálogo DIZANY</span>';
        installButton.addEventListener('click', async () => {
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
            localStorage.setItem(DISMISSED_KEY, String(Date.now()));
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

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        localStorage.setItem(INSTALLED_KEY, '1');
        hidePrompt();
    });

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(error => {
            console.error('No se pudo activar la PWA del catálogo DIZANY.', error);
        });
    });
})();
