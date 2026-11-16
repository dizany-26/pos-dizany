document.addEventListener('DOMContentLoaded', () => {
    const modalElement = document.getElementById('modalDevolucion');
    if (!modalElement) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const form = document.getElementById('return-form');
    const search = document.getElementById('return-sale-search');
    const content = document.getElementById('return-sale-content');
    const feedback = document.getElementById('return-feedback');
    const submit = document.getElementById('return-submit');
    let sale = null;
    const message = (text, type = 'danger') => { feedback.className = `alert alert-${type} mt-3`; feedback.textContent = text; };
    const clearMessage = () => feedback.className = 'alert d-none mt-3';
    const money = value => `S/ ${Number(value || 0).toFixed(2)}`;
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[character]));
    const proportionalAmount = (lineTotal, soldQuantity, returnQuantity) => Math.round(((Number(lineTotal || 0) * Number(returnQuantity || 0) / Math.max(1, Number(soldQuantity || 0))) + Number.EPSILON) * 100) / 100;
    const updateTotal = () => {
        let total = 0;
        document.querySelectorAll('#return-items tr').forEach(row => { const quantity = Number(row.querySelector('.return-quantity')?.value || 0); const lineAmount = proportionalAmount(row.dataset.lineTotal, row.dataset.soldQuantity, quantity); total += lineAmount; row.querySelector('.return-line-total').textContent = money(lineAmount); });
        document.getElementById('return-total').textContent = money(total); submit.disabled = total <= 0;
    };
    async function loadSale(params) {
        clearMessage(); content.classList.add('d-none'); submit.disabled = true;
        try {
            const url = new URL('/devoluciones/ventas/buscar', window.location.origin); Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));
            const response = await fetch(url, {headers: {'Accept': 'application/json'}}); const data = await response.json();
            if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'No se encontró la venta.');
            sale = data; document.getElementById('return-sale-id').value = data.id;
            document.getElementById('return-sale-header').innerHTML = `<div><span>Venta original</span><strong>${escapeHtml(data.comprobante)}</strong></div><div><span>Cliente</span><strong>${escapeHtml(data.cliente)}</strong></div><div><span>Total / pagado</span><strong>${money(data.total)} / ${money(data.pagado)}</strong></div>`;
            document.getElementById('return-tax-warning').classList.toggle('d-none', !data.requiere_nota_credito);
            document.getElementById('return-items').innerHTML = data.detalles.map(line => `<tr data-detail-id="${Number(line.id)}" data-line-total="${Number(line.importe_linea_devolucion)}" data-sold-quantity="${Number(line.vendida)}"><td><strong>${escapeHtml(line.producto)}</strong><small class="d-block text-muted">${escapeHtml(line.presentacion || 'Unidad')}</small></td><td>${Number(line.disponible)} de ${Number(line.vendida)}</td><td><input type="number" class="form-control return-quantity" min="0" max="${Number(line.disponible)}" value="0" ${line.disponible < 1 ? 'disabled' : ''}></td><td class="text-end fw-bold return-line-total">S/ 0.00</td><td><label class="form-check"><input class="form-check-input return-restock" type="checkbox" ${line.disponible < 1 ? 'disabled' : ''}><span class="form-check-label">Reponer stock</span></label></td></tr>`).join('');
            content.classList.remove('d-none'); if (!data.puede_devolver) message('Todos los productos de esta venta ya fueron devueltos.', 'warning'); updateTotal();
        } catch (error) { message(error.message); }
    }
    document.querySelectorAll('.js-open-return').forEach(button => button.addEventListener('click', () => { form.reset(); sale = null; content.classList.add('d-none'); clearMessage(); modal.show(); if (button.dataset.saleId) loadSale({venta_id: button.dataset.saleId}); else setTimeout(() => search.focus(), 200); }));
    document.getElementById('return-search-button').addEventListener('click', () => { if (search.value.trim()) loadSale({q: search.value.trim()}); });
    search.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); document.getElementById('return-search-button').click(); } });
    document.getElementById('return-items').addEventListener('input', event => { if (event.target.classList.contains('return-quantity')) updateTotal(); });
    form.addEventListener('submit', async event => {
        event.preventDefault(); clearMessage();
        const details = [...document.querySelectorAll('#return-items tr')].map(row => ({detalle_venta_id: Number(row.dataset.detailId), cantidad: Number(row.querySelector('.return-quantity').value || 0), reintegra_stock: row.querySelector('.return-restock').checked})).filter(item => item.cantidad > 0);
        if (!details.length) return message('Indica la cantidad de al menos un producto.'); submit.disabled = true;
        try {
            const response = await fetch('/devoluciones', {method: 'POST', headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value}, body: JSON.stringify({venta_id: sale.id, detalles: details, metodo_reembolso: form.metodo_reembolso.value, motivo: form.motivo.value})});
            const data = await response.json(); if (!response.ok) throw new Error(data.message || Object.values(data.errors || {})[0]?.[0] || 'No se pudo registrar la devolución.');
            window.location.href = `/movimientos?tipo=devoluciones&return_success=${encodeURIComponent(data.message)}`;
        } catch (error) { message(error.message); submit.disabled = false; }
    });
    const pageParams = new URLSearchParams(location.search);
    const requestedSale = pageParams.get('venta_id');
    if (requestedSale) { modal.show(); loadSale({venta_id: requestedSale}); }
    const success = pageParams.get('return_success'); if (success && window.Swal) Swal.fire({icon: 'success', title: 'Devolución registrada', text: success});
});
