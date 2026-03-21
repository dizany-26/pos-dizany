import './bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';



const enhanceNewModalTitles = () => {
    document.querySelectorAll('.modal .modal-title').forEach((title) => {
        const plainText = (title.textContent || '').trim();
        const isCreateTitle = /^(nuevo|nueva)\b/i.test(plainText);
        const hasPrefix = title.querySelector('.modal-title-prefix');
        const hasAnyIcon = title.querySelector('i, svg');

        if (!isCreateTitle || hasPrefix || hasAnyIcon) {
            return;
        }

        const prefix = document.createElement('span');
        prefix.className = 'modal-title-prefix';
        prefix.setAttribute('aria-hidden', 'true');
        prefix.innerHTML = '<i class="fa-solid fa-plus"></i>';
        title.prepend(prefix);
    });
};

document.addEventListener('DOMContentLoaded', () => {
    enhanceNewModalTitles();

    document.querySelectorAll('.modal').forEach((modalElement) => {
        modalElement.addEventListener('show.bs.modal', enhanceNewModalTitles);
    });
});
