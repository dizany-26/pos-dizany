(() => {
    const storageKey = 'dizany_catalog_cart_v1';
    const themeStorageKey = 'dizany-catalog-theme';
    const themeToggle = document.querySelector('[data-catalog-theme-toggle]');
    const themeColor = document.querySelector('meta[name="theme-color"]');

    function applyCatalogTheme(theme) {
        const selected = theme === 'dark' ? 'dark' : 'light';
        const isDark = selected === 'dark';
        document.documentElement.setAttribute('data-catalog-theme', selected);
        localStorage.setItem(themeStorageKey, selected);
        themeColor?.setAttribute('content', isDark ? '#07162b' : '#ffffff');
        if (themeToggle) {
            themeToggle.setAttribute('aria-pressed', String(isDark));
            themeToggle.setAttribute('aria-label', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
            themeToggle.setAttribute('title', isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        }
    }

    applyCatalogTheme(localStorage.getItem(themeStorageKey));
    themeToggle?.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-catalog-theme');
        applyCatalogTheme(current === 'dark' ? 'light' : 'dark');
    });

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
    let detailQuantity = 1;
    const igvPercent = Math.max(0, Number(data?.dataset.igv || 0));

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
        return current ? {...item, ...current} : item;
    }).filter(item => item?.presentations?.length && item.stock > 0);

    const money = value => Number(value || 0).toFixed(2);
    const finalPrice = basePrice => Number(basePrice || 0) * (1 + igvPercent / 100);
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
            images: JSON.parse(card.dataset.images || '[]'),
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
        const galleryImages = modalProduct.images.length ? modalProduct.images : (modalProduct.image ? [modalProduct.image] : []);
        modal.querySelector('[data-modal-image]').innerHTML = galleryImages[0]
            ? `<img src="${escapeHtml(galleryImages[0])}" alt="${escapeHtml(modalProduct.name)}">`
            : '<span>D</span>';
        const thumbnails = modal.querySelector('[data-modal-thumbnails]');
        thumbnails.hidden = galleryImages.length < 2;
        thumbnails.innerHTML = galleryImages.map((image, index) => `
            <button type="button" class="${index === 0 ? 'active' : ''}" data-gallery-image="${escapeHtml(image)}" aria-label="Ver imagen ${index + 1}">
                <img src="${escapeHtml(image)}" alt="">
            </button>
        `).join('');
        const select = modal.querySelector('[data-modal-presentation]');
        select.innerHTML = modalProduct.presentations
            .filter(p => p.factor <= modalProduct.stock)
            .map(p => `<option value="${p.key}">${p.name} · ${p.factor} un.</option>`).join('');
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
        document.querySelector('[data-modal-price]').textContent = money(finalPrice(selected.price) * modalQuantity);
        document.querySelector('[data-modal-plus]').disabled = modalQuantity >= max;
    }

    function updateDetailQuantity() {
        const purchase = document.querySelector('[data-detail-purchase]');
        if (!purchase) return;
        const add = purchase.querySelector('[data-add-product]');
        const selectedKey = purchase.querySelector('[data-detail-presentation]').value;
        const presentations = JSON.parse(add.dataset.presentations);
        const selected = presentations.find(item => item.key === selectedKey) || presentations[0];
        const max = Math.max(1, Math.floor(Number(add.dataset.stock) / selected.factor));
        detailQuantity = Math.max(1, Math.min(detailQuantity, max));
        const price = document.querySelector('[data-detail-price]');
        if (price) price.textContent = money(finalPrice(selected.price));
        purchase.querySelector('[data-detail-quantity]').textContent = detailQuantity;
        purchase.querySelector('[data-detail-minus]').disabled = detailQuantity <= 1;
        purchase.querySelector('[data-detail-plus]').disabled = detailQuantity >= max;
    }

    function renderCart() {
        cart = cart.filter(item => item.presentations?.length && item.stock > 0);
        let total = 0;
        itemsBox.innerHTML = '';

        cart.forEach((item, index) => {
            const selected = presentation(item);
            const max = Math.floor(item.stock / selected.factor);
            item.quantity = Math.max(1, Math.min(item.quantity, max || 1));
            total += finalPrice(selected.price) * item.quantity;

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
                            `<option value="${p.key}" ${p.key === selected.key ? 'selected' : ''}>${p.name} · ${p.factor} un.</option>`
                        ).join('')}
                    </select>
                    <div class="cart-row-controls">
                        <button type="button" data-minus="${index}">−</button>
                        <b>${item.quantity}</b>
                        <button type="button" data-plus="${index}" ${item.quantity >= max ? 'disabled' : ''}>+</button>
                    </div>
                </div>
                <div class="cart-row-price">
                    <strong>S/ ${money(finalPrice(selected.price) * item.quantity)}</strong>
                    <button type="button" class="remove-product" data-remove="${index}"
                        aria-label="Eliminar producto" title="Eliminar producto">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"/>
                        </svg>
                    </button>
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
            const detailLink = card.querySelector('.options-button[href]');
            if (detailLink && !event.target.closest('a, button')) {
                window.location.href = detailLink.href;
            }
            return;
        }
        if (event.target.closest('[data-close-product]')) closeProduct();
        const galleryButton = event.target.closest('[data-gallery-image]');
        if (galleryButton) {
            const modal = galleryButton.closest('[data-product-modal]');
            const mainImage = modal.querySelector('[data-modal-image] img');
            if (mainImage) mainImage.src = galleryButton.dataset.galleryImage;
            modal.querySelectorAll('[data-gallery-image]').forEach(button => button.classList.toggle('active', button === galleryButton));
        }
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
        if (event.target.closest('[data-toggle-categories]')) {
            document.querySelector('[data-catalog-sidebar]').classList.toggle('collapsed');
        }
        if (event.target.closest('[data-clear-filters]')) {
            category = 'all';
            document.getElementById('searchInput').value = '';
            document.querySelectorAll('[data-category]').forEach(el => el.classList.toggle('active', el.dataset.category === 'all'));
            filterProducts();
        }
        if (event.target.closest('[data-detail-minus]')) {
            detailQuantity = Math.max(1, detailQuantity - 1);
            updateDetailQuantity();
            return;
        }
        if (event.target.closest('[data-detail-plus]')) {
            detailQuantity++;
            updateDetailQuantity();
            return;
        }
        const add = event.target.closest('[data-add-product]');
        if (add) {
            const presentations = JSON.parse(add.dataset.presentations);
            const detailPurchase = add.closest('[data-detail-purchase]');
            const selectedKey = detailPurchase?.querySelector('[data-detail-presentation]')?.value
                || presentations[0].key;
            const requestedQuantity = detailPurchase ? detailQuantity : 1;
            const existing = cart.find(item => item.id === add.dataset.id);
            if (existing) {
                existing.presentation = selectedKey;
                const selected = presentation(existing);
                const max = Math.floor(existing.stock / selected.factor);
                existing.quantity = Math.min(existing.quantity + requestedQuantity, max);
            } else {
                cart.push({
                    id: add.dataset.id,
                    name: add.dataset.name,
                    image: add.dataset.image,
                    stock: Number(add.dataset.stock),
                    presentations,
                    presentation: selectedKey,
                    quantity: requestedQuantity
                });
            }
            renderCart();
            animateToCart(add.closest('[data-product]')?.querySelector('.product-visual')
                || add.closest('[data-detail-product]')?.querySelector('.catalog-detail-main-image')
                || add);
            if (detailPurchase) {
                detailQuantity = 1;
                updateDetailQuantity();
            }
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
        if (event.target.matches('[data-delivery-type]')) {
            const homeDelivery = event.target.value === 'domicilio';
            const addressField = document.querySelector('[data-address-field]');
            const addressInput = document.querySelector('[data-customer-address]');
            addressField.hidden = !homeDelivery;
            addressInput.required = homeDelivery;
            if (!homeDelivery) addressInput.value = '';
            return;
        }
        if (event.target.matches('[data-modal-presentation]')) {
            modalQuantity = 1;
            updateModal();
            return;
        }
        if (event.target.matches('[data-detail-presentation]')) {
            detailQuantity = 1;
            updateDetailQuantity();
            return;
        }
        if (!event.target.matches('[data-presentation]')) return;
        const item = cart[Number(event.target.dataset.presentation)];
        item.presentation = event.target.value;
        item.quantity = Math.min(item.quantity, Math.floor(item.stock / presentation(item).factor)) || 1;
        renderCart();
    });

    document.querySelector('[data-send-order]').addEventListener('click', async event => {
        const sendButton = event.currentTarget;
        const customer = {
            name: document.querySelector('[data-customer-name]').value.trim(),
            phone: document.querySelector('[data-customer-phone]').value.trim(),
            address: document.querySelector('[data-customer-address]').value.trim(),
            delivery: document.querySelector('[data-delivery-type]:checked').value
        };
        const error = document.querySelector('[data-form-error]');
        const needsAddress = customer.delivery === 'domicilio';
        if (!customer.name || !customer.phone || (needsAddress && !customer.address)) {
            error.hidden = false;
            document.querySelector('.customer-details').open = true;
            return;
        }
        error.hidden = true;
        // Se abre dentro del gesto del usuario para que navegadores móviles no
        // bloqueen WhatsApp mientras el pedido se registra en el servidor.
        const whatsappWindow = window.open('', '_blank');
        if (whatsappWindow) whatsappWindow.opener = null;
        sendButton.disabled = true;
        const originalButtonHtml = sendButton.innerHTML;
        sendButton.textContent = 'Preparando pedido...';

        let orderCode;
        try {
            const response = await fetch('/catalogo/pedidos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    cliente: {
                        nombre: customer.name,
                        telefono: customer.phone,
                        entrega: customer.delivery,
                        direccion: customer.address
                    },
                    items: cart.map(item => ({
                        producto_id: Number(item.id),
                        presentacion: presentation(item).key,
                        cantidad: item.quantity
                    }))
                })
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                const validation = result.errors
                    ? Object.values(result.errors).flat().find(Boolean)
                    : null;
                throw new Error(validation || result.message || 'No se pudo registrar el pedido.');
            }
            orderCode = result.pedido.codigo;
        } catch (requestError) {
            whatsappWindow?.close();
            sendButton.disabled = false;
            sendButton.innerHTML = originalButtonHtml;
            error.textContent = requestError.message || 'No se pudo registrar el pedido. Inténtalo nuevamente.';
            error.hidden = false;
            return;
        }

        const now = new Date();
        const orderDate = now.toLocaleString('es-PE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        const lines = cart.map(item => {
            const selected = presentation(item);
            const subtotal = finalPrice(selected.price) * item.quantity;
            return [
                `🔷 *${item.name}*`,
                `${item.quantity} × ${selected.name} (${selected.factor} un.)  *S/ ${money(subtotal)}*`
            ].join('\n');
        });
        const total = cart.reduce((sum, item) => sum + finalPrice(presentation(item).price) * item.quantity, 0);
        const message = [
            `🛒 *NUEVO PEDIDO | ${data.dataset.business.toUpperCase()}*`,
            '━━━━━━━━━━━━━━━━━━',
            `🧾 *Pedido:* ${orderCode}`,
            `🗓️ *Fecha:* ${orderDate}`,
            '',
            '👤 *DATOS DEL CLIENTE*',
            `• *Nombre:* ${customer.name}`,
            `• *Teléfono:* ${customer.phone}`,
            `• *Entrega:* ${needsAddress ? 'Entrega a domicilio' : 'Recoger en tienda'}`,
            needsAddress ? `• *Dirección:* ${customer.address}` : null,
            '',
            '📦 *DETALLE DEL PEDIDO*',
            '──────────────────',
            ...lines,
            '',
            `💵 *TOTAL ESTIMADO: S/ ${money(total)}*`,
            igvPercent > 0 ? `• Precios incluyen IGV (${money(igvPercent)}%)` : null,
            '━━━━━━━━━━━━━━━━━━',
            '',
            '✅ Por favor, confirmar disponibilidad, forma de pago y método de entrega.',
            '',
            `_Pedido generado desde el catálogo de ${data.dataset.business}._`
        ].filter(line => line !== null).join('\n');
        const whatsappUrl = `https://wa.me/${data.dataset.phone}?text=${encodeURIComponent(message)}`;
        if (whatsappWindow) whatsappWindow.location.href = whatsappUrl;
        else window.location.href = whatsappUrl;
        cart = [];
        renderCart();
        closeCart();
        sendButton.disabled = false;
        sendButton.innerHTML = originalButtonHtml;
    });

    function normalizeSearch(value) {
        return String(value ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('es')
            .trim();
    }

    function filterProducts() {
        const term = normalizeSearch(document.getElementById('searchInput').value);
        let visible = 0;
        products.forEach(product => {
            const show = (category === 'all' || product.dataset.category === category)
                && normalizeSearch(product.dataset.name).includes(term);
            product.hidden = !show;
            if (show) visible++;
        });
        document.getElementById('noResults').hidden = visible > 0;
    }

    document.getElementById('searchInput')?.addEventListener('input', filterProducts);
    document.getElementById('categoryFilter')?.addEventListener('click', event => {
        const button = event.target.closest('[data-category]');
        if (!button) return;
        category = button.dataset.category;
        document.querySelectorAll('[data-category]').forEach(el => el.classList.toggle('active', el === button));
        filterProducts();
    });
    document.querySelectorAll('[data-detail-thumbnail]').forEach(button => {
        button.addEventListener('click', () => {
            const mainImage = document.querySelector('[data-detail-main-image]');
            if (!mainImage) return;
            mainImage.src = button.dataset.detailThumbnail;
            mainImage.parentElement?.style.setProperty('--product-image', `url("${button.dataset.detailThumbnail}")`);
            document.querySelectorAll('[data-detail-thumbnail]').forEach(item => {
                item.classList.toggle('active', item === button);
            });
        });
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeCart();
            if (document.querySelector('[data-product-modal]')) closeProduct();
        }
    });
    document.querySelector('[data-customer-address]').required = true;
    updateDetailQuantity();
    renderCart();
})();
