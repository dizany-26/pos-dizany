document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('buscar_producto');
    const btnEscanear = document.getElementById('btnEscanearVenta');
    const modalElement = document.getElementById('modalEscanearVenta');
    const status = document.getElementById('ventaBarcodeScannerStatus');
    const btnCerrar = document.getElementById('btnCerrarEscanerVenta');
    const btnTorch = document.getElementById('btnVentaBarcodeTorch');
    const btnZoom = document.getElementById('btnVentaBarcodeZoom');
    const cameraSelectWrap = document.getElementById('ventaBarcodeCameraSelectWrap');
    const cameraSelect = document.getElementById('ventaBarcodeCameraSelect');
    const zoomControl = document.getElementById('ventaBarcodeZoomControl');
    const zoomRange = document.getElementById('ventaBarcodeZoomRange');
    const zoomValue = document.getElementById('ventaBarcodeZoomValue');
    const btnPhoto = document.getElementById('btnVentaBarcodePhoto');
    const photoInput = document.getElementById('ventaBarcodePhotoInput');

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
    const PREFERRED_CAMERA_STORAGE_KEY = 'dizany_barcode_preferred_camera_v1';
    let successAudio = null;
    let currentDevices = [];

    const getStoredCameraId = () => {
        try {
            return localStorage.getItem(PREFERRED_CAMERA_STORAGE_KEY) || '';
        } catch (_) {
            return '';
        }
    };

    const rememberCameraId = (cameraId) => {
        if (!cameraId) return;
        try {
            localStorage.setItem(PREFERRED_CAMERA_STORAGE_KEY, cameraId);
        } catch (_) {
            // La selección automática continúa aunque no haya almacenamiento.
        }
    };

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

    const populateCameraSelect = (devices = [], selectedId = '') => {
        if (!cameraSelect || !cameraSelectWrap) return;

        cameraSelect.innerHTML = '';
        devices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.id;
            option.textContent = device.label || `Cámara ${index + 1}`;
            option.selected = device.id === selectedId;
            cameraSelect.appendChild(option);
        });

        cameraSelectWrap.classList.toggle('d-none', devices.length < 2);
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
            .map((device, index) => {
                const label = String(device.label || '').toLowerCase();
                let score = 0;
                if (/back|rear|environment|tr[aá]s|posterior/.test(label)) score += 200;
                if (/macro|close|closeup/.test(label)) score += 80;
                if (/front|user|selfie|frontal/.test(label)) score -= 250;
                if (/wide|ultra/.test(label)) score -= 30;
                const genericCameraIndex = label.match(/camera\s*(\d+)/)?.[1];
                if (genericCameraIndex !== undefined && /back|rear|environment/.test(label)) {
                    score += Math.max(0, 70 - Number(genericCameraIndex) * 15);
                }
                if (!label) score += index * 3;
                return { device, score };
            })
            .sort((a, b) => b.score - a.score);

        return ranked[0]?.device || null;
    };

    const primeRearCameraAccess = async () => {
        if (!navigator.mediaDevices?.getUserMedia) return;

        const probe = async (facingMode) => {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode,
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                },
                audio: false,
            });

            const [track] = stream.getVideoTracks();
            const settings = track?.getSettings?.() || {};
            const deviceId = settings.deviceId || null;
            const facing = String(settings.facingMode || '').toLowerCase();

            stream.getTracks().forEach((t) => t.stop());

            if (facing === 'user' || facing === 'front') {
                throw new Error('La cámara seleccionada en probe fue frontal');
            }

            return deviceId;
        };

        try {
            return await probe({ exact: 'environment' });
        } catch (errorExact) {
            try {
                return await probe({ ideal: 'environment' });
            } catch (errorIdeal) {
                console.warn('No se pudo precalentar acceso a cámara trasera en ventas:', errorIdeal);
                return null;
            }
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

    const syncTools = () => {
        const capabilities = getTrackCapabilities();
        const settings = getRunningTrackSettings();
        const supportsTorch = capabilities?.torch === true || (Array.isArray(capabilities?.fillLightMode) && capabilities.fillLightMode.includes('flash'));
        const supportsZoom = typeof capabilities?.zoom !== 'undefined';

        setToolVisibility(btnTorch, supportsTorch);
        setToolVisibility(btnZoom, supportsZoom);
        zoomControl?.classList.toggle('d-none', !supportsZoom);

        if (supportsZoom && zoomRange) {
            const min = Number(capabilities.zoom.min ?? 1);
            const max = Number(capabilities.zoom.max ?? 3);
            const step = Number(capabilities.zoom.step ?? 0.1);
            const current = Number(settings.zoom ?? min);
            zoomRange.min = String(min);
            zoomRange.max = String(max);
            zoomRange.step = String(step);
            zoomRange.value = String(Math.max(min, Math.min(max, current)));
            if (zoomValue) zoomValue.textContent = `${Number(zoomRange.value).toFixed(1)}×`;
        }
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
            const closeFocus = Math.max(min, Math.min(max, max - (max - min) * 0.18));
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
            const formatsToSupport = typeof Html5QrcodeSupportedFormats !== 'undefined'
                ? [
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.ITF,
                ]
                : undefined;

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
                        deviceId: { exact: preferredRear.id },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        focusMode: 'continuous',
                    },
                    formatsToSupport,
                },
                async (decodedText) => {
                    await procesarCodigoDetectado(decodedText);
                },
                () => {}
            );
            scannerRunning = true;
            rememberCameraId(preferredRear.id);
            if (cameraSelect) cameraSelect.value = preferredRear.id;
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
            zoomControl?.classList.add('d-none');
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
        zoomControl?.classList.add('d-none');
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

    const startScanner = async (forcedCameraId = '') => {
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

        const probedRearDeviceId = forcedCameraId ? null : await primeRearCameraAccess();

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
        const storedCameraId = getStoredCameraId();
        const storedCamera = currentDevices.find((device) => device.id === storedCameraId);
        populateCameraSelect(currentDevices, forcedCameraId || storedCamera?.id || preferredRear?.id || '');
        const cameraCandidates = [
            forcedCameraId,
            storedCamera?.id,
            preferredRear?.id,
            probedRearDeviceId,
            currentDevices.find((device) => /macro|close|closeup/i.test(device.label || ''))?.id,
            { facingMode: { exact: 'environment' } },
            { facingMode: 'environment' },
        ].filter(Boolean);

        for (const cameraConfig of cameraCandidates) {
            try {
                const cameraVideoConstraints = typeof cameraConfig === 'string'
                    ? {
                        deviceId: { exact: cameraConfig },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        focusMode: 'continuous',
                    }
                    : {
                        ...cameraConfig,
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                        focusMode: 'continuous',
                    };

                await html5QrCode.start(
                    cameraConfig,
                    {
                        ...config,
                        videoConstraints: cameraVideoConstraints,
                    },
                    async (decodedText) => {
                        await procesarCodigoDetectado(decodedText);
                    },
                    () => {}
                );
                scannerRunning = true;
                if (typeof cameraConfig === 'string') {
                    rememberCameraId(cameraConfig);
                    if (cameraSelect) cameraSelect.value = cameraConfig;
                }
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
        const zoom = nextState ? Math.min(maxZoom, Math.max(minZoom, 2)) : minZoom;
        const focusConstraints = [];
        if (Array.isArray(capabilities?.focusMode) && capabilities.focusMode.includes('continuous')) {
            focusConstraints.push({ focusMode: 'continuous' });
        }
        if (typeof capabilities?.focusDistance !== 'undefined') {
            const minFocus = Number(capabilities.focusDistance.min ?? 0);
            const maxFocus = Number(capabilities.focusDistance.max ?? 1);
            focusConstraints.push({ focusDistance: maxFocus - (maxFocus - minFocus) * 0.12 });
        }
        const applied = await applyVideoConstraints({ advanced: [...focusConstraints, { zoom }] });
        if (applied) {
            zoomEnabled = nextState;
            setToolActive(btnZoom, zoomEnabled);
            if (zoomRange) zoomRange.value = String(zoom);
            if (zoomValue) zoomValue.textContent = `${Number(zoom).toFixed(1)}×`;
        }
    });

    zoomRange?.addEventListener('input', async () => {
        const zoom = Number(zoomRange.value);
        if (zoomValue) zoomValue.textContent = `${zoom.toFixed(1)}×`;
        const applied = await applyVideoConstraints({ advanced: [{ zoom }] });
        if (applied) {
            zoomEnabled = zoom > Number(zoomRange.min);
            setToolActive(btnZoom, zoomEnabled);
        }
    });

    cameraSelect?.addEventListener('change', async () => {
        const cameraId = cameraSelect.value;
        if (!cameraId) return;

        rememberCameraId(cameraId);
        setStatus('Cambiando a la cámara seleccionada…', 'info');
        await stopScanner();
        await startScanner(cameraId);
    });

    btnPhoto?.addEventListener('click', () => {
        photoInput?.click();
    });

    photoInput?.addEventListener('change', async () => {
        const file = photoInput.files?.[0];
        if (!file || typeof Html5Qrcode === 'undefined') return;

        const selectedCameraId = cameraSelect?.value || getStoredCameraId();
        setStatus('Analizando la fotografía…', 'info');

        try {
            await stopScanner();
            html5QrCode = new Html5Qrcode(readerElementId);
            const decodedText = await html5QrCode.scanFile(file, true);
            setStatus('Código detectado en la fotografía.', 'success');
            await procesarCodigoDetectado(decodedText);
            try {
                await html5QrCode?.clear();
            } catch (_) {
                // La lectura ya terminó y el modal puede haberse cerrado.
            }
        } catch (error) {
            console.warn('No se pudo leer el código desde la fotografía:', error);
            setStatus('No se encontró un código. Usa una foto más nítida y cercana.', 'error');
            try {
                await html5QrCode?.clear();
            } catch (_) {
                // El lector puede no haber creado todavía una superficie visible.
            }
            html5QrCode = null;
            await startScanner(selectedCameraId);
        } finally {
            photoInput.value = '';
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
