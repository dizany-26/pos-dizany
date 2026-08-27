document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-catalog-images]').forEach((input) => {
        const field = input.closest('.col-md-4') || input.parentElement;
        const previewContainer = field?.querySelector('[data-catalog-previews]');
        const errorContainer = field?.querySelector('[data-catalog-images-error]');
        let previewUrls = [];

        if (!previewContainer) return;

        const clearPreviews = () => {
            previewUrls.forEach((url) => URL.revokeObjectURL(url));
            previewUrls = [];
            previewContainer.replaceChildren();
            previewContainer.classList.add('d-none');
        };

        const showError = (message = '') => {
            if (!errorContainer) return;
            errorContainer.textContent = message;
            errorContainer.classList.toggle('d-none', !message);
        };

        input.addEventListener('change', () => {
            clearPreviews();
            showError();

            const files = Array.from(input.files || []);
            if (!files.length) return;

            if (files.length > 2) {
                input.value = '';
                showError('Puedes seleccionar como máximo 2 imágenes adicionales.');
                return;
            }

            if (files.some((file) => !file.type.startsWith('image/'))) {
                input.value = '';
                showError('Todos los archivos seleccionados deben ser imágenes.');
                return;
            }

            files.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                previewUrls.push(url);

                const item = document.createElement('figure');
                item.className = 'catalog-image-preview-item';

                const number = document.createElement('span');
                number.className = 'catalog-image-preview-number';
                number.textContent = String(index + 1);

                const image = document.createElement('img');
                image.src = url;
                image.alt = `Vista previa de imagen adicional ${index + 1}`;

                const caption = document.createElement('figcaption');
                caption.textContent = file.name;
                caption.title = file.name;

                item.append(number, image, caption);
                previewContainer.append(item);
            });

            previewContainer.classList.remove('d-none');
        });

        input.form?.addEventListener('reset', () => {
            window.setTimeout(() => {
                clearPreviews();
                showError();
            }, 0);
        });

        window.addEventListener('pagehide', () => {
            previewUrls.forEach((url) => URL.revokeObjectURL(url));
        }, { once: true });
    });
});
