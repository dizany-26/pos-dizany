@extends('layouts.catalogo')

@section('title', ($config->nombre_empresa ?? 'DIZANY') . ' | Catálogo')

@push('styles')
    <link href="{{ asset('css/catalago/catalago.css') }}?v={{ filemtime(public_path('css/catalago/catalago.css')) }}" rel="stylesheet">
@endpush

@section('content')
@php
    $empresa = $config->nombre_empresa ?? 'DIZANY';
    $telefono = preg_replace('/\D+/', '', $config->telefono ?? '973451688');
    $telefono = strlen($telefono) === 9 ? '51' . $telefono : $telefono;
    $telefonoVisible = strlen($telefono) === 11 && str_starts_with($telefono, '51')
        ? '+51 ' . substr($telefono, 2, 3) . ' ' . substr($telefono, 5, 3) . ' ' . substr($telefono, 8, 3)
        : '+' . $telefono;
    $direccionTienda = $config->direccion ?? 'Pacaipampa, Piura';
    $mensaje = $config->mensaje_bienvenida ?? 'Todo lo que necesitas, cerca de ti y a precios que te encantarán.';
@endphp

<header class="site-header">
    <a class="brand" href="{{ route('inicio') }}" aria-label="Ir al inicio">
        @if(!empty($config->logo))
            <img src="{{ asset('uploads/config/' . $config->logo) }}" alt="Logo de {{ $empresa }}">
        @else
            <span class="brand-mark">D</span>
        @endif
        <span><strong>{{ $empresa }}</strong><small>{{ $config->rubro ?? 'Tienda y licorería' }}</small></span>
    </a>

    <div class="header-store-details" aria-label="Información de la tienda">
        <span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.2 7-12a7 7 0 1 0-14 0c0 6.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>
            <span><small>Tienda física</small><strong>{{ $direccionTienda }}</strong></span>
        </span>
        <span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.7a8.5 8.5 0 0 1-12.6 7.4L3 20.4l1.3-4.7a8.5 8.5 0 1 1 16.2-4Z"/><path d="M8.2 7.8c.2-.4.4-.4.7-.4h.4c.1 0 .3 0 .4.4l.8 1.9c.1.2.1.4 0 .6l-.6.8c-.2.2-.1.4 0 .6.7 1.2 1.7 2.1 2.9 2.7"/></svg>
            <span><small>WhatsApp</small><strong>{{ $telefonoVisible }}</strong></span>
        </span>
    </div>

    <nav class="header-actions">
        <button type="button" class="catalog-theme-toggle" data-catalog-theme-toggle
                aria-label="Cambiar a modo oscuro" aria-pressed="false" title="Cambiar a modo oscuro">
            <span class="catalog-theme-track" aria-hidden="true">
                <svg class="catalog-theme-icon catalog-theme-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3.5"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                <svg class="catalog-theme-icon catalog-theme-moon" viewBox="0 0 24 24"><path d="M20 15.2A8.4 8.4 0 0 1 8.8 4a8.5 8.5 0 1 0 11.2 11.2Z"/></svg>
                <span class="catalog-theme-thumb"></span>
            </span>
        </button>
        <div class="header-menu">
            <button type="button" class="menu-trigger" data-menu-trigger aria-label="Abrir menú" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <div class="menu-dropdown" data-menu-dropdown hidden>
                <button type="button" data-open-cart>Ver mi pedido</button>
                @if(!empty($config->telefono))
                    <a href="https://wa.me/{{ $telefono }}" target="_blank" rel="noopener">Contactar por WhatsApp</a>
                @endif
            </div>
        </div>
        @auth
            <a class="access-link" href="{{ route(auth()->user()->rutaInicio()) }}"
               target="_blank" rel="noopener" aria-label="Ir al panel del sistema" title="Ir al panel">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h7v7H4zM13 4h7v5h-7zM13 11h7v9h-7zM4 13h7v7H4z"/></svg>
                Ir al panel
            </a>
        @else
            <a class="access-link" href="{{ route('login') }}"
               target="_blank" rel="noopener" aria-label="Acceso al sistema" title="Acceso al sistema">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-4-4 5-5-5-5m5 5H3"/></svg>
                Acceso al sistema
            </a>
        @endauth
        <button class="header-cart" type="button" data-open-cart aria-label="Abrir carrito">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h2l2.4 11.3a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 7H6m4 13h.01M18 20h.01"/></svg>
            <span class="cart-label">Mi pedido</span>
            <b data-cart-count>0</b>
        </button>
    </nav>
</header>

<main>
    <section class="catalog-shell" id="productos">
        <aside class="catalog-sidebar" data-catalog-sidebar>
            <div class="sidebar-head">
                <div><small>Explorar</small><strong>Categorías</strong></div>
                <button type="button" data-toggle-categories aria-label="Ocultar categorías">‹</button>
            </div>
            <div class="category-filter" id="categoryFilter">
                <button type="button" class="active" data-category="all"><span>Todos los productos</span></button>
                @foreach($categorias as $categoria)
                    <button type="button" data-category="{{ $categoria->id }}"><span>{{ $categoria->nombre }}</span></button>
                @endforeach
            </div>
            <button type="button" class="clear-filters" data-clear-filters>Limpiar filtros</button>
        </aside>

        <div class="catalog-content">
        <div class="section-heading">
            <div>
                <span class="section-kicker">Nuestro catálogo</span>
                <span class="section-subtitle">Elige un producto para consultar sus presentaciones y preparar tu pedido.</span>
            </div>
        </div>

        <label class="header-search catalog-search" for="searchInput">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.35-4.35m2.35-5.15A7.5 7.5 0 1 1 4 11.5a7.5 7.5 0 0 1 15 0Z"/></svg>
            <input id="searchInput" type="search" placeholder="Buscar por nombre o categoría..." autocomplete="off">
        </label>

        <div class="product-grid" id="productContainer">
            @forelse($productos as $producto)
                @php
                    $stock = (int) ($producto->stock_total ?? 0);
                    $imagenesCatalogo = collect([$producto->imagen])
                        ->filter()
                        ->merge($producto->imagenesCatalogo->pluck('imagen'))
                        ->take(3)
                        ->map(fn ($imagen) => asset('uploads/productos/' . $imagen))
                        ->values();
                    $presentaciones = collect([
                        ['key' => 'unidad', 'name' => 'Unidad', 'factor' => 1, 'price' => (float) $producto->precio_venta],
                        ['key' => 'paquete', 'name' => 'Paquete', 'factor' => (int) $producto->unidades_por_paquete, 'price' => (float) $producto->precio_paquete],
                        ['key' => 'caja', 'name' => 'Caja', 'factor' => $producto->paquetes_por_caja
                            ? (int) $producto->unidades_por_paquete * (int) $producto->paquetes_por_caja
                            : (int) $producto->unidades_por_caja, 'price' => (float) $producto->precio_caja],
                    ])->filter(fn ($item) => $item['factor'] > 0 && $item['price'] > 0)->values();
                    $principal = $presentaciones->first();
                    $precioPrincipalFinal = $principal
                        ? round($principal['price'] * (1 + ((float) $igv / 100)), 2)
                        : null;
                @endphp
                <article class="product-card"
                    data-product
                    data-name="{{ Illuminate\Support\Str::lower($producto->nombre . ' ' . ($producto->categoria->nombre ?? '') . ' ' . ($producto->descripcion ?? '')) }}"
                    data-category="{{ $producto->categoria_id }}"
                    data-id="{{ $producto->id }}"
                    data-stock="{{ $stock }}"
                    data-description="{{ $producto->descripcion ?: 'Disponible en nuestra tienda.' }}"
                    data-category-name="{{ $producto->categoria->nombre ?? 'Producto' }}"
                    data-images='@json($imagenesCatalogo)'
                    aria-disabled="{{ $stock <= 0 ? 'true' : 'false' }}">
                    @if($stock <= 0)
                        <div class="sold-out-cover" aria-label="Producto agotado">
                            <span>Agotado</span>
                        </div>
                    @endif
                    <div class="product-visual">
                        @if($producto->imagen)
                            <img src="{{ asset('uploads/productos/' . $producto->imagen) }}" alt="{{ $producto->nombre }}" loading="lazy">
                        @else
                            <span class="image-placeholder">D</span>
                        @endif
                        @if($stock > 0)
                            <span class="stock-pill">En stock</span>
                        @else
                            <span class="stock-pill sold-out">Agotado</span>
                        @endif
                    </div>
                    <div class="product-info">
                        <span class="product-category">{{ $producto->categoria->nombre ?? 'Producto' }}</span>
                        <h3>{{ $producto->nombre }}</h3>
                        <p>{{ $producto->descripcion ?: 'Disponible en nuestra tienda.' }}</p>
                        @if($presentaciones->isNotEmpty())
                            <div class="product-presentations" aria-label="Presentaciones disponibles">
                                @foreach($presentaciones as $presentacion)
                                    <span>
                                        {{ $presentacion['name'] }}
                                        <b>{{ $presentacion['factor'] }} un.</b>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if($principal)
                            <div class="product-buy">
                                <div class="price-wrap">
                                    <small>Desde</small>
                                    <strong>S/ {{ number_format($precioPrincipalFinal, 2) }}</strong>
                                    @if($igv > 0)<span class="tax-included">Incl. IGV</span>@endif
                                </div>
                                <div class="product-actions">
                                    <button type="button" class="options-button" data-view-product {{ $stock <= 0 ? 'disabled' : '' }}>
                                        Detalles
                                    </button>
                                    <button type="button" class="add-button" {{ $stock <= 0 ? 'disabled' : '' }}
                                        data-add-product
                                        data-id="{{ $producto->id }}"
                                        data-name="{{ $producto->nombre }}"
                                        data-image="{{ $producto->imagen ? asset('uploads/productos/' . $producto->imagen) : '' }}"
                                        data-stock="{{ $stock }}"
                                        data-presentations='@json($presentaciones)'>
                                        <span>+</span><b>Agregar</b>
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="unavailable-price">Precio por consultar</div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-products">Aún no hay productos publicados.</div>
            @endforelse
        </div>
        <div class="no-results" id="noResults" hidden>
            <span>⌕</span><h3>No encontramos coincidencias</h3><p>Prueba con otro nombre o categoría.</p>
        </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div><strong>{{ $empresa }}</strong><span>{{ $direccionTienda }}</span></div>
    <p>© {{ date('Y') }} {{ $empresa }}. Todos los derechos reservados.</p>
</footer>

<div class="cart-overlay" data-cart-overlay></div>
<aside class="cart-drawer" data-cart-drawer aria-hidden="true">
    <div class="cart-head">
        <div><span>Tu selección</span><h2>Mi pedido <b data-cart-count>0</b></h2></div>
        <button type="button" data-close-cart aria-label="Cerrar carrito">×</button>
    </div>
    <div class="cart-items" data-cart-items></div>
    <div class="cart-empty" data-cart-empty>
        <span>🛒</span><h3>Tu carrito está vacío</h3><p>Agrega productos del catálogo para preparar tu pedido.</p>
        <button type="button" data-close-cart>Explorar productos</button>
    </div>
    <div class="cart-summary" data-cart-summary hidden>
        <details class="customer-details">
            <summary>
                <span class="customer-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.5-4 3.1-6.2 7-6.2s6.5 2.2 7 6.2"/></svg>
                </span>
                <span><b>Datos del pedido</b><small>Cliente y forma de entrega</small></span>
                <i>⌄</i>
            </summary>
            <div class="customer-fields">
                <label>Nombre completo<input type="text" data-customer-name placeholder="Tu nombre" autocomplete="name"></label>
                <label>Teléfono<input type="tel" data-customer-phone placeholder="Ej. 958 196 510" autocomplete="tel"></label>
                <fieldset class="delivery-options">
                    <legend>Forma de entrega</legend>
                    <label>
                        <input type="radio" name="delivery_type" value="domicilio" data-delivery-type checked>
                        <span><b>Entrega a domicilio</b><small>Enviaremos el pedido a tu dirección</small></span>
                    </label>
                    <label>
                        <input type="radio" name="delivery_type" value="tienda" data-delivery-type>
                        <span><b>Recoger en tienda</b><small>Recoge el pedido en nuestro local</small></span>
                    </label>
                </fieldset>
                <label data-address-field>Dirección o referencia<textarea data-customer-address rows="2" placeholder="Indica dónde entregar el pedido"></textarea></label>
                <p class="form-error" data-form-error hidden>Completa los datos requeridos para continuar.</p>
            </div>
        </details>
        <div><span>Productos</span><b data-cart-units>0</b></div>
        <div class="cart-total">
            <span>Total estimado @if($igv > 0)<small class="tax-included">Incl. IGV</small>@endif</span>
            <strong>S/ <span data-cart-total>0.00</span></strong>
        </div>
        <button type="button" class="whatsapp-checkout" data-send-order>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 11.7a8.5 8.5 0 0 1-12.6 7.4L3 20.4l1.3-4.7a8.5 8.5 0 1 1 16.2-4Z"/><path d="M8.2 7.8c.2-.4.4-.4.7-.4h.4c.1 0 .3 0 .4.4l.8 1.9c.1.2.1.4 0 .6l-.6.8c-.2.2-.1.4 0 .6.7 1.2 1.7 2.1 2.9 2.7.2.1.4.1.6-.1l.8-1c.2-.2.4-.2.6-.1l2 .9c.2.1.4.2.4.4 0 .2-.1 1.3-.7 1.8-.5.5-1.3.8-2.1.7-1.1-.2-2.5-.7-4.2-2.2-2-1.8-3.3-4.1-3.4-4.3-.1-.2-.8-1.1-.8-2.1 0-1 .5-1.5.7-1.8Z"/></svg>
            Enviar pedido por WhatsApp
        </button>
        <small>Confirmaremos disponibilidad y entrega por WhatsApp.</small>
    </div>
</aside>

<div class="product-modal" data-product-modal hidden>
    <div class="product-modal-backdrop" data-close-product></div>
    <section class="product-modal-card" role="dialog" aria-modal="true" aria-labelledby="modalProductName">
        <button type="button" class="modal-close" data-close-product aria-label="Cerrar">×</button>
        <div class="modal-product-gallery">
            <div class="modal-product-image" data-modal-image></div>
            <div class="modal-product-thumbnails" data-modal-thumbnails hidden></div>
        </div>
        <div class="modal-product-copy">
            <div class="modal-product-meta">
                <span class="modal-field-title">Categoría</span>
                <strong data-modal-category></strong>
            </div>
            <h2 id="modalProductName" data-modal-name></h2>
            <div class="modal-product-description">
                <span class="modal-field-title">Descripción</span>
                <p data-modal-description></p>
            </div>
            <label class="modal-presentation">Presentación<select data-modal-presentation></select></label>
            <div class="modal-price">
                S/ <strong data-modal-price>0.00</strong>
                @if($igv > 0)<span class="tax-included">Incl. IGV</span>@endif
            </div>
            <div class="modal-actions">
                <div class="quantity-picker">
                    <button type="button" data-modal-minus>−</button>
                    <b data-modal-quantity>1</b>
                    <button type="button" data-modal-plus>+</button>
                </div>
                <button type="button" class="modal-add" data-modal-add>Añadir al carrito</button>
            </div>
        </div>
    </section>
</div>

<div id="catalogData" data-phone="{{ $telefono }}" data-business="{{ $empresa }}" data-igv="{{ (float) $igv }}"></div>
@endsection

@push('scripts')
    <script src="{{ asset('js/catalogo.js') }}?v={{ filemtime(public_path('js/catalogo.js')) }}" defer></script>
@endpush
