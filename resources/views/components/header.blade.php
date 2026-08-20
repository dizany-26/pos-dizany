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
        @hasSection('header-buttons')
            <button class="btn-header-more"
                    id="btnHeaderMore"
                    type="button"
                    aria-label="Acciones de la página"
                    aria-controls="headerMobilePanel"
                    aria-haspopup="menu"
                    aria-expanded="false">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        @endif

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
                        <span id="contadorTotal"
                              data-alertas-inventario="{{ $alertaStockBajo + $alertaPorVencer }}"
                              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $totalAlertas }}
                        </span>
                    @else
                        <span id="contadorTotal"
                              data-alertas-inventario="{{ $alertaStockBajo + $alertaPorVencer }}"
                              class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                    @endif
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow"
                    aria-labelledby="notificacionesDropdown"
                    style="min-width: 320px;">
                    @if($notificacionesCaja->isNotEmpty())
                        <li><h6 class="dropdown-header">Alertas de caja</h6></li>
                        @foreach($notificacionesCaja as $notificacion)
                            <li>
                                <a class="dropdown-item d-flex gap-3 align-items-start py-2"
                                   href="{{ route('notificaciones.abrir', $notificacion->id) }}">
                                    <i class="fas {{ $notificacion->data['icono'] ?? 'fa-cash-register' }} text-{{ $notificacion->data['color'] ?? 'primary' }} mt-1"></i>
                                    <span class="text-wrap">
                                        <strong class="d-block">{{ $notificacion->data['titulo'] ?? 'Alerta de caja' }}</strong>
                                        <small class="text-muted">{{ $notificacion->data['mensaje'] ?? '' }}</small>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                        <li><hr class="dropdown-divider"></li>
                    @endif
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
                    <i class="fa-solid fa-circle-user"></i>
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
                    @if(Auth::user()->esAdmin())
                        <li>
                            <a class="dropdown-item" href="{{ route('configuracion.index') }}">
                                <i class="fas fa-cog me-2"></i>Configuración
                            </a>
                        </li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li class="px-3 pt-1 pb-2">
                        <button type="button" class="btn-soft btn-soft-danger user-menu-logout w-100 justify-content-center" id="btnHeaderLogout">
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
            <form action="{{ route('perfil.cambiar-clave') }}" method="POST" id="headerChangePasswordForm" autocomplete="off" data-form-type="other">
                @csrf
                <div class="modal-body">
                    <p class="mb-4">Usuario: <strong>{{ Auth::user()->nombre }}</strong></p>
                    <div class="mb-3">
                        <label for="headerNuevaClave" class="form-label">Nueva Contraseña</label>
                        <input type="text"
                               class="form-control secure-credential-entry"
                               id="headerNuevaClave"
                               minlength="8"
                               required
                               autocomplete="one-time-code"
                               data-lpignore="true"
                               data-1p-ignore
                               data-bwignore>
                        <input type="hidden" name="nueva_clave" id="headerNuevaClavePayload" value="">
                        <div class="password-policy-hint mt-2">
                            <i class="fas fa-shield-halved me-1"></i>
                            Usa 8 caracteres o más, con mayúscula, minúscula, número y símbolo.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-soft btn-soft-primary user-modal-submit justify-content-center px-4">Actualizar</button>
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
    @unless(Auth::user()->esAdmin() || Auth::user()->tienePermiso('inventario.resumen'))
    return;
    @endunless

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

    const headerChangePasswordForm = document.getElementById('headerChangePasswordForm');
    if (headerChangePasswordForm) {
        headerChangePasswordForm.addEventListener('submit', () => {
            document.getElementById('headerNuevaClavePayload').value =
                document.getElementById('headerNuevaClave').value;
        });

        document.getElementById('modalCambiarClaveHeader')?.addEventListener('show.bs.modal', () => {
            document.getElementById('headerNuevaClave').value = '';
            document.getElementById('headerNuevaClavePayload').value = '';
        });
    }

    const revisarAlertasCaja = () => {
        fetch("{{ route('notificaciones.caja') }}", {
            headers: { 'Accept': 'application/json' }
        })
            .then(res => res.ok ? res.json() : Promise.reject(res))
            .then(data => {
                const contador = document.getElementById('contadorTotal');
                if (contador) {
                    const inventario = Number(contador.dataset.alertasInventario || 0);
                    const total = inventario + Number(data.total || 0);
                    contador.textContent = total;
                    contador.classList.toggle('d-none', total === 0);
                }

                const avisadas = JSON.parse(localStorage.getItem('dizany_alertas_caja') || '[]');
                const nueva = data.notificaciones.find(item => !avisadas.includes(item.id));
                if (!nueva || typeof Swal === 'undefined') return;

                avisadas.push(nueva.id);
                localStorage.setItem('dizany_alertas_caja', JSON.stringify(avisadas.slice(-50)));

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: nueva.tipo,
                    title: nueva.titulo,
                    text: nueva.mensaje,
                    showConfirmButton: true,
                    confirmButtonText: 'Ver',
                    timer: 9000,
                    timerProgressBar: true
                }).then(result => {
                    if (result.isConfirmed) window.location.href = nueva.url;
                });
            })
            .catch(err => console.error('Alertas de caja:', err));
    };

    revisarAlertasCaja();
    window.setInterval(revisarAlertasCaja, 15000);

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
