(() => {
    if (!('serviceWorker' in navigator)) return;

    let installPrompt = null;
    let installContainer = null;
    let installButton = null;
    const INSTALLED_KEY = 'dizany-pwa-installed';
    const DISMISSED_KEY = 'dizany-pwa-install-dismissed-at';
    const DISMISS_TIME = 7 * 24 * 60 * 60 * 1000;

    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    const installationHidden = () => {
        if (isStandalone() || localStorage.getItem(INSTALLED_KEY) === '1') return true;

        const dismissedAt = Number(localStorage.getItem(DISMISSED_KEY) || 0);
        return dismissedAt > 0 && Date.now() - dismissedAt < DISMISS_TIME;
    };

    const hideInstallPrompt = () => {
        if (installContainer) installContainer.hidden = true;
    };

    const ensureInstallButton = () => {
        if (installButton || installationHidden()) return installButton;

        installContainer = document.createElement('div');
        installContainer.className = 'pwa-install-prompt';
        installContainer.hidden = true;

        installButton = document.createElement('button');
        installButton.type = 'button';
        installButton.className = 'pwa-install-button';
        installButton.innerHTML = '<span aria-hidden="true">↓</span><span>Instalar DIZANY</span>';
        installButton.addEventListener('click', async () => {
            if (!installPrompt) return;
            installPrompt.prompt();
            const choice = await installPrompt.userChoice;
            installPrompt = null;
            if (choice.outcome === 'accepted') {
                localStorage.setItem(INSTALLED_KEY, '1');
            }
            hideInstallPrompt();
        });

        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'pwa-install-dismiss';
        closeButton.setAttribute('aria-label', 'Cerrar invitación para instalar DIZANY');
        closeButton.textContent = '×';
        closeButton.addEventListener('click', () => {
            localStorage.setItem(DISMISSED_KEY, String(Date.now()));
            hideInstallPrompt();
        });

        installContainer.append(installButton, closeButton);
        document.body.appendChild(installContainer);
        return installButton;
    };

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        installPrompt = event;
        ensureInstallButton();
        if (installContainer && !installationHidden()) installContainer.hidden = false;
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        localStorage.setItem(INSTALLED_KEY, '1');
        hideInstallPrompt();
    });

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(error => {
            console.error('No se pudo activar la PWA de DIZANY.', error);
        });
    });
})();
