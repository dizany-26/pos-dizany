(() => {
    if (!('serviceWorker' in navigator)) return;

    let installPrompt = null;
    let installButton = null;

    const isStandalone = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;

    const ensureInstallButton = () => {
        if (installButton || isStandalone()) return installButton;

        installButton = document.createElement('button');
        installButton.type = 'button';
        installButton.className = 'pwa-install-button';
        installButton.innerHTML = '<span aria-hidden="true">↓</span><span>Instalar DIZANY</span>';
        installButton.hidden = true;
        installButton.addEventListener('click', async () => {
            if (!installPrompt) return;
            installPrompt.prompt();
            await installPrompt.userChoice;
            installPrompt = null;
            installButton.hidden = true;
        });
        document.body.appendChild(installButton);
        return installButton;
    };

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        installPrompt = event;
        const button = ensureInstallButton();
        if (button) button.hidden = false;
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        if (installButton) installButton.hidden = true;
    });

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(error => {
            console.error('No se pudo activar la PWA de DIZANY.', error);
        });
    });
})();
