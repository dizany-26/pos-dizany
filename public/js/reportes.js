document.addEventListener('DOMContentLoaded', () => {
    if (window.flatpickr) {
        flatpickr.localize(flatpickr.l10ns.es);
        flatpickr('[data-report-date]', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            allowInput: false,
            disableMobile: true
        });
    }

    const tabs = [...document.querySelectorAll('[data-report-tab]')];
    const panes = [...document.querySelectorAll('[data-report-pane]')];
    const activate = (name) => {
        tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.reportTab === name));
        panes.forEach(pane => pane.classList.toggle('active', pane.dataset.reportPane === name));
        try { sessionStorage.setItem('dizany-report-tab', name); } catch (_) {}
    };
    tabs.forEach(tab => tab.addEventListener('click', () => activate(tab.dataset.reportTab)));
    let saved = '';
    try { saved = sessionStorage.getItem('dizany-report-tab') || ''; } catch (_) {}
    if (tabs.some(tab => tab.dataset.reportTab === saved)) activate(saved);

    const inventoryTabs = [...document.querySelectorAll('[data-inventory-tab]')];
    const inventoryPanes = [...document.querySelectorAll('[data-inventory-pane]')];
    const activateInventory = (name) => {
        inventoryTabs.forEach(tab => tab.classList.toggle('active', tab.dataset.inventoryTab === name));
        inventoryPanes.forEach(pane => pane.classList.toggle('active', pane.dataset.inventoryPane === name));
    };
    inventoryTabs.forEach(tab => tab.addEventListener('click', () => activateInventory(tab.dataset.inventoryTab)));

    const mobileQuery = window.matchMedia('(max-width: 760px)');
    const updateExpandableText = () => {
        document.querySelectorAll('[data-report-expand]').forEach(wrapper => {
            const value = wrapper.querySelector('.report-expandable-value');
            const toggle = wrapper.querySelector('.report-expand-toggle');
            if (!value || !toggle) return;
            wrapper.classList.remove('expanded');
            toggle.textContent = 'Ver más';
            wrapper.classList.toggle('has-overflow', mobileQuery.matches && value.textContent.trim().length > 28);
        });
    };
    document.addEventListener('click', event => {
        const toggle = event.target.closest('.report-expand-toggle');
        if (!toggle) return;
        const wrapper = toggle.closest('[data-report-expand]');
        const expanded = wrapper.classList.toggle('expanded');
        toggle.textContent = expanded ? 'Ver menos' : 'Ver más';
    });
    mobileQuery.addEventListener?.('change', updateExpandableText);
    updateExpandableText();
});
