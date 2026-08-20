@extends('layouts.app')

@push('styles')
    <link href="{{ asset('css/mostrar_detalles_productos.css') }}?v={{ filemtime(public_path('css/mostrar_detalles_productos.css')) }}" rel="stylesheet" />
@endpush

{{-- Botón atrás (opcional) --}}
@section('header-back')
<button class="btn-header-back" onclick="history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
@endsection

{{-- TÍTULO --}}
@section('header-title')
Productos
@endsection

{{-- BOTONES DERECHA --}}
@section('header-buttons')
@if(auth()->user()->esAdmin() || auth()->user()->tienePermiso('productos.create'))
<a href="{{ route('productos.create') }}" class="btn-gasto">
    <i class="fa-solid fa-plus"></i>
    <span class="btn-text">Nuevo producto</span>
</a>
@endif
@endsection

@section('content')

@section('content')

<div class="card ui-card container-card my-4">

    {{-- HEADER --}}
    <div class="card-header text-center pt-4">
        <h4 class="mb-0 fw-semibold">
            <i class="fa-solid fa-box me-2 text-primary"></i>
            Lista de Productos
        </h4>
    </div>

    <div class="card-body pt-2 pb-4">

        <!-- Filtro y buscador -->
        <form method="GET"
            action="{{ route('productos.index') }}"
            class="row g-3 align-items-end mb-4">

            <div class="col-md-3">
                <select name="categoria_id" class="form-select ui-input">
                    <option value="todos">- Todas las Categorías -</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}"
                            {{ request('categoria_id') == $categoria->id ? 'selected' : '' }}>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <select name="marca_id" class="form-select ui-input">
                    <option value="todos">- Todas las Marcas -</option>
                    @foreach($marcas as $marca)
                        <option value="{{ $marca->id }}"
                            {{ request('marca_id') == $marca->id ? 'selected' : '' }}>
                            {{ $marca->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <div class="ui-search-wrapper">
                    <i class="fas fa-search ui-search-icon"></i>
                    <input type="search"
                        name="search"
                        class="form-control ui-input ui-search-input"
                        placeholder="Buscar código / nombre..."
                        value="{{ request('search') }}">
                </div>
            </div>

            <div class="col-md-2 d-flex justify-content-start">
                <a href="{{ route('productos.export') }}"
                class="btn-soft btn-soft-success d-flex align-items-center gap-2 px-3">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Exportar Excel</span>
                </a>
            </div>
        </form>

        <!-- Tabla de productos -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 ui-table text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">Imagen</th>
                        <th class="text-center">Código de Barras</th>
                        <th class="text-start">Nombre</th>
                        <th class="text-start">Descripción</th>
                        <th class="text-end">Precio Venta</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productos as $producto)
                    <tr data-nombre="{{ strtolower($producto->nombre) }}"
                        data-codigo="{{ strtolower($producto->codigo_barras) }}"
                        data-categoria="{{ $producto->categoria_id }}"
                        data-marca="{{ $producto->marca_id }}">

                        <td data-label="Imagen" class="text-center ui-card-image">
                            @if($producto->imagen)
                                <img src="{{ asset('uploads/productos/' . $producto->imagen) }}" 
                                    alt="Imagen actual" 
                                    class="img-thumbnail" 
                                    style="width: 80px; height: 80px; object-fit: contain; background-color: #f8f9fa;">
                            @endif
                        </td>

                        <td data-label="Código" class="text-center">
                            {{ $producto->codigo_barras }}
                        </td>

                        <td data-label="Nombre" class="text-start fw-semibold">
                            {{ $producto->nombre }}
                        </td>

                        <td data-label="Descripción" class="text-start">
                            {{ $producto->descripcion }}
                        </td>

                        <td data-label="Precio" class="text-center">
                            {{ number_format($producto->precio_venta_actual, 2) }}
                        </td>

                        <td data-label="Stock" class="text-center">
                            <span class="fw-bold">{{ $producto->stock_total }}</span>

                            @if($producto->stock_total <= 5)
                                <span class="ui-badge ui-badge-danger ms-2">Stock bajo</span>
                            @elseif($producto->stock_total <= 10)
                                <span class="ui-badge ui-badge-warning ms-2">Poco stock</span>
                            @endif
                        </td>

                        <td data-label="Acciones" class="text-center">
                            <div class="d-flex justify-content-center gap-2 action-buttons">

                                <a href="{{ route('productos.edit', $producto->id) }}"
                                    class="btn-soft btn-soft-warning btn-soft-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <form action="{{ route('productos.toggleEstado', $producto->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    @if($producto->activo)
                                        <button type="submit"
                                            class="btn-soft btn-soft-success btn-soft-icon"
                                            title="Activo: clic para desactivar">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                    @else
                                        <button type="submit"
                                            class="btn-soft btn-soft-danger btn-soft-icon"
                                            title="Inactivo: clic para activar">
                                            <i class="fas fa-toggle-off"></i>
                                        </button>
                                    @endif
                                </form>

                                <a href="javascript:void(0);"
                                    class="btn-soft btn-soft-info btn-soft-icon ver-detalles"
                                    data-id="{{ $producto->id }}">
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                            </div>
                        </td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            No se encontraron productos.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <!-- Paginación -->
        <div class="d-flex justify-content-center mt-3">
            {{ $productos->links('pagination::simple-bootstrap-4') }}
        </div>

    </div>
</div>


<!-- Modal para ver detalles del producto -->
<div class="modal fade" id="productoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

           <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa-solid fa-box me-2 text-primary"></i>
                    Detalles del Producto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body product-detail-body">
                <div class="product-detail-hero">
                    <div class="product-detail-image-wrap">
                        <img id="modalImagen" alt="Imagen del producto">
                    </div>
                    <div class="product-detail-heading">
                        <div class="product-detail-kicker" id="modalCategoria"></div>
                        <h3 id="modalNombre"></h3>
                        <p id="modalDescripcion"></p>
                        <div class="product-detail-badges">
                            <span id="modalActivo" class="detail-badge"></span>
                            <span id="modalVisibleCatalogo" class="detail-badge"></span>
                            <span id="modalVencimiento" class="detail-badge"></span>
                        </div>
                    </div>
                    <div class="product-detail-code">
                        <span>Código de barras</span>
                        <strong id="modalCodigo"></strong>
                        <small>ID <b id="modalId"></b></small>
                    </div>
                </div>

                <div class="product-detail-grid">
                    <section class="detail-panel">
                        <div class="detail-panel-title">
                            <i class="fas fa-circle-info"></i>
                            <h6>Información general</h6>
                        </div>
                        <dl class="detail-data-list">
                            <div><dt>Marca</dt><dd id="modalMarca"></dd></div>
                            <div><dt>Ubicación</dt><dd id="modalUbicacion"></dd></div>
                            <div><dt>Creado</dt><dd id="modalCreado"></dd></div>
                            <div><dt>Última actualización</dt><dd id="modalActualizado"></dd></div>
                        </dl>
                    </section>

                    <section class="detail-panel detail-panel-wide">
                        <div class="detail-panel-title">
                            <i class="fas fa-layer-group"></i>
                            <h6>Presentaciones y precios de venta</h6>
                        </div>
                        <div id="modalPresentaciones" class="presentation-detail-grid"></div>
                    </section>
                </div>

                <section class="detail-panel">
                    <div class="detail-panel-title">
                        <i class="fas fa-warehouse"></i>
                        <h6>Resumen de inventario</h6>
                    </div>
                    <div class="inventory-summary-grid">
                        <div><span>Stock disponible</span><strong id="modalStockTotal">0</strong><small>unidades</small></div>
                        <div><span>Lotes con stock</span><strong id="modalCantidadLotes">0</strong><small id="modalLotesRegistrados"></small></div>
                        <div><span>Valor de compra</span><strong id="modalValorInventario">S/ 0.00</strong><small>stock actual</small></div>
                        <div><span>Próximo vencimiento</span><strong id="modalProximoVencimiento">—</strong><small>lote activo</small></div>
                    </div>
                </section>

                <section class="detail-panel">
                    <div class="detail-panel-title">
                        <i class="fas fa-boxes-stacked"></i>
                        <h6>Detalle de lotes</h6>
                    </div>
                    <div class="detail-scroll-hint">
                        <i class="fas fa-arrows-left-right"></i>
                        Desliza horizontalmente para ver todas las columnas
                    </div>
                    <div class="table-responsive detail-lots-scroll">
                        <table class="table align-middle detail-lots-table">
                            <thead>
                                <tr>
                                    <th>Lote</th>
                                    <th>Proveedor</th>
                                    <th>Comprobante</th>
                                    <th>Ingreso</th>
                                    <th>Vencimiento</th>
                                    <th>Stock inicial</th>
                                    <th>Stock actual</th>
                                    <th>Costo</th>
                                    <th>P. unidad</th>
                                    <th>P. paquete</th>
                                    <th>P. caja</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="modalLotesBody"></tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="modal-footer">
                <button class="btn-soft btn-soft-info" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
@if(session('estado_actualizado'))
<script>
    Swal.fire({
        icon: 'success',
        title: '¡Producto {{ session('estado_actualizado') }}!',
        text: 'El estado del producto fue actualizado correctamente.',
        timer: 2000,
        showConfirmButton: false
    });
</script>
@endif

<script>
    
    function confirmarCambioEstado(id, activar) {
        Swal.fire({
            title: activar ? '¿Activar producto?' : '¿Desactivar producto?',
            text: activar
                ? 'Este producto estará disponible nuevamente para ventas.'
                : 'Este producto ya no se mostrará en el sistema.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: activar ? 'Sí, activar' : 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-estado-' + id).submit();
            }
        });
    }
</script>

<script>
    function formatNumber(value) {
        if (!value || value <= 0) return "0";
        return new Intl.NumberFormat('es-PE').format(value);
    }

    $(document).on('click', '.ver-detalles-legacy', function () {

        const productoId = $(this).data('id');

        $.get(`/producto/detalles/${productoId}`, function (r) {

            if (!r.success) return;

            /* =====================
            INFO GENERAL
            ===================== */
            $('#modalId').val(r.id);
            $('#modalCodigo').val(r.codigo_barras ?? '-');
            $('#modalNombre').val(r.nombre);
            $('#modalDescripcion').val(r.descripcion ?? '-');
            $('#modalCategoria').val(r.categoria_nombre ?? '-');
            $('#modalMarca').val(r.marca_nombre ?? '-');
            $('#modalUbicacion').val(r.ubicacion ?? '-');

            $('#modalActivo').val(r.activo ? 'Sí' : 'No');
            $('#modalVisibleCatalogo').val(r.visible_en_catalogo ? 'Sí' : 'No');

            /* =====================
            PRESENTACIONES
            ===================== */
            $('#modalUnidadesPorPaquete').val(
                r.unidades_por_paquete ? formatNumber(r.unidades_por_paquete) : '-'
            );

            $('#modalPaquetesPorCaja').val(
                r.paquetes_por_caja ? formatNumber(r.paquetes_por_caja) : '-'
            );

            $('#modalUnidadesPorCaja').val(
                r.unidades_por_caja ? formatNumber(r.unidades_por_caja) : '-'
            );

            $('#modalManejaVencimiento').val(
                r.maneja_vencimiento ? 'Sí' : 'No'
            );

            /* =====================
            INVENTARIO (RESUMEN)
            ===================== */
            $('#modalStockTotal').val(formatNumber(r.stock_total));
            $('#modalCantidadLotes').val(formatNumber(r.lotes_activos));

            /* =====================
            IMAGEN
            ===================== */
            $('#modalImagen').attr(
                'src',
                r.imagen
                    ? `/uploads/productos/${r.imagen}`
                    : '/img/sin-imagen.png'
            );

            /* =====================
            MOSTRAR MODAL
            ===================== */
            new bootstrap.Modal(document.getElementById('productoModal')).show();
        });
    });
</script>
<script>
    const productDetailNumber = value =>
        new Intl.NumberFormat('es-PE').format(Number(value || 0));

    const productDetailMoney = value => {
        if (value === null || value === undefined || value === '') return '—';
        return new Intl.NumberFormat('es-PE', {
            style: 'currency',
            currency: 'PEN'
        }).format(Number(value));
    };

    const productDetailDate = value => {
        if (!value) return '—';
        const parts = String(value).slice(0, 10).split('-');
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : value;
    };

    const productDetailEscape = value =>
        $('<div>').text(value ?? '—').html();

    $(document).on('click', '.ver-detalles', function () {
        const productoId = $(this).data('id');

        $.get(`/producto/detalles/${productoId}`, function (r) {
            if (!r.success) return;

            const p = r.producto;
            const inventario = r.inventario;

            $('#modalId').text(p.id);
            $('#modalCodigo').text(p.codigo_barras || 'Sin código');
            $('#modalNombre').text(p.nombre);
            $('#modalDescripcion').text(p.descripcion || 'Sin descripción registrada.');
            $('#modalCategoria').text(p.categoria || 'Sin categoría');
            $('#modalMarca').text(p.marca || 'Sin marca');
            $('#modalUbicacion').text(p.ubicacion || 'No registrada');
            $('#modalCreado').text(p.creado_en || '—');
            $('#modalActualizado').text(p.actualizado_en || '—');

            $('#modalActivo')
                .text(p.activo ? 'Producto activo' : 'Producto inactivo')
                .toggleClass('is-success', p.activo)
                .toggleClass('is-danger', !p.activo);
            $('#modalVisibleCatalogo')
                .text(p.visible_en_catalogo ? 'Visible en catálogo' : 'Oculto del catálogo')
                .toggleClass('is-success', p.visible_en_catalogo)
                .toggleClass('is-muted', !p.visible_en_catalogo);
            $('#modalVencimiento')
                .text(p.maneja_vencimiento ? 'Controla vencimiento' : 'Sin control de vencimiento')
                .toggleClass('is-warning', p.maneja_vencimiento)
                .toggleClass('is-muted', !p.maneja_vencimiento);

            $('#modalImagen')
                .attr('src', p.imagen ? `/uploads/productos/${p.imagen}` : '/img/sin-imagen.png')
                .attr('alt', p.nombre);

            const labels = {
                unidad: ['Unidad', '1 unidad'],
                paquete: ['Paquete', `${productDetailNumber(r.presentaciones.paquete.contenido)} unidades`],
                caja: [
                    'Caja',
                    r.presentaciones.caja.paquetes
                        ? `${productDetailNumber(r.presentaciones.caja.paquetes)} paquetes · ${productDetailNumber(r.presentaciones.caja.contenido)} unidades`
                        : `${productDetailNumber(r.presentaciones.caja.contenido)} unidades`
                ]
            };
            const presentaciones = Object.entries(r.presentaciones)
                .filter(([, item]) => item.habilitada)
                .map(([key, item]) => `
                    <article class="presentation-detail-card">
                        <span>${labels[key][0]}</span>
                        <strong>${productDetailMoney(item.precio)}</strong>
                        <small>${labels[key][1]}</small>
                    </article>
                `).join('');
            $('#modalPresentaciones').html(presentaciones);

            $('#modalStockTotal').text(productDetailNumber(inventario.stock_total));
            $('#modalCantidadLotes').text(productDetailNumber(inventario.lotes_con_stock));
            $('#modalLotesRegistrados').text(`${productDetailNumber(inventario.lotes_registrados)} registrados`);
            $('#modalValorInventario').text(productDetailMoney(inventario.valor_compra));
            $('#modalProximoVencimiento').text(productDetailDate(inventario.proximo_vencimiento));

            const lotes = r.lotes.length
                ? r.lotes.map(lote => `
                    <tr>
                        <td><strong>${productDetailEscape(lote.numero || `#${lote.id}`)}</strong></td>
                        <td>${productDetailEscape(lote.proveedor || 'Sin proveedor')}</td>
                        <td>${productDetailEscape(lote.comprobante || '—')}</td>
                        <td>${productDetailDate(lote.fecha_ingreso)}</td>
                        <td>${productDetailDate(lote.fecha_vencimiento)}</td>
                        <td>${productDetailNumber(lote.stock_inicial)}</td>
                        <td><strong>${productDetailNumber(lote.stock_actual)}</strong></td>
                        <td>${productDetailMoney(lote.precio_compra)}</td>
                        <td>${productDetailMoney(lote.precio_unidad)}</td>
                        <td>${productDetailMoney(lote.precio_paquete)}</td>
                        <td>${productDetailMoney(lote.precio_caja)}</td>
                        <td><span class="detail-lot-status ${lote.activo ? 'is-active' : 'is-inactive'}">${lote.activo ? 'Activo' : 'Inactivo'}</span></td>
                    </tr>
                `).join('')
                : '<tr><td colspan="12" class="text-center text-muted py-4">Este producto todavía no tiene lotes registrados.</td></tr>';
            $('#modalLotesBody').html(lotes);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('productoModal')).show();
        }).fail(() => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los detalles del producto.' });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const inputSearch = document.querySelector('input[name="search"]');
        const selectCategoria = document.querySelector('select[name="categoria_id"]');
        const selectMarca = document.querySelector('select[name="marca_id"]');
        const rows = document.querySelectorAll('.ui-table tbody tr');

        function filtrar() {

            const texto = inputSearch.value.toLowerCase();
            const categoria = selectCategoria.value;
            const marca = selectMarca.value;

            rows.forEach(row => {

                const nombre = row.dataset.nombre || '';
                const codigo = row.dataset.codigo || '';
                const rowCategoria = row.dataset.categoria;
                const rowMarca = row.dataset.marca;

                let coincideBusqueda =
                    nombre.includes(texto) ||
                    codigo.includes(texto);

                let coincideCategoria =
                    categoria === 'todos' || categoria === rowCategoria;

                let coincideMarca =
                    marca === 'todos' || marca === rowMarca;

                if (coincideBusqueda && coincideCategoria && coincideMarca) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }

            });
        }

        inputSearch.addEventListener('input', filtrar);
        selectCategoria.addEventListener('change', filtrar);
        selectMarca.addEventListener('change', filtrar);

    });
</script>
@endpush
