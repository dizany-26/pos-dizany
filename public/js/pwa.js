(() => {
    if (!('serviceWorker' in navigator)) return;

    let installPrompt = window.__dizanyInstallPrompt || null;
    let installContainer = null;
    let installButton = null;
    const INSTALLED_KEY = 'dizany-pwa-installed';

    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    // No confiar en una marca antigua de localStorage: si el usuario desinstala
    // la PWA, Chrome no emite un evento que permita borrar esa marca.
    const installationHidden = () => isStandalone();

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
            installPrompt = installPrompt || window.__dizanyInstallPrompt;
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

    window.addEventListener('dizany-install-ready', () => {
        installPrompt = window.__dizanyInstallPrompt || installPrompt;
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
        installPrompt = installPrompt || window.__dizanyInstallPrompt;
        if (installPrompt) {
            ensureInstallButton();
            if (installContainer && !installationHidden()) installContainer.hidden = false;
        }
    });
})();
