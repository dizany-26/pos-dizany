document.addEventListener('DOMContentLoaded', () => {
    const panel = document.querySelector('[data-theme-config]');
    if (!panel) return;

    const defaults = {
        enabled: false,
        accent: '#0d6efd',
        header_from: '#ffffff', header_to: '#eef4ff', header_gradient: true,
        sidebar_from: '#ffffff', sidebar_to: '#f4f7fb', sidebar_gradient: false,
        footer_from: '#ffffff', footer_to: '#eef4ff', footer_gradient: false,
        table_from: '#eaf2ff', table_to: '#dceaff', table_gradient: false,
        modal_from: '#ffffff', modal_to: '#eef4ff', modal_gradient: false
    };

    const refresh = () => {
        panel.querySelectorAll('[data-theme-preview]').forEach(preview => {
            const key = preview.dataset.themePreview;
            if (key === 'accent') {
                preview.style.background = panel.querySelector('[data-theme-color="accent"]').value;
                return;
            }
            const from = panel.querySelector(`[data-theme-color="${key}_from"]`).value;
            const to = panel.querySelector(`[data-theme-color="${key}_to"]`).value;
            const gradient = panel.querySelector(`[data-theme-gradient="${key}"]`).checked;
            preview.style.background = gradient ? `linear-gradient(135deg, ${from}, ${to})` : from;
        });
    };

    panel.addEventListener('input', refresh);
    panel.querySelector('[data-theme-reset]').addEventListener('click', () => {
        Object.entries(defaults).forEach(([key, value]) => {
            const input = key === 'enabled'
                ? panel.querySelector('[data-theme-enabled]')
                : panel.querySelector(`[data-theme-color="${key}"]`) || panel.querySelector(`[data-theme-gradient="${key.replace('_gradient', '')}"]`);
            if (!input) return;
            input.type === 'checkbox' ? input.checked = value : input.value = value;
        });
        refresh();
    });
    refresh();
});
