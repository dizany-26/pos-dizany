<aside id="sidebar">
    <div class="sidebar-card">
        <div class="sidebar-content">

        @auth
            @if(auth()->user()->rol->nombre == 'Administrador')

                <div class="sidebar-section-title">INICIO</div>

                <a href="{{ route('admin.dashboard') }}"
                class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Dashboard</span>
                </a>

                <div class="sidebar-section-title">GESTIÓN</div>

                <a href="{{ route('usuarios.index') }}"
                class="{{ request()->routeIs('usuarios.index') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span class="menu-text">Usuarios</span>
                </a>

                <a href="{{ route('clientes.index') }}"
                class="{{ request()->routeIs('clientes.index') ? 'active' : '' }}">
                    <i class="fas fa-user-friends"></i>
                    <span class="menu-text">Clientes</span>
                </a>

                <a href="{{ route('proveedores.index') }}"
                class="{{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
                    <i class="fas fa-industry"></i>
                    <span class="menu-text">Proveedores</span>
                </a>

                <div class="sidebar-section-title">INVENTARIO</div>

                <div class="submenu">
                    <button class="submenu-toggle {{ request()->is('productos*') ? 'active' : '' }}">
                        <div class="submenu-left">
                            <i class="fas fa-box me-2"></i>
                            <span class="menu-text">Productos</span>
                        </div>
                        <i class="fas fa-caret-down toggle-icon"></i>
                    </button>

                    <div class="submenu-items {{ request()->is('productos*') ? 'show' : '' }}">
                        <a href="{{ route('productos.index') }}"
                        class="{{ request()->routeIs('productos.index') ? 'active' : '' }}">
                            <i class="fas fa-box-open me-1"></i>
                            <span class="menu-text">Ver Productos</span>
                        </a>

                        <a href="{{ route('productos.parametros') }}"
                        class="{{ request()->routeIs('productos.parametros') ? 'active' : '' }}">
                            <i class="fas fa-cogs"></i>
                            <span class="menu-text">Parámetros</span>
                        </a>
                    </div>
                </div>

                <div class="submenu">
                    <button class="submenu-toggle {{ request()->is('inventario*') ? 'active' : '' }}">
                        <div class="submenu-left">
                            <i class="fas fa-warehouse me-2"></i>
                            <span class="menu-text">Inventario</span>
                        </div>
                        <i class="fas fa-caret-down toggle-icon"></i>
                    </button>

                    <div class="submenu-items {{ request()->is('inventario*') ? 'show' : '' }}">
                        <a href="{{ route('inventario.resumen') }}"
                        class="{{ request()->routeIs('inventario.resumen') ? 'active' : '' }}">
                            <i class="fas fa-chart-pie me-1"></i>
                            <span class="menu-text">Resumen</span>
                        </a>

                        <a href="{{ route('inventario.lote') }}"
                        class="{{ request()->routeIs('inventario.lote') ? 'active' : '' }}">
                            <i class="fas fa-truck-loading me-1"></i>
                            <span class="menu-text">Ingreso de Lotes</span>
                        </a>
                    </div>
                </div>

                <div class="sidebar-section-title">OPERACIONES</div>

                <a href="{{ route('ventas.index') }}"
                class="{{ request()->routeIs('ventas.index') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="menu-text">Ventas</span>
                </a>

                <a href="{{ route('movimientos.index') }}"
                class="{{ request()->routeIs('movimientos.*') ? 'active' : '' }}">
                    <i class="fas fa-coins"></i>
                    <span class="menu-text">Movimientos</span>
                </a>

                <a href="{{ route('gastos.index') }}"
                class="{{ request()->routeIs('gastos.index') ? 'active' : '' }}">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="menu-text">Gastos</span>
                </a>

                <div class="sidebar-section-title">ANÁLISIS</div>

                <a href="{{ route('reportes.index') }}"
                class="{{ request()->routeIs('reportes.index') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span class="menu-text">Reportes</span>
                </a>

                <div class="sidebar-section-title">SISTEMA</div>

                <a href="{{ route('configuracion.index') }}"
                class="{{ request()->routeIs('configuracion.index') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    <span class="menu-text">Configuración</span>
                </a>

                <div class="sidebar-section-title sidebar-section-catalogo">CATÁLOGO WEB</div>
                <div class="submenu">
                    <button class="submenu-toggle {{ request()->is('catalogo-admin*') ? 'active' : '' }}">
                        <div class="submenu-left">
                            <i class="fas fa-store me-2"></i>
                            <span class="menu-text">Catálogo</span>
                        </div>
                        <i class="fas fa-caret-down toggle-icon"></i>
                    </button>

                    <div class="submenu-items {{ request()->is('catalogo-admin*') ? 'show' : '' }}">
                        <a href="{{ route('catalogo.admin.index') }}"
                        class="{{ request()->routeIs('catalogo.admin.index') ? 'active' : '' }}">
                            <i class="fas fa-eye me-1"></i>
                            <span class="menu-text">Vista catálogo</span>
                        </a>

                        <a href="{{ route('catalogo.admin.config') }}"
                        class="{{ request()->routeIs('catalogo.admin.config') ? 'active' : '' }}">
                            <i class="fas fa-cog me-1"></i>
                            <span class="menu-text">Configurar catálogo</span>
                        </a>
                    </div>
                </div>

            @elseif(auth()->user()->rol->nombre == 'Empleado')

                <a href="{{ route('empleado.dashboard') }}"
                class="{{ request()->routeIs('empleado.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Inicio</span>
                </a>

                <a href="{{ route('ventas.index') }}"
                class="{{ request()->routeIs('ventas.index') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="menu-text">Ventas</span>
                </a>

                <a href="{{ route('gastos.index') }}"
                class="{{ request()->routeIs('gastos.index') ? 'active' : '' }}">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="menu-text">Gastos</span>
                </a>

            @endif
        @endauth

        </div>

        <!-- Botón Cerrar sesión -->
        <a href="#" id="btn-logout" class="nav-link sidebar-footer">
        <i class="fas fa-sign-out-alt"></i>
        <span class="menu-text logout-text">Cerrar sesión</span>
        </a>
    </div>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>
</aside>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('btn-logout').addEventListener('click', function (e) {
        e.preventDefault();
        Swal.fire({
            title: '¿Cerrar sesión?',
            text: "Tu sesión se cerrará.",
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
</script>

