(function () {
    'use strict';

    const searchableNames = /(producto|proveedor|cliente|usuario|responsable|categoria|marca)/i;
    const excludedSelects = /(?:barcode.*camera|camera.*select)/i;

    function initSelect(select) {
        if (!window.jQuery?.fn?.select2 || !select.matches('select')) return;
        if (select.classList.contains('select2-hidden-accessible')) return;
        if (select.classList.contains('swal2-select') || select.closest('.swal2-popup')) return;
        if (select.dataset.nativeSelect !== undefined || excludedSelects.test(select.id || '')) return;
        if (select.closest('[data-native-controls]')) return;
        select.classList.add('form-select');

        const options = Array.from(select.options);
        const firstEmpty = options.find(option => option.value === '');
        const identity = `${select.name || ''} ${select.id || ''}`;
        const searchable = select.dataset.selectSearch === 'true'
            || (select.dataset.selectSearch !== 'false' && (options.length > 8 || searchableNames.test(identity)));
        const modal = select.closest('.modal');

        window.jQuery(select).select2({
            width: '100%',
            placeholder: firstEmpty?.textContent?.trim() || 'Seleccionar...',
            allowClear: Boolean(firstEmpty),
            minimumResultsForSearch: searchable ? 0 : Infinity,
            dropdownCssClass: 'ui-modern-select-dropdown',
            dropdownParent: modal ? window.jQuery(modal) : window.jQuery(document.body)
        });

        if (searchable) {
            window.jQuery(select).on('select2:open.uiModernControls', function () {
                const input = document.querySelector('.select2-container--open .select2-search__field');
                if (!input) return;
                input.placeholder = 'Buscar...';
                input.focus();
            });
        }
    }

    function initCalendar(input) {
        if (typeof window.flatpickr !== 'function' || input._flatpickr) return;
        if (input.dataset.nativeCalendar !== undefined || input.closest('[data-native-controls]')) return;

        const isDateTime = input.type === 'datetime-local';
        window.flatpickr(input, {
            locale: 'es',
            dateFormat: isDateTime ? 'Y-m-d\\TH:i' : 'Y-m-d',
            altInput: true,
            altFormat: isDateTime ? 'd F Y, H:i' : 'd F Y',
            enableTime: isDateTime,
            time_24hr: true,
            allowInput: true,
            disableMobile: true,
            minDate: input.min || undefined,
            maxDate: input.max || undefined
        });
    }

    function initialize(root) {
        const scope = root?.querySelectorAll ? root : document;
        scope.querySelectorAll('select').forEach(initSelect);
        scope.querySelectorAll('input[type="date"],input[type="datetime-local"]').forEach(initCalendar);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initialize(document);

        new MutationObserver(mutations => {
            mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
                if (node.nodeType !== Node.ELEMENT_NODE) return;
                if (node.matches?.('select')) initSelect(node);
                if (node.matches?.('input[type="date"],input[type="datetime-local"]')) initCalendar(node);
                initialize(node);
            }));
        }).observe(document.body, { childList: true, subtree: true });
    });
})();
