document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalNuevoGasto');
    const form = document.getElementById('formNuevoGasto');
    if (!modal || !form) return;

    const errorBox = document.getElementById('gastoModalError');
    const submitButton = form.querySelector('[type="submit"]');
    const fecha = document.getElementById('gastoFecha');
    const enListaGastos = window.location.pathname.replace(/\/$/, '') === '/gastos';

    function fechaLocalActual() {
        const ahora = new Date();
        const local = new Date(ahora.getTime() - ahora.getTimezoneOffset() * 60000);
        return local.toISOString().slice(0, 16);
    }

    modal.addEventListener('show.bs.modal', () => {
        if (!fecha.value) fecha.value = fechaLocalActual();
        errorBox.classList.add('d-none');
        errorBox.textContent = '';
    });

    if (new URLSearchParams(window.location.search).get('nuevo') === '1') {
        bootstrap.Modal.getOrCreateInstance(modal).show();
        const url = new URL(window.location.href);
        url.searchParams.delete('nuevo');
        window.history.replaceState(window.history.state, '', url);
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        submitButton.disabled = true;
        errorBox.classList.add('d-none');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form)
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                const firstError = Object.values(data.errors || {}).flat()[0];
                throw new Error(firstError || data.message || 'No se pudo registrar el gasto.');
            }

            bootstrap.Modal.getOrCreateInstance(modal).hide();
            form.reset();
            fecha.value = fechaLocalActual();
            if (enListaGastos) {
                window.location.reload();
            } else if (window.Swal) {
                Swal.fire({ icon: 'success', title: 'Gasto registrado', text: data.message, timer: 2200, showConfirmButton: false });
            }
        } catch (error) {
            errorBox.textContent = error.message || 'No se pudo registrar el gasto.';
            errorBox.classList.remove('d-none');
        } finally {
            submitButton.disabled = false;
        }
    });
});
