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
    let scanLock = false;
    let lastScanValue = '';
    let lastScanAt = 0;
    const SCAN_COOLDOWN_MS = 1500;
    let successAudio = null;
    let currentDevices = [];

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

    const playCartSuccessFeedback = async () => {
        try {
            if (!successAudio) {
                successAudio = new Audio('/sonidos/success.mp3');
                successAudio.preload = 'auto';
            }

            successAudio.currentTime = 0;
            await successAudio.play();
        } catch (error) {
            console.warn('No se pudo reproducir el sonido de éxito al agregar al carrito:', error);
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

    const getRunningTrackSettings = () => {
        if (!html5QrCode || !scannerRunning || typeof html5QrCode.getRunningTrackSettings !== 'function') {
            return {};
        }

        try {
            return html5QrCode.getRunningTrackSettings() || {};
        } catch (error) {
            console.warn('No se pudieron obtener settings de cámara en ventas:', error);
            return {};
        }
    };

    const chooseRearCamera = (devices = []) => {
        if (!devices.length) return null;

        const ranked = devices
            .map((device) => {
                const label = String(device.label || '').toLowerCase();
                let score = 0;
                if (/back|rear|environment|tr[aá]s|posterior/.test(label)) score += 200;
                if (/macro|close|closeup/.test(label)) score += 80;
                if (/front|user|selfie|frontal/.test(label)) score -= 250;
                if (/wide|ultra/.test(label)) score -= 30;
                return { device, score };
            })
            .sort((a, b) => b.score - a.score);

        return ranked[0]?.device || null;
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

    const syncTools = () => {
        const capabilities = getTrackCapabilities();
        const supportsTorch = capabilities?.torch === true || (Array.isArray(capabilities?.fillLightMode) && capabilities.fillLightMode.includes('flash'));
        const supportsZoom = typeof capabilities?.zoom !== 'undefined';

        setToolVisibility(btnTorch, supportsTorch);
        setToolVisibility(btnZoom, supportsZoom);
    };

    const applyFocusEnhancements = async () => {
        const capabilities = getTrackCapabilities();
        const advanced = [];

        if (Array.isArray(capabilities?.focusMode) && capabilities.focusMode.includes('continuous')) {
            advanced.push({ focusMode: 'continuous' });
        }

        if (typeof capabilities?.focusDistance !== 'undefined') {
            const min = typeof capabilities.focusDistance.min === 'number' ? capabilities.focusDistance.min : 0;
            const max = typeof capabilities.focusDistance.max === 'number' ? capabilities.focusDistance.max : 1;
            const closeFocus = Math.max(min, Math.min(max, min + (max - min) * 0.2));
            advanced.push({ focusDistance: closeFocus });
        }

        for (const setting of advanced) {
            await applyVideoConstraints({ advanced: [setting] });
        }
    };

    const ensureRearCameraActive = async () => {
        const settings = getRunningTrackSettings();
        const facingMode = String(settings?.facingMode || '').toLowerCase();
        const isFront = facingMode === 'user' || facingMode === 'front';

        if (!isFront) return;

        const preferredRear = chooseRearCamera(currentDevices);
        if (!preferredRear?.id) return;

        try {
            await stopScanner();
            await html5QrCode.start(
                preferredRear.id,
                {
                    fps: window.innerWidth < 768 ? 14 : 12,
                    qrbox: (w, h) => {
                        const width = Math.min(w * 0.9, 420);
                        const height = Math.min(Math.max(width * 0.82, 210), h * 0.72);
                        return { width: Math.round(width), height: Math.round(height) };
                    },
                    aspectRatio: 1.7778,
                    rememberLastUsedCamera: true,
                    videoConstraints: {
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        focusMode: 'continuous',
                    },
                },
                async (decodedText) => {
                    await procesarCodigoDetectado(decodedText);
                },
                () => {}
            );
            scannerRunning = true;
            syncTools();
            await applyFocusEnhancements();
            setStatus('Cámara trasera activada para escaneo.', 'info');
        } catch (error) {
            console.warn('No se pudo forzar cámara trasera en ventas:', error);
        }
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

    const procesarCodigoDetectado = async (decodedText) => {
        const now = Date.now();
        const codigoNormalizado = normalizarEscaneo(decodedText);

        if (
            scanLock ||
            (codigoNormalizado &&
                codigoNormalizado === lastScanValue &&
                now - lastScanAt < SCAN_COOLDOWN_MS)
        ) {
            return;
        }

        scanLock = true;
        lastScanValue = codigoNormalizado;
        lastScanAt = now;

        const codigo = normalizarEscaneo(decodedText);
        input.value = codigo || decodedText.trim();
        input.dispatchEvent(new Event('input', { bubbles: true }));

        try {
            const termino = codigo || decodedText;
            const resultado = await window.posResolverYAgregarProducto?.(termino, { render: true });

            if (resultado?.added) {
                await playCartSuccessFeedback();
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
            window.setTimeout(() => {
                scanLock = false;
            }, SCAN_COOLDOWN_MS);
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
            rememberLastUsedCamera: true,
            videoConstraints: {
                width: { ideal: 1920 },
                height: { ideal: 1080 },
                focusMode: 'continuous',
            },
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

        try {
            currentDevices = await Html5Qrcode.getCameras();
        } catch (error) {
            console.warn('No se pudieron listar cámaras en ventas:', error);
            currentDevices = [];
        }

        const preferredRear = chooseRearCamera(currentDevices);
        const cameraCandidates = [
            preferredRear?.id,
            { facingMode: { exact: 'environment' } },
            { facingMode: 'environment' },
            currentDevices[0]?.id,
            { facingMode: 'user' },
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
                await applyFocusEnhancements();
                await ensureRearCameraActive();
                setStatus('Apunta la cámara al código de barras.', 'info');
                return;
            } catch (error) {
                console.warn('Intento de cámara fallido en ventas:', cameraConfig, error);
            }
        }

        setStatus('No se pudo iniciar la cámara del buscador POS.', 'error');
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
        scanLock = false;
        lastScanValue = '';
        lastScanAt = 0;
        startScanner();
    });

    modalElement.addEventListener('hidden.bs.modal', async () => {
        await stopScanner();
        setStatus('Escanea un código para enviarlo directo al carrito.', 'info');
    });
});
