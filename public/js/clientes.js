document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const forms = document.querySelectorAll('.client-form');

    const jsonFetch = async (url, options = {}) => {
        const response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.body ? {'X-CSRF-TOKEN': csrf} : {}),
                ...(options.headers || {})
            }
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const firstError = Object.values(data.errors || {})[0]?.[0];
            throw new Error(firstError || data.message || 'No se pudo completar la operación.');
        }
        return data;
    };

    const configureForm = form => {
        const type = form.querySelector('.client-document-type');
        const number = form.querySelector('.client-document-number');
        const queryButton = form.querySelector('.client-query-button');
        const status = form.querySelector('.client-query-status');
        const saveButton = form.querySelector('.client-save-button');
        let timer = null;
        let lastQuery = '';
        let duplicated = false;

        const setStatus = (message, state = '') => {
            status.textContent = message;
            status.className = `provider-query-status client-query-status${state ? ` is-${state}` : ''}`;
        };

        const resetDocument = (clear = false) => {
            const length = type.value === 'RUC' ? 11 : 8;
            number.maxLength = length;
            number.placeholder = `Ingresa ${length} dígitos`;
            if (clear) number.value = '';
            lastQuery = '';
            duplicated = false;
            queryButton.disabled = false;
            saveButton.disabled = false;
            setStatus('La consulta se realizará al completar el documento.');
        };

        const consult = async () => {
            const documentNumber = number.value.replace(/\D/g, '');
            const length = type.value === 'RUC' ? 11 : 8;
            const key = `${type.value}-${documentNumber}`;
            if (documentNumber.length !== length) {
                setStatus(`El ${type.value} debe tener ${length} dígitos.`, 'warning');
                return;
            }
            if (key === lastQuery) return;
            lastQuery = key;
            duplicated = false;
            queryButton.disabled = true;
            queryButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span><span>Consultando</span>';
            setStatus('Verificando primero los clientes registrados…', 'loading');

            try {
                const params = new URLSearchParams({tipo: type.value, numero: documentNumber});
                const id = form.querySelector('[name="cliente_id"]')?.value;
                if (id) params.set('excepto', id);
                const local = await jsonFetch(`/clientes/verificar-documento?${params}`);
                if (local.existe) {
                    duplicated = true;
                    saveButton.disabled = true;
                    setStatus(`Este documento ya está registrado para ${local.cliente.nombre}.`, 'error');
                    return;
                }

                setStatus('Consultando información oficial…', 'loading');
                const official = await jsonFetch(`/consulta-documento/${type.value.toLowerCase()}/${documentNumber}`);
                form.querySelector('[name="nombre"]').value = official.nombre || '';
                if (official.direccion) form.querySelector('[name="direccion"]').value = official.direccion;
                setStatus(type.value === 'RUC' && official.estado
                    ? `Empresa encontrada · Estado: ${official.estado}`
                    : 'Datos encontrados correctamente.', 'success');
            } catch (error) {
                lastQuery = '';
                setStatus(error.message, 'error');
            } finally {
                queryButton.disabled = duplicated;
                queryButton.innerHTML = '<i class="fas fa-search"></i><span>Consultar</span>';
            }
        };

        type.addEventListener('change', () => {
            form.querySelector('[name="nombre"]').value = '';
            form.querySelector('[name="direccion"]').value = '';
            resetDocument(true);
            number.focus();
        });
        number.addEventListener('input', () => {
            number.value = number.value.replace(/\D/g, '');
            duplicated = false;
            queryButton.disabled = false;
            saveButton.disabled = false;
            lastQuery = '';
            setStatus('La consulta se realizará al completar el documento.');
            clearTimeout(timer);
            const length = type.value === 'RUC' ? 11 : 8;
            if (number.value.length === length) timer = setTimeout(consult, 350);
        });
        queryButton.addEventListener('click', consult);
        form._resetDocument = resetDocument;
    };

    forms.forEach(form => {
        configureForm(form);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const save = form.querySelector('.client-save-button');
            const id = form.querySelector('[name="cliente_id"]')?.value;
            const payload = new FormData(form);
            if (id) payload.append('_method', 'PUT');
            save.disabled = true;
            try {
                const result = await jsonFetch(id ? `/clientes/${id}` : '/clientes', {method: 'POST', body: payload});
                await Swal.fire({icon: 'success', title: 'Listo', text: result.message, confirmButtonText: 'Aceptar'});
                window.location.reload();
            } catch (error) {
                Swal.fire({icon: 'error', title: 'No se pudo guardar', text: error.message});
                save.disabled = false;
            }
        });
        form.closest('.modal')?.addEventListener('hidden.bs.modal', () => {
            form.reset();
            form._resetDocument?.();
        });
        form._resetDocument?.();
    });

    document.addEventListener('click', async event => {
        const button = event.target.closest('.btn-edit-client');
        if (!button) return;
        try {
            const client = await jsonFetch(`/clientes/${button.dataset.id}/edit`);
            const form = document.getElementById('formEditarCliente');
            form.querySelector('[name="cliente_id"]').value = client.id;
            form.querySelector('[name="tipo_documento"]').value = client.ruc ? 'RUC' : 'DNI';
            form._resetDocument?.();
            form.querySelector('[name="numero_documento"]').value = client.ruc || client.dni || '';
            form.querySelector('[name="nombre"]').value = client.nombre || '';
            form.querySelector('[name="direccion"]').value = client.direccion || '';
            form.querySelector('[name="telefono"]').value = client.telefono || '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarCliente')).show();
        } catch (error) {
            Swal.fire({icon: 'error', title: 'No se pudo abrir', text: error.message});
        }
    });

    const search = document.getElementById('searchCliente');
    let searchTimer = null;
    search?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(async () => {
            const url = new URL(window.location.href);
            search.value.trim() ? url.searchParams.set('search', search.value.trim()) : url.searchParams.delete('search');
            try {
                const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
                const html = await response.text();
                const documentResult = new DOMParser().parseFromString(html, 'text/html');
                document.getElementById('clientesTableContent').innerHTML = documentResult.getElementById('clientesTableContent').innerHTML;
                window.history.replaceState({}, '', url);
            } catch (_) {}
        }, 280);
    });
});
