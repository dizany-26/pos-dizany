document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('codigo_barras');
    const btnEscanear = document.getElementById('btnEscanearCodigo');
    const modalElement = document.getElementById('modalEscanearCodigoBarras');
    const readerElementId = 'barcode-reader';
    const status = document.getElementById('barcodeScannerStatus');
    const btnCerrar = document.getElementById('btnCerrarEscaner');

    if (!input || !btnEscanear || !modalElement || !status || !btnCerrar) {
        return;
    }

    const modal = window.bootstrap ? new bootstrap.Modal(modalElement) : null;
    let html5QrCode = null;
    let scannerRunning = false;


    const setStatus = (message, type = 'info') => {
        status.textContent = message;
        status.className = `barcode-scanner-status barcode-scanner-status-${type}`;
    };

    const fillBarcode = (decodedText) => {
        input.value = decodedText.trim();
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
    };

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

        scannerRunning = false;
    };

    const startScanner = async () => {
        if (typeof Html5Qrcode === 'undefined') {
            setStatus('No se pudo cargar el escáner. Revisa tu conexión e inténtalo de nuevo.', 'error');
            return;
        }

        if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            setStatus('La cámara requiere HTTPS o localhost para funcionar.', 'error');
            return;
        }

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode(readerElementId);
        }

        setStatus('Solicitando acceso a la cámara…', 'info');

        const config = {
            fps: 10,
            qrbox: { width: 250, height: 120 },
            aspectRatio: 1.7778,
            rememberLastUsedCamera: true,
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
            fillBarcode(decodedText);
            setStatus('Código detectado correctamente.', 'success');
            await stopScanner();
            modal?.hide();
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
                setStatus('Apunta la cámara al código de barras.', 'info');
                return;
            }
        } catch (error) {
            console.warn('No se pudieron listar cámaras:', error);
        }

        setStatus('No se pudo iniciar la cámara. Verifica permisos o usa una pistola lectora en PC.', 'error');
    };

    btnEscanear.addEventListener('click', () => {
        modal?.show();
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
        setStatus('En móvil usa la cámara. En PC puedes usar una pistola lectora enfocando el campo.', 'info');
    });
});
