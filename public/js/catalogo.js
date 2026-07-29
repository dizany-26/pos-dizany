(() => {
    const storageKey = 'dizany_catalog_cart_v1';
    const products = [...document.querySelectorAll('[data-product]')];
    const drawer = document.querySelector('[data-cart-drawer]');
    const itemsBox = document.querySelector('[data-cart-items]');
    const emptyBox = document.querySelector('[data-cart-empty]');
    const summary = document.querySelector('[data-cart-summary]');
    const data = document.getElementById('catalogData');
    let category = 'all';
    let cart = [];
    let modalProduct = null;
    let modalQuantity = 1;

    try {
        cart = JSON.parse(localStorage.getItem(storageKey)) || [];
    } catch (_) {
        cart = [];
    }

    const currentCatalog = new Map(products.map(card => {
        const add = card.querySelector('[data-add-product]');
        if (!add) return [card.dataset.id, null];
        return [card.dataset.id, {
            name: add.dataset.name,
            image: add.dataset.image,
            stock: Number(add.dataset.stock),
            presentations: JSON.parse(add.dataset.presentations)
        }];
    }));
    cart = cart.map(item => {
        const current = currentCatalog.get(String(item.id));
        return current ? {...item, ...current} : null;
    }).filter(Boolean);

    const money = value => Number(value || 0).toFixed(2);
    const save = () => localStorage.setItem(storageKey, JSON.stringify(cart));
    const presentation = item => item.presentations.find(p => p.key === item.presentation) || item.presentations[0];
    const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[char]);

    function openCart() {
        document.body.classList.add('cart-open');
        drawer.setAttribute('aria-hidden', 'false');
    }

    function closeCart() {
        document.body.classList.remove('cart-open');
        drawer.setAttribute('aria-hidden', 'true');
    }

    function openProduct(card) {
        const add = card.querySelector('[data-add-product]');
        if (!add || add.disabled) return;
        modalProduct = {
            id: add.dataset.id,
            name: add.dataset.name,
            image: add.dataset.image,
            stock: Number(add.dataset.stock),
            description: card.dataset.description,
            category: card.dataset.categoryName,
            presentations: JSON.parse(add.dataset.presentations)
        };
        modalQuantity = 1;
        const modal = document.querySelector('[data-product-modal]');
        modal.querySelector('[data-modal-name]').textContent = modalProduct.name;
        modal.querySelector('[data-modal-category]').textContent = modalProduct.category;
        modal.querySelector('[data-modal-description]').textContent = modalProduct.description;
        modal.querySelector('[data-modal-image]').innerHTML = modalProduct.image
            ? `<img src="${escapeHtml(modalProduct.image)}" alt="${escapeHtml(modalProduct.name)}">`
            : '<span>D</span>';
        const select = modal.querySelector('[data-modal-presentation]');
        select.innerHTML = modalProduct.presentations
            .filter(p => p.factor <= modalProduct.stock)
            .map(p => `<option value="${p.key}">${p.name}</option>`).join('');
        updateModal();
        modal.hidden = false;
        document.body.classList.add('modal-open');
    }

    function closeProduct() {
        document.querySelector('[data-product-modal]').hidden = true;
        document.body.classList.remove('modal-open');
        modalProduct = null;
    }

    function animateToCart(source) {
        const target = document.querySelector('.header-cart');
        if (!source || !target) return;

        const sourceRect = source.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();
        const image = source.querySelector('img');
        const flyer = document.createElement('span');
        flyer.className = 'cart-flyer';
        flyer.innerHTML = image
            ? `<img src="${escapeHtml(image.src)}" alt="">`
            : '<b>+</b>';
        flyer.style.left = `${sourceRect.left + sourceRect.width / 2 - 24}px`;
        flyer.style.top = `${sourceRect.top + sourceRect.height / 2 - 24}px`;
        document.body.appendChild(flyer);

        requestAnimationFrame(() => {
            flyer.style.transform = `translate(${targetRect.left + targetRect.width / 2 - sourceRect.left - sourceRect.width / 2}px, ${targetRect.top + targetRect.height / 2 - sourceRect.top - sourceRect.height / 2}px) scale(.25)`;
            flyer.style.opacity = '0';
        });
        target.classList.remove('cart-bump');
        window.setTimeout(() => target.classList.add('cart-bump'), 420);
        window.setTimeout(() => {
            flyer.remove();
            target.classList.remove('cart-bump');
        }, 850);
    }

    function modalPresentation() {
        const key = document.querySelector('[data-modal-presentation]').value;
        return modalProduct.presentations.find(p => p.key === key) || modalProduct.presentations[0];
    }

    function updateModal() {
        const selected = modalPresentation();
        const max = Math.max(1, Math.floor(modalProduct.stock / selected.factor));
        modalQuantity = Math.min(modalQuantity, max);
        document.querySelector('[data-modal-quantity]').textContent = modalQuantity;
        document.querySelector('[data-modal-price]').textContent = money(selected.price * modalQuantity);
        document.querySelector('[data-modal-plus]').disabled = modalQuantity >= max;
    }

    function renderCart() {
        cart = cart.filter(item => item.presentations?.length && item.stock > 0);
        let total = 0;
        itemsBox.innerHTML = '';

        cart.forEach((item, index) => {
            const selected = presentation(item);
            const max = Math.floor(item.stock / selected.factor);
            item.quantity = Math.max(1, Math.min(item.quantity, max || 1));
            total += selected.price * item.quantity;

            const row = document.createElement('div');
            row.className = 'cart-row';
            const picture = item.image
                ? `<img src="${escapeHtml(item.image)}" alt="">`
                : '<span class="cart-thumb"></span>';
            row.innerHTML = `${picture}
                <div class="cart-row-info">
                    <h4>${escapeHtml(item.name)}</h4>
                    <select data-presentation="${index}">
                        ${item.presentations.filter(p => p.factor <= item.stock).map(p =>
                            `<option value="${p.key}" ${p.key === selected.key ? 'selected' : ''}>${p.name} · S/ ${money(p.price)}</option>`
                        ).join('')}
                    </select>
                    <div class="cart-row-controls">
                        <button type="button" data-minus="${index}">−</button>
                        <b>${item.quantity}</b>
                        <button type="button" data-plus="${index}" ${item.quantity >= max ? 'disabled' : ''}>+</button>
                    </div>
                </div>
                <div class="cart-row-price">
                    <strong>S/ ${money(selected.price * item.quantity)}</strong>
                    <button type="button" data-remove="${index}" aria-label="Quitar">×</button>
                </div>`;
            itemsBox.appendChild(row);
        });

        const productCount = new Set(cart.map(item => item.id)).size;
        document.querySelectorAll('[data-cart-count]').forEach(el => el.textContent = productCount);
        document.querySelectorAll('[data-cart-total]').forEach(el => el.textContent = money(total));
        document.querySelector('[data-cart-units]').textContent = productCount;
        emptyBox.hidden = cart.length > 0;
        summary.hidden = cart.length === 0;
        save();
    }

    document.addEventListener('click', event => {
        const card = event.target.closest('[data-product]');
        if (card && !event.target.closest('[data-add-product]')) {
            openProduct(card);
            return;
        }
        if (event.target.closest('[data-close-product]')) closeProduct();
        if (event.target.closest('[data-modal-minus]') && modalQuantity > 1) {
            modalQuantity--;
            updateModal();
        }
        if (event.target.closest('[data-modal-plus]')) {
            modalQuantity++;
            updateModal();
        }
        if (event.target.closest('[data-modal-add]') && modalProduct) {
            const animationSource = document.querySelector('.modal-product-image');
            const selected = modalPresentation();
            const existing = cart.find(item => item.id === modalProduct.id);
            if (existing) {
                existing.presentation = selected.key;
                existing.quantity = Math.min(
                    existing.quantity + modalQuantity,
                    Math.floor(existing.stock / selected.factor)
                );
            }
            else cart.push({...modalProduct, presentation: selected.key, quantity: modalQuantity});
            renderCart();
            animateToCart(animationSource);
            closeProduct();
            return;
        }
        if (event.target.closest('[data-menu-trigger]')) {
            const menu = document.querySelector('[data-menu-dropdown]');
            menu.hidden = !menu.hidden;
        } else if (!event.target.closest('[data-menu-dropdown]')) {
            document.querySelector('[data-menu-dropdown]').hidden = true;
        }
        if (event.target.closest('[data-toggle-categories]')) {
            document.querySelector('[data-catalog-sidebar]').classList.toggle('collapsed');
        }
        if (event.target.closest('[data-clear-filters]')) {
            category = 'all';
            document.getElementById('searchInput').value = '';
            document.querySelectorAll('[data-category]').forEach(el => el.classList.toggle('active', el.dataset.category === 'all'));
            filterProducts();
        }
        const add = event.target.closest('[data-add-product]');
        if (add) {
            const presentations = JSON.parse(add.dataset.presentations);
            const existing = cart.find(item => item.id === add.dataset.id);
            if (existing) {
                const selected = presentation(existing);
                const max = Math.floor(existing.stock / selected.factor);
                existing.quantity = Math.min(existing.quantity + 1, max);
            } else {
                cart.push({
                    id: add.dataset.id,
                    name: add.dataset.name,
                    image: add.dataset.image,
                    stock: Number(add.dataset.stock),
                    presentations,
                    presentation: presentations[0].key,
                    quantity: 1
                });
            }
            renderCart();
            animateToCart(add.closest('[data-product]').querySelector('.product-visual'));
            return;
        }

        if (event.target.closest('[data-open-cart]')) openCart();
        if (event.target.closest('[data-close-cart]') || event.target.closest('[data-cart-overlay]')) closeCart();

        const remove = event.target.closest('[data-remove]');
        if (remove) cart.splice(Number(remove.dataset.remove), 1);
        const minus = event.target.closest('[data-minus]');
        if (minus) {
            const item = cart[Number(minus.dataset.minus)];
            item.quantity > 1 ? item.quantity-- : cart.splice(Number(minus.dataset.minus), 1);
        }
        const plus = event.target.closest('[data-plus]');
        if (plus) {
            const item = cart[Number(plus.dataset.plus)];
            const selected = presentation(item);
            item.quantity = Math.min(item.quantity + 1, Math.floor(item.stock / selected.factor));
        }
        if (remove || minus || plus) renderCart();
    });

    document.addEventListener('change', event => {
        if (event.target.matches('[data-modal-presentation]')) {
            modalQuantity = 1;
            updateModal();
            return;
        }
        if (!event.target.matches('[data-presentation]')) return;
        const item = cart[Number(event.target.dataset.presentation)];
        item.presentation = event.target.value;
        item.quantity = Math.min(item.quantity, Math.floor(item.stock / presentation(item).factor)) || 1;
        renderCart();
    });

    document.querySelector('[data-send-order]').addEventListener('click', () => {
        const customer = {
            name: document.querySelector('[data-customer-name]').value.trim(),
            phone: document.querySelector('[data-customer-phone]').value.trim(),
            address: document.querySelector('[data-customer-address]').value.trim(),
            note: document.querySelector('[data-customer-note]').value.trim()
        };
        const error = document.querySelector('[data-form-error]');
        if (!customer.name || !customer.phone || !customer.address) {
            error.hidden = false;
            document.querySelector('.customer-details').open = true;
            return;
        }
        error.hidden = true;
        const lines = cart.map(item => {
            const selected = presentation(item);
            return `• ${item.quantity} ${selected.name}${item.quantity > 1 ? 's' : ''} de ${item.name} — S/ ${money(selected.price * item.quantity)}`;
        });
        const total = cart.reduce((sum, item) => sum + presentation(item).price * item.quantity, 0);
        const message = [
            `Hola ${data.dataset.business}, deseo realizar este pedido:`,
            '',
            `*Cliente:* ${customer.name}`,
            `*Teléfono:* ${customer.phone}`,
            `*Dirección:* ${customer.address}`,
            customer.note ? `*Nota:* ${customer.note}` : '',
            '',
            ...lines,
            '',
            `*Total estimado: S/ ${money(total)}*`,
            '',
            '¿Podrían confirmarme la disponibilidad y el método de entrega?'
        ].join('\n');
        window.open(`https://wa.me/${data.dataset.phone}?text=${encodeURIComponent(message)}`, '_blank', 'noopener');
    });

    function filterProducts() {
        const term = document.getElementById('searchInput').value.trim().toLocaleLowerCase('es');
        let visible = 0;
        products.forEach(product => {
            const show = (category === 'all' || product.dataset.category === category)
                && product.dataset.name.includes(term);
            product.hidden = !show;
            if (show) visible++;
        });
        document.getElementById('visibleCount').textContent = visible;
        document.getElementById('noResults').hidden = visible > 0;
    }

    document.getElementById('searchInput').addEventListener('input', filterProducts);
    document.getElementById('categoryFilter').addEventListener('click', event => {
        const button = event.target.closest('[data-category]');
        if (!button) return;
        category = button.dataset.category;
        document.querySelectorAll('[data-category]').forEach(el => el.classList.toggle('active', el === button));
        filterProducts();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeCart();
            closeProduct();
        }
    });
    renderCart();
})();
