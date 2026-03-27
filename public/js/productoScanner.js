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
    let macroModeEnabled = false;
    let beepAudioContext = null;
    let torchLikelyAvailable = false;
    let autoMacroApplied = false;
    let activeCameraConfig = null;
    let currentDevices = [];
    let isProcessingScan = false;
    let lastAcceptedScanAt = 0;
    let lastAcceptedCode = '';
    const GLOBAL_SCAN_THROTTLE_MS = 250;
    const SAME_CODE_COOLDOWN_MS = 1500;

    const isMobileDevice = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || '');

    const setStatus = (message, type = 'info') => {
        status.textContent = message;
        status.className = `barcode-scanner-status barcode-scanner-status-${type}`;
    };

    const toggleFallback = (visible) => {
        fallbackActions.classList.toggle('d-none', !visible);
    };

    const setToolVisibility = (button, visible) => {
        if (!button) return;
        button.classList.toggle('d-none', !visible);
    };

    const setToolActive = (button, active) => {
        if (!button) return;
        button.classList.toggle('is-active', active);
    };

    const focusNextInput = () => {
        if (!nextInput) return;

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
            if (!AudioContextCtor) return;

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

    const shouldIgnoreScan = (decodedText) => {
        const now = Date.now();
        const codigoNormalizado = String(decodedText || '').trim();
        const isSameCode = codigoNormalizado && codigoNormalizado === lastAcceptedCode;

        if ((now - lastAcceptedScanAt) < GLOBAL_SCAN_THROTTLE_MS) {
            return true;
        }

        if (isSameCode && (now - lastAcceptedScanAt) < SAME_CODE_COOLDOWN_MS) {
            return true;
        }

        return false;
    };

    const handleBarcodeDetected = async (decodedText) => {
        const codigoNormalizado = String(decodedText || '').trim();

        if (isProcessingScan || shouldIgnoreScan(codigoNormalizado)) {
            return;
        }

        isProcessingScan = true;
        lastAcceptedScanAt = Date.now();
        lastAcceptedCode = codigoNormalizado;

        try {
            fillBarcode(decodedText);
            await playSuccessFeedback();
            setStatus('Código detectado correctamente. Cerrando escáner…', 'success');
            await stopScanner();
            modal?.hide();
        } finally {
            isProcessingScan = false;
        }
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
            torchEnabled = false;
            zoomEnabled = false;
            macroModeEnabled = false;
            torchLikelyAvailable = false;
            autoMacroApplied = false;
            activeCameraConfig = null;

            setToolActive(btnTorch, false);
            setToolActive(btnZoom, false);
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
        macroModeEnabled = false;
        torchLikelyAvailable = false;
        autoMacroApplied = false;
        activeCameraConfig = null;

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

    const getRunningTrackSettings = () => {
        if (!html5QrCode || !scannerRunning || typeof html5QrCode.getRunningTrackSettings !== 'function') {
            return {};
        }

        try {
            return html5QrCode.getRunningTrackSettings() || {};
        } catch (error) {
            console.warn('No se pudieron obtener los settings de la cámara:', error);
            return {};
        }
    };

    const supportsTorchFromCapabilities = (capabilities) => {
        return Boolean(
            capabilities?.torch === true ||
            (Array.isArray(capabilities?.fillLightMode) && capabilities.fillLightMode.includes('flash'))
        );
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

        for (const constraint of advancedConstraints) {
            await applyVideoConstraints({ advanced: [constraint] });
        }
    };

    const verifyTorchState = (expectedState) => {
        const settings = getRunningTrackSettings();
        console.log('Settings actuales cámara:', settings);

        const torchBySetting = settings?.torch === true;
        const flashBySetting = settings?.fillLightMode === 'flash';
        const offBySetting = settings?.torch === false || settings?.fillLightMode === 'off';

        if (expectedState === true) {
            return torchBySetting || flashBySetting;
        }

        if (expectedState === false) {
            return offBySetting || (!torchBySetting && !flashBySetting);
        }

        return false;
    };

    const getPreferredCameraDevice = (devices = [], preferMacro = false) => {
        if (!devices.length) {
            return null;
        }

        const rankedDevices = devices
            .map((device) => {
                const label = `${device.label || ''}`.toLowerCase();
                let score = 0;

                if (preferMacro) {
                    if (/macro/.test(label)) score += 200;
                    if (/close|closeup/.test(label)) score += 120;
                    if (/telephoto/.test(label)) score += 40;
                    if (/back|rear|environment|tr[aá]s|posterior/.test(label)) score += 30;
                    if (/wide|ultra/.test(label)) score -= 40;
                    if (/front|user|selfie|frontal/.test(label)) score -= 200;
                } else {
                    if (/back|rear|environment|tr[aá]s|posterior/.test(label)) score += 100;
                    if (/macro/.test(label)) score += 20;
                    if (/front|user|selfie|frontal/.test(label)) score -= 150;
                    if (/wide|ultra/.test(label)) score -= 15;
                }

                return { device, score, label };
            })
            .sort((a, b) => b.score - a.score);

        console.log('Ranking cámaras:', rankedDevices);

        return rankedDevices[0]?.device || null;
    };

    const syncCameraTools = () => {
        const capabilities = getTrackCapabilities();

        console.log('Capacidades cámara:', capabilities);

        const supportsTorch =
            capabilities?.torch === true ||
            (Array.isArray(capabilities?.fillLightMode) &&
                capabilities.fillLightMode.includes('flash'));

        const supportsZoom = typeof capabilities?.zoom !== 'undefined';
        const supportsFocusMode = Array.isArray(capabilities?.focusMode) && capabilities.focusMode.length > 0;
        const supportsFocusDistance = typeof capabilities?.focusDistance !== 'undefined';
        const hasMacroCamera = currentDevices.some((device) => /macro|close|closeup/i.test(device.label || ''));

        torchLikelyAvailable = supportsTorch;

        setToolVisibility(btnTorch, isMobileDevice);
        setToolVisibility(
            btnZoom,
            hasMacroCamera || supportsFocusMode || supportsFocusDistance || supportsZoom
        );

        if (btnTorch) {
            const span = btnTorch.querySelector('span');
            const icon = btnTorch.querySelector('i');

            if (supportsTorch) {
                btnTorch.disabled = false;
                btnTorch.classList.remove('btn-disabled');
                btnTorch.title = 'Encender o apagar linterna';
                if (span) span.textContent = 'Linterna';
                if (icon) icon.className = 'fas fa-lightbulb';
            } else {
                btnTorch.disabled = true;
                btnTorch.classList.add('btn-disabled');
                btnTorch.title = 'Linterna no disponible en este dispositivo';
                if (span) span.textContent = 'No disponible';
                if (icon) icon.className = 'fas fa-ban';
            }
        }

        if (btnZoom) {
            btnZoom.disabled = false;
            btnZoom.classList.remove('btn-disabled');
            btnZoom.title = 'Activar modo macro';

            const span = btnZoom.querySelector('span');
            const icon = btnZoom.querySelector('i');

            if (span) span.textContent = 'Macro';
            if (icon) icon.className = 'fas fa-seedling';
        }
    };

    const restartScannerWithCamera = async (cameraConfig, keepMacroMessage = false) => {
        if (scannerRunning) {
            await stopScanner();
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode(readerElementId);
        }

        const config = {
            fps: window.innerWidth < 768 ? 14 : 12,
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                const width = Math.min(viewfinderWidth * 0.9, 420);
                const height = Math.min(Math.max(width * 0.82, 210), viewfinderHeight * 0.72);
                return {
                    width: Math.round(width),
                    height: Math.round(height),
                };
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

        await html5QrCode.start(
            cameraConfig,
            config,
            async (decodedText) => {
                await handleBarcodeDetected(decodedText);
            },
            () => { }
        );

        scannerRunning = true;
        activeCameraConfig = cameraConfig;

        console.log('Cámara reiniciada con:', cameraConfig);
        console.log('Capacidades detectadas:', getTrackCapabilities());
        console.log('Settings detectados:', getRunningTrackSettings());

        syncCameraTools();
        await enhanceVideoQuality();

        if (keepMacroMessage) {
            setStatus('Modo macro activado.', 'info');
        } else {
            setStatus('Apunta la cámara al código de barras.', 'info');
        }
    };

    const restartScannerWithTorchHint = async () => {
        if (!scannerRunning) return false;

        const currentCamera = activeCameraConfig;
        await stopScanner();

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode(readerElementId);
        }

        const config = {
            fps: window.innerWidth < 768 ? 14 : 12,
            qrbox: (viewfinderWidth, viewfinderHeight) => {
                const width = Math.min(viewfinderWidth * 0.9, 420);
                const height = Math.min(Math.max(width * 0.82, 210), viewfinderHeight * 0.72);
                return {
                    width: Math.round(width),
                    height: Math.round(height),
                };
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

        try {
            await html5QrCode.start(
                currentCamera || { facingMode: 'environment' },
                config,
                async (decodedText) => {
                    await handleBarcodeDetected(decodedText);
                },
                () => { }
            );

            scannerRunning = true;
            activeCameraConfig = currentCamera || { facingMode: 'environment' };

            console.log('Cámara reiniciada');
            console.log('Capacidades detectadas:', getTrackCapabilities());
            console.log('Settings detectados:', getRunningTrackSettings());

            syncCameraTools();
            await enhanceVideoQuality();
            return true;
        } catch (error) {
            console.warn('No se pudo reiniciar la cámara:', error);
            return false;
        }
    };

    const toggleTorch = async () => {
        if (!scannerRunning || !html5QrCode) {
            setStatus('La cámara aún no está activa.', 'error');
            return;
        }

        const capabilities = getTrackCapabilities();
        console.log('Capacidades torch:', capabilities);

        const supportsTorch = supportsTorchFromCapabilities(capabilities);

        if (!supportsTorch) {
            setStatus('Linterna no disponible.', 'error');
            return;
        }

        const nextTorchState = !torchEnabled;

        const strategies = [
            { torch: nextTorchState },
            { advanced: [{ torch: nextTorchState }] },
            { advanced: [{ fillLightMode: nextTorchState ? 'flash' : 'off' }] }
        ];

        let applied = false;

        for (const constraints of strategies) {
            const ok = await applyVideoConstraints(constraints);
            if (ok) {
                applied = true;
                break;
            }
        }

        if (!applied) {
            setStatus('No se pudo usar la linterna.', 'error');
            return;
        }

        await new Promise((resolve) => setTimeout(resolve, 250));

        const realTorchState = verifyTorchState(nextTorchState);

        if (realTorchState) {
            torchEnabled = nextTorchState;
            setToolActive(btnTorch, torchEnabled);
            setStatus(torchEnabled ? 'Linterna activada.' : 'Linterna desactivada.', 'info');
            return;
        }

        console.warn('La solicitud de torch fue aceptada, pero no se reflejó en los settings reales.');

        if (nextTorchState === true) {
            const restarted = await restartScannerWithTorchHint();

            if (restarted) {
                const capabilitiesAfterRestart = getTrackCapabilities();
                const supportsTorchAfterRestart = supportsTorchFromCapabilities(capabilitiesAfterRestart);

                if (supportsTorchAfterRestart) {
                    for (const constraints of strategies) {
                        const ok = await applyVideoConstraints(constraints);
                        if (ok) break;
                    }

                    await new Promise((resolve) => setTimeout(resolve, 250));

                    if (verifyTorchState(true)) {
                        torchEnabled = true;
                        setToolActive(btnTorch, true);
                        setStatus('Linterna activada.', 'info');
                        return;
                    }
                }
            }
        }

        torchEnabled = false;
        setToolActive(btnTorch, false);
        setStatus('No se logró encender el flash.', 'error');
    };

    const toggleZoom = async () => {
        if (!scannerRunning) {
            return;
        }

        const nextMacroState = !macroModeEnabled;

        if (!nextMacroState) {
            macroModeEnabled = false;
            zoomEnabled = false;
            autoMacroApplied = false;
            setToolActive(btnZoom, false);

            try {
                const normalCamera =
                    getPreferredCameraDevice(currentDevices, false)?.id ||
                    { facingMode: 'environment' };

                await restartScannerWithCamera(normalCamera, false);
            } catch (error) {
                console.warn('No se pudo volver a la cámara normal:', error);
                setStatus('No se pudo volver al modo normal.', 'error');
            }

            return;
        }

        const macroDevice = getPreferredCameraDevice(currentDevices, true);
        const macroLabel = `${macroDevice?.label || ''}`.toLowerCase();
        const looksLikeRealMacro = /macro|close|closeup/.test(macroLabel);

        try {
            if (macroDevice && looksLikeRealMacro) {
                await restartScannerWithCamera(macroDevice.id, true);
                macroModeEnabled = true;
                zoomEnabled = false;
                setToolActive(btnZoom, true);
                return;
            }
        } catch (error) {
            console.warn('No se pudo iniciar la cámara macro real:', error);
        }

        const capabilities = getTrackCapabilities();
        console.log('Capacidades para macro fallback:', capabilities);

        let appliedAny = false;

        if (Array.isArray(capabilities.focusMode) && capabilities.focusMode.includes('continuous')) {
            appliedAny = await applyVideoConstraints({
                advanced: [{ focusMode: 'continuous' }]
            }) || appliedAny;
        }

        if (typeof capabilities.focusDistance !== 'undefined') {
            const min = typeof capabilities.focusDistance.min === 'number' ? capabilities.focusDistance.min : 0;
            const max = typeof capabilities.focusDistance.max === 'number' ? capabilities.focusDistance.max : 1;
            const closeFocus = Math.max(min, Math.min(max, min + (max - min) * 0.15));

            appliedAny = await applyVideoConstraints({
                advanced: [{ focusDistance: closeFocus }]
            }) || appliedAny;
        }

        if (!appliedAny && typeof capabilities.zoom !== 'undefined') {
            const minZoom = typeof capabilities.zoom.min === 'number' ? capabilities.zoom.min : 1;
            const maxZoom = typeof capabilities.zoom.max === 'number' ? capabilities.zoom.max : 3;
            const softZoom = Math.min(maxZoom, Math.max(minZoom, 1.4));

            appliedAny = await applyVideoConstraints({
                advanced: [{ zoom: softZoom }]
            }) || appliedAny;
        }

        if (appliedAny) {
            macroModeEnabled = true;
            zoomEnabled = false;
            setToolActive(btnZoom, true);
            setStatus('Modo macro aproximado activado.', 'info');
        } else {
            macroModeEnabled = false;
            setToolActive(btnZoom, false);
            setStatus('Macro no disponible.', 'error');
        }
    };

    const applyAutoMacroPreset = async () => {
        if (!scannerRunning || autoMacroApplied) {
            return;
        }

        const capabilities = getTrackCapabilities();
        const advanced = [];
        let appliedAnySetting = false;

        if (Array.isArray(capabilities.focusMode) && capabilities.focusMode.includes('continuous')) {
            advanced.push({ focusMode: 'continuous' });
        }

        if (typeof capabilities.focusDistance !== 'undefined') {
            const min = typeof capabilities.focusDistance.min === 'number' ? capabilities.focusDistance.min : 0;
            const max = typeof capabilities.focusDistance.max === 'number' ? capabilities.focusDistance.max : 1;
            const closeFocus = Math.max(min, Math.min(max, min + (max - min) * 0.15));
            advanced.push({ focusDistance: closeFocus });
        }

        if (!advanced.length && typeof capabilities.zoom !== 'undefined') {
            const minZoom = typeof capabilities.zoom.min === 'number' ? capabilities.zoom.min : 1;
            const maxZoom = typeof capabilities.zoom.max === 'number' ? capabilities.zoom.max : 3;
            const autoMacroZoom = Math.min(maxZoom, Math.max(minZoom, isMobileDevice ? 1.4 : 1.25));
            advanced.push({ zoom: autoMacroZoom });
        }

        if (!advanced.length) {
            return;
        }

        for (const setting of advanced) {
            const applied = await applyVideoConstraints({ advanced: [setting] });
            appliedAnySetting = appliedAnySetting || applied;
        }

        if (appliedAnySetting) {
            autoMacroApplied = true;
            setToolActive(btnZoom, false);
            setStatus('Modo macro automático activado para etiquetas pequeñas.', 'info');
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
                const width = Math.min(viewfinderWidth * 0.9, 420);
                const height = Math.min(Math.max(width * 0.82, 210), viewfinderHeight * 0.72);
                return {
                    width: Math.round(width),
                    height: Math.round(height),
                };
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

        const onScanSuccess = async (decodedText) => {
            await handleBarcodeDetected(decodedText);
        };

        const onScanError = () => { };

        let devices = [];
        try {
            devices = await Html5Qrcode.getCameras();
            currentDevices = devices;
        } catch (error) {
            console.warn('No se pudieron listar cámaras:', error);
            currentDevices = [];
        }

        const preferredDevice = getPreferredCameraDevice(devices, false);
        const rearDeviceByLabel = devices.find((device) =>
            /back|rear|environment|tr[aá]s|posterior/i.test(device.label || '')
        );

        const cameraCandidates = [
            preferredDevice?.id,
            rearDeviceByLabel?.id,
            { facingMode: { exact: 'environment' } },
            { facingMode: 'environment' },
        ].filter(Boolean);

        for (const cameraConfig of cameraCandidates) {
            try {
                console.log('Intentando cámara:', cameraConfig);

                await html5QrCode.start(cameraConfig, config, onScanSuccess, onScanError);

                scannerRunning = true;
                activeCameraConfig = cameraConfig;

                console.log('Cámara iniciada');
                console.log('Capacidades detectadas:', getTrackCapabilities());
                console.log('Settings detectados:', getRunningTrackSettings());

                syncCameraTools();
                await enhanceVideoQuality();
                await applyAutoMacroPreset();

                if (!autoMacroApplied) {
                    setStatus('Apunta la cámara al código de barras.', 'info');
                }

                return;
            } catch (error) {
                console.warn('Intento de cámara fallido:', cameraConfig, error);
            }
        }

        setStatus('No se pudo iniciar la cámara trasera. Usa Escanear con app externa o revisa permisos.', 'error');
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
        if (btnTorch.disabled) return;
        await toggleTorch();
    });

    btnZoom?.addEventListener('click', async () => {
        if (btnZoom.disabled) return;
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
