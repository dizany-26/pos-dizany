@php
    $stock = (int) ($detalleProducto->stock_total ?? 0);
    $imagenes = collect([$detalleProducto->imagen])
        ->filter()
        ->merge($detalleProducto->imagenesCatalogo->pluck('imagen'))
        ->unique()
        ->take(3)
        ->map(fn ($imagen) => asset('uploads/productos/' . $imagen))
        ->values();
    $presentaciones = collect([
        ['key' => 'unidad', 'name' => 'Unidad', 'factor' => 1, 'price' => (float) $detalleProducto->precio_venta],
        ['key' => 'paquete', 'name' => 'Paquete', 'factor' => (int) $detalleProducto->unidades_por_paquete, 'price' => (float) $detalleProducto->precio_paquete],
        ['key' => 'caja', 'name' => 'Caja', 'factor' => $detalleProducto->paquetes_por_caja
            ? (int) $detalleProducto->unidades_por_paquete * (int) $detalleProducto->paquetes_por_caja
            : (int) $detalleProducto->unidades_por_caja, 'price' => (float) $detalleProducto->precio_caja],
    ])->filter(fn ($item) => $item['factor'] > 0 && $item['price'] > 0)->values();
@endphp

<section class="catalog-detail-page" data-detail-product>
    <nav class="catalog-detail-breadcrumb" aria-label="Ruta del producto">
        <a href="{{ route('inicio') }}">Catálogo</a><span>›</span>
        <span>{{ $detalleProducto->categoria->nombre ?? 'Productos' }}</span><span>›</span>
        <strong>{{ $detalleProducto->nombre }}</strong>
    </nav>

    <article class="catalog-detail-card">
        <div class="catalog-detail-gallery">
            <div class="catalog-detail-main-image {{ $imagenes->isNotEmpty() ? 'has-product-image' : '' }}"
                 @if($imagenes->isNotEmpty()) style="--product-image: url('{{ $imagenes->first() }}')" @endif>
                @if($imagenes->isNotEmpty())
                    <img src="{{ $imagenes->first() }}" alt="{{ $detalleProducto->nombre }}" data-detail-main-image>
                @else
                    <span>D</span>
                @endif
                <span class="catalog-detail-stock {{ $stock > 0 ? 'available' : 'sold-out' }}">
                    {{ $stock > 0 ? 'Disponible' : 'Agotado' }}
                </span>
            </div>
            @if($imagenes->count() > 1)
                <div class="catalog-detail-thumbnails" aria-label="Imágenes del producto">
                    @foreach($imagenes as $imagen)
                        <button type="button" class="{{ $loop->first ? 'active' : '' }}"
                                data-detail-thumbnail="{{ $imagen }}">
                            <img src="{{ $imagen }}" alt="Vista {{ $loop->iteration }} de {{ $detalleProducto->nombre }}">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="catalog-detail-copy">
            <span class="catalog-detail-category">{{ $detalleProducto->categoria->nombre ?? 'Producto' }}</span>
            <h1>{{ $detalleProducto->nombre }}</h1>
            @if($detalleProducto->marca)
                <span class="catalog-detail-brand">Marca: <b>{{ $detalleProducto->marca->nombre }}</b></span>
            @endif
            <p>{{ $detalleProducto->descripcion ?: 'Disponible en nuestra tienda.' }}</p>

            @if($presentaciones->isNotEmpty())
                @php
                    $precioDetalleInicial = $presentaciones->first()['price'] * (1 + ((float) $igv / 100));
                @endphp
                <div class="catalog-detail-price">
                    <small>Precio</small>
                    <strong>S/ <span data-detail-price>{{ number_format($precioDetalleInicial, 2) }}</span></strong>
                    @if($igv > 0)<em>Incluye IGV</em>@endif
                </div>
                <div class="catalog-detail-purchase" data-detail-purchase>
                    <label for="detailPresentation">Presentación</label>
                    <select id="detailPresentation" data-detail-presentation {{ $stock <= 0 ? 'disabled' : '' }}>
                        @foreach($presentaciones as $presentacion)
                            <option value="{{ $presentacion['key'] }}" {{ $presentacion['factor'] > $stock ? 'disabled' : '' }}>
                                {{ $presentacion['name'] }} · {{ $presentacion['factor'] }} un.
                            </option>
                        @endforeach
                    </select>
                    <div class="catalog-detail-quantity" aria-label="Cantidad">
                        <button type="button" data-detail-minus aria-label="Disminuir cantidad">−</button>
                        <b data-detail-quantity>1</b>
                        <button type="button" data-detail-plus aria-label="Aumentar cantidad">+</button>
                    </div>
                    <button type="button" class="catalog-detail-add"
                            data-add-product
                            data-id="{{ $detalleProducto->id }}"
                            data-name="{{ $detalleProducto->nombre }}"
                            data-image="{{ $imagenes->first() ?? '' }}"
                            data-stock="{{ $stock }}"
                            data-presentations='@json($presentaciones)'
                            {{ $stock <= 0 ? 'disabled' : '' }}>
                        <span>+</span> Agregar al pedido
                    </button>
                </div>
            @else
                <div class="catalog-detail-unavailable">Precio por consultar</div>
            @endif
            <a href="{{ route('inicio') }}" class="catalog-detail-back">← Volver al catálogo</a>
        </div>
    </article>

    @if($productosRelacionados->isNotEmpty())
        <section class="catalog-related">
            <div class="catalog-related-heading">
                <span>También podrían interesarte</span>
                <h2>Productos relacionados</h2>
            </div>
            <div class="catalog-related-grid">
                @foreach($productosRelacionados as $relacionado)
                    @php $stockRelacionado = (int) ($relacionado->stock_total ?? 0); @endphp
                    <a href="{{ route('catalogo.producto', $relacionado) }}"
                       class="catalog-related-card {{ $stockRelacionado <= 0 ? 'is-sold-out' : '' }}">
                        <div class="{{ $relacionado->imagen ? 'has-product-image' : '' }}"
                             @if($relacionado->imagen) style="--product-image: url('{{ asset('uploads/productos/' . $relacionado->imagen) }}')" @endif>
                            @if($relacionado->imagen)
                                <img src="{{ asset('uploads/productos/' . $relacionado->imagen) }}" alt="{{ $relacionado->nombre }}" loading="lazy">
                            @else
                                <span>D</span>
                            @endif
                            @if($stockRelacionado <= 0)
                                <span class="catalog-related-sold-out">Agotado</span>
                            @endif
                        </div>
                        <small>{{ $relacionado->categoria->nombre ?? 'Producto' }}</small>
                        <h3>{{ $relacionado->nombre }}</h3>
                        <p>{{ $relacionado->descripcion ?: 'Disponible en nuestra tienda.' }}</p>
                        <strong>Ver detalles →</strong>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</section>
