document.addEventListener('DOMContentLoaded', () => {
    const rucInput = document.getElementById('fiscal_ruc');
    const queryButton = document.getElementById('query-fiscal-ruc');
    const status = document.getElementById('fiscal-ruc-status');
    if (!rucInput || !queryButton || !status) return;

    const fields = {
        legal_name: document.getElementById('legal_name'),
        trade_name: document.getElementById('trade_name'),
        fiscal_address: document.getElementById('fiscal_address'),
        ubigeo: document.getElementById('ubigeo'),
        department: document.getElementById('department'),
        province: document.getElementById('province'),
        district: document.getElementById('district'),
    };

    const setStatus = (message, type = 'muted') => {
        status.textContent = message;
        status.className = `d-block mt-1 text-${type}`;
        status.hidden = !message;
    };

    const assign = (field, value) => {
        if (fields[field] && value) fields[field].value = value;
    };

    rucInput.addEventListener('input', () => {
        rucInput.value = rucInput.value.replace(/\D/g, '').slice(0, 11);
        setStatus('');
    });

    queryButton.addEventListener('click', async () => {
        const ruc = rucInput.value.replace(/\D/g, '');
        if (ruc.length !== 11) {
            setStatus('El RUC debe contener exactamente 11 dígitos.', 'danger');
            rucInput.focus();
            return;
        }

        queryButton.disabled = true;
        queryButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Consultando';
        setStatus('Consultando los datos registrados para este RUC…', 'primary');

        try {
            const response = await fetch(`/consulta-documento/ruc/${encodeURIComponent(ruc)}`, {
                headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message || 'No se pudo consultar el RUC.');

            assign('legal_name', data.nombre);
            assign('fiscal_address', data.direccion);
            assign('ubigeo', data.ubigeo);
            assign('department', data.departamento);
            assign('province', data.provincia);
            assign('district', data.distrito);

            const condition = [data.estado, data.condicion].filter(Boolean).join(' · ');
            const warning = data.estado && data.estado.toUpperCase() !== 'ACTIVO';
            setStatus(`Datos encontrados${condition ? ` · ${condition}` : ''}. Revisa la dirección antes de guardar.`, warning ? 'warning' : 'success');
        } catch (error) {
            setStatus(error.message || 'No se pudo consultar el RUC.', 'danger');
        } finally {
            queryButton.disabled = false;
            queryButton.innerHTML = '<i class="fas fa-search me-1"></i>Consultar';
        }
    });
});
