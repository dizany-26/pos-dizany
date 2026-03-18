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
        <img
            src="{{ $config && $config->logo ? asset($config->logo) : asset('images/LOGO.png') }}"
            alt="Logo"
            class="brand-logo me-2"
        >

        <span class="brand-name text-white fw-bold">
            {{ $config->nombre_empresa ?? 'Dizany' }}
        </span>
    </div>

    @hasSection('header-title')
        @include('layouts.header-actions')
    @endif

    <div class="d-flex align-items-center ms-auto">
        <button class="btn-header-more" id="btnHeaderMore" type="button" aria-label="Más opciones">
            <i class="fas fa-ellipsis-v"></i>
        </button>

        <div class="d-flex align-items-center ms-3">
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
                            <span class="badge bg-danger">{{ $alertaStockBajo }}</span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item d-flex justify-content-between align-items-center"
                           href="{{ route('inventario.resumen') }}">
                            <span>
                                <i class="fas fa-calendar-alt text-warning me-2"></i> Por vencer
                            </span>
                            <span class="badge bg-warning text-dark">{{ $alertaPorVencer }}</span>
                        </a>
                    </li>
                </ul>
            </div>

            <button
                type="button"
                id="themeToggle"
                class="theme-toggle-btn me-3"
                aria-label="Cambiar tema"
                title="Cambiar tema">
                <span class="theme-toggle-track">
                    <i class="fas fa-sun theme-track-icon theme-track-icon-sun" aria-hidden="true"></i>
                    <i class="fas fa-moon theme-track-icon theme-track-icon-moon" aria-hidden="true"></i>
                    <span class="theme-toggle-thumb" aria-hidden="true"></span>
                </span>
            </button>

            <div class="dropdown user-menu-dropdown">
                <button class="user-info-header user-menu-toggle dropdown-toggle"
                        type="button"
                        id="userMenuDropdown"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside"
                        aria-expanded="false">
                    <i class="fa-solid fa-user-check"></i>
                    <span class="user-name">{{ Auth::user()->nombre }}</span>
                    <i class="fa-solid fa-chevron-down user-menu-caret"></i>
                </button>

                <ul class="dropdown-menu dropdown-menu-end user-menu-panel shadow" aria-labelledby="userMenuDropdown">
                    <li class="dropdown-header user-menu-title">
                        <i class="fa-solid fa-user me-2"></i>{{ Auth::user()->nombre }}
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalCambiarClaveHeader">
                            <i class="fas fa-key me-2"></i>Cambiar contraseña
                        </button>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('configuracion.index') }}">
                            <i class="fas fa-cog me-2"></i>Configuración
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li class="px-3 pt-1 pb-2">
                        <button type="button" class="btn btn-danger btn-sm user-menu-logout w-100" id="btnHeaderLogout">
                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>

<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>

<div class="modal fade" id="modalCambiarClaveHeader" tabindex="-1" aria-labelledby="modalCambiarClaveHeaderLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content user-password-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCambiarClaveHeaderLabel">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="{{ route('perfil.cambiar-clave') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="mb-4">Usuario: <strong>{{ Auth::user()->nombre }}</strong></p>
                    <div class="mb-3">
                        <label for="headerNuevaClave" class="form-label">Nueva Contraseña</label>
                        <input type="password"
                               class="form-control"
                               id="headerNuevaClave"
                               name="nueva_clave"
                               minlength="4"
                               required
                               autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary px-4">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="header-mobile-overlay" id="headerMobileOverlay"></div>
<div class="header-mobile-panel" id="headerMobilePanel"></div>

@push('scripts')
<script>
function cargarNotificaciones() {
    fetch("/notificaciones/inventario")
        .then(res => res.json())
        .then(data => {
            const total = data.stock_bajo + data.por_vencer;
            const contadorTotal = document.getElementById("contadorTotal");
            if (contadorTotal) {
                contadorTotal.textContent = total;
                contadorTotal.classList.toggle("d-none", total === 0);
            }
            const contadorStock = document.getElementById("contadorStock");
            if (contadorStock) contadorStock.textContent = data.stock_bajo;
            const contadorVencimiento = document.getElementById("contadorVencimiento");
            if (contadorVencimiento) contadorVencimiento.textContent = data.por_vencer;
        })
        .catch(err => console.error("Notificaciones:", err));
}

document.addEventListener("DOMContentLoaded", function () {
    cargarNotificaciones();

    const hash = window.location.hash;
    if (hash) {
        const tabTrigger = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (tabTrigger) new bootstrap.Tab(tabTrigger).show();
    }

    const btnHeaderLogout = document.getElementById('btnHeaderLogout');
    if (btnHeaderLogout) {
        btnHeaderLogout.addEventListener('click', function () {
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: 'Tu sesión se cerrará.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        });
    }
});
</script>
@endpush
