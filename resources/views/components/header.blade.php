<header id="header" class="d-flex align-items-center p-2 text-white">

    <!-- ☰ Sidebar -->
    <button id="btn-toggle-sidebar"
            class="btn btn-primary me-3"
            aria-label="Toggle sidebar">
        &#9776;
    </button>

    @php
        use App\Models\Configuracion;
        $config = Configuracion::first();
    @endphp

    <div class="d-flex align-items-center brand-container">
        <!-- Logo -->
        <img
            src="{{ $config && $config->logo ? asset($config->logo) : asset('images/LOGO.png') }}"
            alt="Logo"
            class="brand-logo me-2"
        >

        <!-- Nombre empresa -->
        <span class="brand-name text-white fw-bold">
            {{ $config->nombre_empresa ?? 'Dizany' }}
        </span>
    </div>


    {{-- HEADER ACTIONS (si la vista define título) --}}
    @hasSection('header-title')
        @include('layouts.header-actions')
    @endif

    <!-- TODO LO DERECHO -->
    <div class="d-flex align-items-center ms-auto">
    <!-- BOTÓN MÁS OPCIONES (SOLO MÓVIL) -->
        <button class="btn-header-more" id="btnHeaderMore" type="button" aria-label="Más opciones">
            <i class="fas fa-ellipsis-v"></i>
        </button>
        <!-- DERECHA: campana + usuario -->
        <div class="d-flex align-items-center ms-3">
            <!-- Campanita -->
            <div class="position-relative me-4">
                <a class="nav-link position-relative text-white"
                href="#"
                id="notificacionesDropdown"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false">

                    <i class="fas fa-bell fa-lg"></i>

                    @if($totalAlertas > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $totalAlertas }}
                        </span>
                    @endif

                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow"
                    aria-labelledby="notificacionesDropdown"
                    style="min-width: 250px;">

                    <li>
                        <a class="dropdown-item d-flex justify-content-between align-items-center"
                        href="{{ route('inventario.resumen') }}">
                            <span>
                                <i class="fas fa-boxes text-danger me-2"></i> Bajo stock
                            </span>
                            <span class="badge bg-danger">
                                {{ $alertaStockBajo }}
                            </span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item d-flex justify-content-between align-items-center"
                        href="{{ route('inventario.resumen') }}">
                            <span>
                                <i class="fas fa-calendar-alt text-warning me-2"></i> Por vencer
                            </span>
                            <span class="badge bg-warning text-dark">
                                {{ $alertaPorVencer }}
                            </span>
                        </a>
                    </li>

                </ul>
            </div>

            <!-- Toggle tema -->
            <button
                type="button"
                id="themeToggle"
                class="theme-toggle-btn me-3"
                aria-label="Cambiar tema"
                title="Cambiar tema">
                <span class="theme-toggle-track">
                    <i class="fas fa-sun theme-icon-sun" aria-hidden="true"></i>
                    <i class="fas fa-moon theme-icon-moon" aria-hidden="true"></i>
                    <span class="theme-toggle-thumb" aria-hidden="true"></span>
                </span>
            </button>

            <!-- Usuario -->
            <div class="user-info-header">
                <i class="fa-solid fa-user-check"></i>
                <span class="user-name">{{ Auth::user()->nombre }}</span>
            </div>
        </div>
    </div>
</header>
<!-- PANEL MOBILE: ACCIONES DEL HEADER -->
<!-- OVERLAY MOBILE -->
<div class="header-mobile-overlay" id="headerMobileOverlay"></div>

<!-- PANEL MOBILE -->
<div class="header-mobile-panel" id="headerMobilePanel"></div>


@push('scripts')
<script>
function cargarNotificaciones() {
    fetch("/notificaciones/inventario")
        .then(res => res.json())
        .then(data => {

            const total = data.stock_bajo + data.por_vencer;

            // Total
            const contadorTotal = document.getElementById("contadorTotal");
            if (contadorTotal) {
                contadorTotal.textContent = total;
                contadorTotal.classList.toggle("d-none", total === 0);
            }

            // Bajo stock
            const contadorStock = document.getElementById("contadorStock");
            if (contadorStock) {
                contadorStock.textContent = data.stock_bajo;
            }

            // Por vencer
            const contadorVencimiento = document.getElementById("contadorVencimiento");
            if (contadorVencimiento) {
                contadorVencimiento.textContent = data.por_vencer;
            }

        })
        .catch(err => console.error("Notificaciones:", err));
}

document.addEventListener("DOMContentLoaded", cargarNotificaciones);
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash;
    if (hash) {
        const tabTrigger = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (tabTrigger) {
            new bootstrap.Tab(tabTrigger).show();
        }
    }
});
</script>

@endpush