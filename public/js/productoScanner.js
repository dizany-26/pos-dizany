document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('codigo_barras');
    const btnEscanear = document.getElementById('btnEscanearCodigo');
    const modalElement = document.getElementById('modalEscanearCodigoBarras');
    const readerElementId = 'barcode-reader';
    const status = document.getElementById('barcodeScannerStatus');
    const btnCerrar = document.getElementById('btnCerrarEscaner');
    const fallbackActions = document.getElementById('barcodeFallbackActions');
    const btnEscanerExterno = document.getElementById('btnEscanerExterno');
    const btnTorch = document.getElementById('btnBarcodeTorch');
    const btnZoom = document.getElementById('btnBarcodeZoom');
    const nextInput = document.getElementById('nombre');

    if (!input || !btnEscanear || !modalElement || !status || !btnCerrar || !fallbackActions || !btnEscanerExterno) {
        return;
    }

    const modal = window.bootstrap ? new bootstrap.Modal(modalElement) : null;
    let html5QrCode = null;
    let scannerRunning = false;
    let torchEnabled = false;
    let zoomEnabled = false;
    let beepAudioContext = null;
    let torchLikelyAvailable = false;

    const isMobileDevice = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || '');

    const setStatus = (message, type = 'info') => {
        status.textContent = message;
        status.className = `barcode-scanner-status barcode-scanner-status-${type}`;
    };

    const toggleFallback = (visible) => {
        fallbackActions.classList.toggle('d-none', !visible);
    };

    const setToolVisibility = (button, visible) => {
        if (!button) {
            return;
        }

        button.classList.toggle('d-none', !visible);
    };

    const setToolActive = (button, active) => {
        if (!button) {
            return;
        }

        button.classList.toggle('is-active', active);
    };

    const focusNextInput = () => {
        if (!nextInput) {
            return;
        }

        window.setTimeout(() => {
            nextInput.focus();
            nextInput.select?.();
        }, 150);
    };

    const playSuccessFeedback = async () => {
        if ('vibrate' in navigator) {
            navigator.vibrate([120, 60, 120]);
        }

        try {
            const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextCtor) {
                return;
            }

            beepAudioContext = beepAudioContext || new AudioContextCtor();
            if (beepAudioContext.state === 'suspended') {
                await beepAudioContext.resume();
            }

            const oscillator = beepAudioContext.createOscillator();
            const gainNode = beepAudioContext.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.value = 1046;
            gainNode.gain.setValueAtTime(0.0001, beepAudioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.18, beepAudioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, beepAudioContext.currentTime + 0.18);

            oscillator.connect(gainNode);
            gainNode.connect(beepAudioContext.destination);
            oscillator.start();
            oscillator.stop(beepAudioContext.currentTime + 0.18);
        } catch (error) {
            console.warn('No se pudo reproducir el sonido de confirmación:', error);
        }
    };

    const fillBarcode = (decodedText) => {
        input.value = decodedText.trim();
        input.dispatchEvent(new Event('input', { bubbles: true }));
        focusNextInput();
    };

    const handleBarcodeDetected = async (decodedText) => {
        fillBarcode(decodedText);
        await playSuccessFeedback();
        setStatus('Código detectado correctamente. Cerrando escáner…', 'success');
        await stopScanner();
        modal?.hide();
    };

    const returnedBarcode = new URLSearchParams(window.location.search).get('barcode');
    if (returnedBarcode) {
        fillBarcode(returnedBarcode);
        const cleanUrl = new URL(window.location.href);
        cleanUrl.searchParams.delete('barcode');
        window.history.replaceState({}, '', cleanUrl.toString());
    }

    const stopScanner = async () => {
        if (!html5QrCode || !scannerRunning) {
            return;
        }

        try {
            await html5QrCode.stop();
        } catch (error) {
            console.warn('No se pudo detener el escáner correctamente:', error);
        }

        try {
            await html5QrCode.clear();
        } catch (error) {
            console.warn('No se pudo limpiar el escáner:', error);
        }

        torchEnabled = false;
        zoomEnabled = false;
        torchLikelyAvailable = false;
        setToolActive(btnTorch, false);
        setToolActive(btnZoom, false);
        setToolVisibility(btnTorch, false);
        setToolVisibility(btnZoom, false);
        scannerRunning = false;
    };

    const getTrackCapabilities = () => {
        if (!html5QrCode || !scannerRunning || typeof html5QrCode.getRunningTrackCapabilities !== 'function') {
            return {};
        }

        try {
            return html5QrCode.getRunningTrackCapabilities() || {};
        } catch (error) {
            console.warn('No se pudieron obtener las capacidades de la cámara:', error);
            return {};
        }
    };

    const syncCameraTools = () => {
        const capabilities = getTrackCapabilities();
        const supportsTorch = capabilities.torch || (Array.isArray(capabilities.fillLightMode) && capabilities.fillLightMode.includes('flash'));
        const supportsZoom = typeof capabilities.zoom !== 'undefined';

        torchLikelyAvailable = Boolean(supportsTorch);

        setToolVisibility(btnTorch, Boolean(supportsTorch) || isMobileDevice);
        setToolVisibility(btnZoom, Boolean(supportsZoom));
    };

    const applyVideoConstraints = async (constraints) => {
        if (!html5QrCode || typeof html5QrCode.applyVideoConstraints !== 'function') {
            return false;
        }

        try {
            await html5QrCode.applyVideoConstraints(constraints);
            return true;
        } catch (error) {
            console.warn('No se pudieron aplicar restricciones de video:', constraints, error);
            return false;
        }
    };

    const toggleTorch = async () => {
        if (!scannerRunning) {
            return;
        }

        const nextTorchState = !torchEnabled;
        const applied = await applyVideoConstraints({ advanced: [{ torch: nextTorchState }] });
        if (applied) {
            torchEnabled = nextTorchState;
            setToolActive(btnTorch, torchEnabled);
            setStatus(torchEnabled ? 'Linterna activada.' : 'Linterna desactivada.', 'info');
            return;
        }

        if (isMobileDevice) {
            setStatus(
                torchLikelyAvailable
                    ? 'No se pudo cambiar la linterna en este momento. Intenta reabrir la cámara.'
                    : 'Tu cámara o navegador no permite controlar la linterna desde la web.',
                'error'
            );
        }
    };

    const toggleZoom = async () => {
        if (!scannerRunning) {
            return;
        }

        const capabilities = getTrackCapabilities();
        if (typeof capabilities.zoom === 'undefined') {
            return;
        }

        const minZoom = typeof capabilities.zoom.min === 'number' ? capabilities.zoom.min : 1;
        const maxZoom = typeof capabilities.zoom.max === 'number' ? capabilities.zoom.max : 3;
        const macroZoom = Math.min(maxZoom, Math.max(minZoom, 2));
        const normalZoom = Math.max(minZoom, 1);
        const nextZoomEnabled = !zoomEnabled;
        const applied = await applyVideoConstraints({
            advanced: [{ zoom: nextZoomEnabled ? macroZoom : normalZoom }],
        });

        if (applied) {
            zoomEnabled = nextZoomEnabled;
            setToolActive(btnZoom, zoomEnabled);
            setStatus(
                zoomEnabled
                    ? 'Modo enfoque activado para códigos pequeños.'
                    : 'Modo enfoque desactivado.',
                'info'
            );
        }
    };

    const startScanner = async () => {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('No se pudo cargar el escáner. Revisa tu conexión e inténtalo de nuevo.', 'error');
            toggleFallback(true);
            return;
        }

        if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            setStatus('Tu navegador por IP/HTTP no permite cámara en vivo. Usa Escanear con app externa.', 'error');
            toggleFallback(true);
            return;
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode(readerElementId);
        }

        toggleFallback(false);
        setStatus('Solicitando acceso a la cámara…', 'info');

        const config = {
            fps: window.innerWidth < 768 ? 14 : 12,
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                const width = Math.min(viewfinderWidth * 0.72, 320);
                const height = Math.min(Math.max(width * 0.42, 120), viewfinderHeight * 0.36);
                return {
                    width: Math.round(width),
                    height: Math.round(height),
                };
            },
            aspectRatio: 1.7778,
            rememberLastUsedCamera: true,
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true,
            },
        };

        if (typeof Html5QrcodeSupportedFormats !== 'undefined') {
            config.formatsToSupport = [
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.UPC_A,
                Html5QrcodeSupportedFormats.UPC_E,
                Html5QrcodeSupportedFormats.ITF,
            ];
        }

        const onScanSuccess = async (decodedText) => {
            await handleBarcodeDetected(decodedText);
        };
        const onScanError = () => {};

        const cameraCandidates = [
            { facingMode: { exact: 'environment' } },
            { facingMode: 'environment' },
            { facingMode: 'user' },
        ];

        for (const cameraConfig of cameraCandidates) {
            try {
                await html5QrCode.start(cameraConfig, config, onScanSuccess, onScanError);
                scannerRunning = true;
                syncCameraTools();
                setStatus('Apunta la cámara al código de barras.', 'info');
                return;
            } catch (error) {
                console.warn('Intento de cámara fallido:', cameraConfig, error);
            }
        }

        try {
            const devices = await Html5Qrcode.getCameras();
            if (devices?.length) {
                await html5QrCode.start(devices[0].id, config, onScanSuccess, onScanError);
                scannerRunning = true;
                syncCameraTools();
                setStatus('Apunta la cámara al código de barras.', 'info');
                return;
            }
        } catch (error) {
            console.warn('No se pudieron listar cámaras:', error);
        }

        setStatus('No se pudo iniciar la cámara en vivo. Usa Escanear con app externa o una pistola lectora en PC.', 'error');
        toggleFallback(true);
    };

    const launchExternalScanner = () => {
        const returnUrl = new URL(window.location.href);
        returnUrl.searchParams.set('barcode', '{CODE}');

        const scannerUrl = new URL('https://zxing.appspot.com/scan');
        scannerUrl.searchParams.set('ret', returnUrl.toString());
        scannerUrl.searchParams.set('SCAN_FORMATS', 'EAN_13,EAN_8,UPC_A,UPC_E,CODE_128,CODE_39,ITF');

        window.location.href = scannerUrl.toString();
    };

    btnEscanear.addEventListener('click', () => {
        modal?.show();
    });

    btnEscanerExterno.addEventListener('click', () => {
        launchExternalScanner();
    });

    btnTorch?.addEventListener('click', async () => {
        await toggleTorch();
    });

    btnZoom?.addEventListener('click', async () => {
        await toggleZoom();
    });

    btnCerrar.addEventListener('click', async () => {
        await stopScanner();
        modal?.hide();
    });

    modalElement.addEventListener('shown.bs.modal', () => {
        startScanner();
    });

    modalElement.addEventListener('hidden.bs.modal', async () => {
        await stopScanner();
        toggleFallback(false);
        setStatus('En móvil usa la cámara. En PC puedes usar una pistola lectora enfocando el campo.', 'info');
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            focusNextInput();
        }
    });
});
