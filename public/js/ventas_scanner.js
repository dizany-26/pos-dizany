document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('buscar_producto');
    const btnEscanear = document.getElementById('btnEscanearVenta');
    const modalElement = document.getElementById('modalEscanearVenta');
    const status = document.getElementById('ventaBarcodeScannerStatus');
    const btnCerrar = document.getElementById('btnCerrarEscanerVenta');
    const btnTorch = document.getElementById('btnVentaBarcodeTorch');
    const btnZoom = document.getElementById('btnVentaBarcodeZoom');

    if (!input || !btnEscanear || !modalElement || !status || !btnCerrar) {
        return;
    }

    const modal = window.bootstrap ? new bootstrap.Modal(modalElement) : null;
    const readerElementId = 'venta-barcode-reader';
    let html5QrCode = null;
    let scannerRunning = false;
    let torchEnabled = false;
    let zoomEnabled = false;
    let beepAudioContext = null;
    let isProcessingScan = false;
    let lastAcceptedScanAt = 0;
    let lastAcceptedCode = '';
    const GLOBAL_SCAN_THROTTLE_MS = 250;
    const SAME_CODE_COOLDOWN_MS = 1500;

    const setStatus = (message, type = 'info') => {
        status.textContent = message;
        status.className = `barcode-scanner-status barcode-scanner-status-${type}`;
    };

    const setToolVisibility = (button, visible) => {
        if (!button) return;
        button.classList.toggle('d-none', !visible);
    };

    const setToolActive = (button, active) => {
        if (!button) return;
        button.classList.toggle('is-active', active);
    };

    const normalizarEscaneo = (value) =>
        String(value || '')
            .replace(/[^0-9A-Za-z]/g, '')
            .trim();

    const playSuccessFeedback = async () => {
        if ('vibrate' in navigator) {
            navigator.vibrate(90);
        }

        try {
            const AudioContextCtor = window.AudioContext || window.webkitAudioContext;
            if (!AudioContextCtor) return;

            beepAudioContext = beepAudioContext || new AudioContextCtor();
            if (beepAudioContext.state === 'suspended') {
                await beepAudioContext.resume();
            }

            const oscillator = beepAudioContext.createOscillator();
            const gainNode = beepAudioContext.createGain();

            oscillator.type = 'triangle';
            oscillator.frequency.value = 1174;
            gainNode.gain.setValueAtTime(0.0001, beepAudioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.15, beepAudioContext.currentTime + 0.01);
            gainNode.gain.exponentialRampToValueAtTime(0.0001, beepAudioContext.currentTime + 0.12);

            oscillator.connect(gainNode);
            gainNode.connect(beepAudioContext.destination);
            oscillator.start();
            oscillator.stop(beepAudioContext.currentTime + 0.12);
        } catch (error) {
            console.warn('No se pudo reproducir el beep de éxito en ventas:', error);
        }
    };

    const getTrackCapabilities = () => {
        if (!html5QrCode || !scannerRunning || typeof html5QrCode.getRunningTrackCapabilities !== 'function') {
            return {};
        }

        try {
            return html5QrCode.getRunningTrackCapabilities() || {};
        } catch (error) {
            console.warn('No se pudieron obtener las capacidades de la cámara en ventas:', error);
            return {};
        }
    };

    const applyVideoConstraints = async (constraints) => {
        if (!html5QrCode || typeof html5QrCode.applyVideoConstraints !== 'function') {
            return false;
        }

        try {
            await html5QrCode.applyVideoConstraints(constraints);
            return true;
        } catch (error) {
            console.warn('No se pudieron aplicar restricciones de video en ventas:', constraints, error);
            return false;
        }
    };

    const enhanceVideoQuality = async () => {
        const capabilities = getTrackCapabilities();
        const advancedConstraints = [];

        if (Array.isArray(capabilities?.focusMode) && capabilities.focusMode.includes('continuous')) {
            advancedConstraints.push({ focusMode: 'continuous' });
        }

        if (Array.isArray(capabilities?.exposureMode) && capabilities.exposureMode.includes('continuous')) {
            advancedConstraints.push({ exposureMode: 'continuous' });
        }

        if (Array.isArray(capabilities?.whiteBalanceMode) && capabilities.whiteBalanceMode.includes('continuous')) {
            advancedConstraints.push({ whiteBalanceMode: 'continuous' });
        }

        if (typeof capabilities?.sharpness !== 'undefined' && typeof capabilities.sharpness.max === 'number') {
            advancedConstraints.push({ sharpness: capabilities.sharpness.max });
        }

        if (typeof capabilities?.contrast !== 'undefined' && typeof capabilities.contrast.max === 'number') {
            const boostedContrast = Math.max(
                capabilities.contrast.min ?? capabilities.contrast.max,
                capabilities.contrast.max * 0.85
            );
            advancedConstraints.push({ contrast: boostedContrast });
        }

        if (!advancedConstraints.length) return;

        for (const constraint of advancedConstraints) {
            await applyVideoConstraints({ advanced: [constraint] });
        }
    };

    const syncTools = () => {
        const capabilities = getTrackCapabilities();
        const supportsTorch = capabilities?.torch === true || (Array.isArray(capabilities?.fillLightMode) && capabilities.fillLightMode.includes('flash'));
        const supportsZoom = typeof capabilities?.zoom !== 'undefined';

        setToolVisibility(btnTorch, supportsTorch);
        setToolVisibility(btnZoom, supportsZoom);
    };

    const stopScanner = async () => {
        if (!html5QrCode || !scannerRunning) {
            setToolActive(btnTorch, false);
            setToolActive(btnZoom, false);
            setToolVisibility(btnTorch, false);
            setToolVisibility(btnZoom, false);
            return;
        }

        try {
            await html5QrCode.stop();
        } catch (error) {
            console.warn('No se pudo detener el escáner de ventas:', error);
        }

        try {
            await html5QrCode.clear();
        } catch (error) {
            console.warn('No se pudo limpiar el escáner de ventas:', error);
        }

        scannerRunning = false;
        torchEnabled = false;
        zoomEnabled = false;
        setToolActive(btnTorch, false);
        setToolActive(btnZoom, false);
        setToolVisibility(btnTorch, false);
        setToolVisibility(btnZoom, false);
    };

    const shouldIgnoreScan = (codigoNormalizado) => {
        const now = Date.now();
        const isSameCode = codigoNormalizado && codigoNormalizado === lastAcceptedCode;

        if ((now - lastAcceptedScanAt) < GLOBAL_SCAN_THROTTLE_MS) {
            return true;
        }

        if (isSameCode && (now - lastAcceptedScanAt) < SAME_CODE_COOLDOWN_MS) {
            return true;
        }

        return false;
    };

    const procesarCodigoDetectado = async (decodedText) => {
        const codigo = normalizarEscaneo(decodedText);
        if (isProcessingScan || shouldIgnoreScan(codigo)) {
            return;
        }

        isProcessingScan = true;
        lastAcceptedScanAt = Date.now();
        lastAcceptedCode = codigo;
        input.value = codigo || decodedText.trim();
        input.dispatchEvent(new Event('input', { bubbles: true }));

        try {
            const termino = codigo || decodedText;
            const resultado = await window.posResolverYAgregarProducto?.(termino, { render: true });

            if (resultado?.added) {
                await playSuccessFeedback();
                setStatus('Producto enviado al carrito.', 'success');
                await stopScanner();
                modal?.hide();
                return;
            }

            if (resultado?.reason === 'duplicate') {
                if (typeof window.mostrarAlerta === 'function') {
                    window.mostrarAlerta('Este producto ya está en la canasta.');
                }
                setStatus('Ese producto ya estaba en la lista.', 'info');
            } else {
                setStatus('Código leído. Revisa los resultados y presiona Enter si deseas agregarlo.', 'info');
            }
            await stopScanner();
            modal?.hide();
        } catch (error) {
            console.error(error);
            setStatus('No se pudo procesar el código escaneado.', 'error');
        } finally {
            isProcessingScan = false;
        }
    };

    const startScanner = async () => {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('No se pudo cargar el escáner.', 'error');
            return;
        }

        if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            setStatus('Tu navegador necesita HTTPS para usar cámara en vivo.', 'error');
            return;
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode(readerElementId);
        }

        const config = {
            fps: window.innerWidth < 768 ? 14 : 12,
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                const width = Math.min(viewfinderWidth * 0.9, 420);
                const height = Math.min(Math.max(width * 0.82, 210), viewfinderHeight * 0.72);
                return { width: Math.round(width), height: Math.round(height) };
            },
            aspectRatio: 1.7778,
            videoConstraints: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
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

        let devices = [];
        try {
            devices = await Html5Qrcode.getCameras();
        } catch (error) {
            console.warn('No se pudieron listar cámaras en ventas:', error);
        }

        const rearDeviceByLabel = devices.find((device) =>
            /back|rear|environment|tr[aá]s|posterior/i.test(device.label || '')
        );

        const cameraCandidates = [
            rearDeviceByLabel?.id,
            { facingMode: { exact: 'environment' } },
            { facingMode: 'environment' },
        ].filter(Boolean);

        for (const cameraConfig of cameraCandidates) {
            try {
                await html5QrCode.start(
                    cameraConfig,
                    config,
                    async (decodedText) => {
                        await procesarCodigoDetectado(decodedText);
                    },
                    () => {}
                );
                scannerRunning = true;
                syncTools();
                await enhanceVideoQuality();
                setStatus('Apunta la cámara al código de barras.', 'info');
                return;
            } catch (error) {
                console.warn('Intento de cámara fallido en ventas:', cameraConfig, error);
            }
        }

        setStatus('No se pudo iniciar la cámara trasera. Verifica permisos de cámara.', 'error');
    };

    btnEscanear.addEventListener('click', () => {
        modal?.show();
    });

    btnCerrar.addEventListener('click', async () => {
        await stopScanner();
        modal?.hide();
    });

    btnTorch?.addEventListener('click', async () => {
        const nextState = !torchEnabled;
        const applied = await applyVideoConstraints({ advanced: [{ torch: nextState }] });
        if (applied) {
            torchEnabled = nextState;
            setToolActive(btnTorch, torchEnabled);
        }
    });

    btnZoom?.addEventListener('click', async () => {
        const capabilities = getTrackCapabilities();
        if (typeof capabilities?.zoom === 'undefined') return;

        const minZoom = typeof capabilities.zoom.min === 'number' ? capabilities.zoom.min : 1;
        const maxZoom = typeof capabilities.zoom.max === 'number' ? capabilities.zoom.max : 3;
        const nextState = !zoomEnabled;
        const zoom = nextState ? Math.min(maxZoom, Math.max(minZoom, 1.5)) : Math.max(minZoom, 1);
        const applied = await applyVideoConstraints({ advanced: [{ zoom }] });
        if (applied) {
            zoomEnabled = nextState;
            setToolActive(btnZoom, zoomEnabled);
        }
    });

    modalElement.addEventListener('shown.bs.modal', () => {
        startScanner();
    });

    modalElement.addEventListener('hidden.bs.modal', async () => {
        await stopScanner();
        setStatus('Escanea un código para enviarlo directo al carrito.', 'info');
    });
});
