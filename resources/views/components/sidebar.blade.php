<aside id="sidebar">
    <div class="sidebar-content">

        @auth
            @php($user = auth()->user())

            @if($user->hasPermission('dashboard.admin'))
                <div class="sidebar-section-title">INICIO</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            @elseif($user->hasPermission('dashboard.empleado'))
                <div class="sidebar-section-title">INICIO</div>
                <a href="{{ route('empleado.dashboard') }}" class="{{ request()->routeIs('empleado.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i>
                    <span class="menu-text">Inicio</span>
                </a>
            @endif

            @if($user->hasPermission('gestion.usuarios') || $user->hasPermission('gestion.clientes') || $user->hasPermission('gestion.proveedores'))
                <div class="sidebar-section-title">GESTIÓN</div>

                @if($user->hasPermission('gestion.usuarios'))
                    <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.index') ? 'active' : '' }}">
                        <i class="fas fa-users"></i>
                        <span class="menu-text">Usuarios</span>
                    </a>
                @endif

                @if($user->hasPermission('gestion.clientes'))
                    <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.index') ? 'active' : '' }}">
                        <i class="fas fa-user-friends"></i>
                        <span class="menu-text">Clientes</span>
                    </a>
                @endif

                @if($user->hasPermission('gestion.proveedores'))
                    <a href="{{ route('proveedores.index') }}" class="{{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
                        <i class="fas fa-industry"></i>
                        <span class="menu-text">Proveedores</span>
                    </a>
                @endif
            @endif

            @if($user->hasPermission('inventario.productos') || $user->hasPermission('inventario.parametros') || $user->hasPermission('inventario.resumen') || $user->hasPermission('inventario.lotes'))
                <div class="sidebar-section-title">INVENTARIO</div>

                @if($user->hasPermission('inventario.productos') || $user->hasPermission('inventario.parametros'))
                    <div class="submenu">
                        <button class="submenu-toggle {{ request()->is('productos*') ? 'active' : '' }}">
                            <div class="submenu-left">
                                <i class="fas fa-box me-2"></i>
                                <span class="menu-text">Productos</span>
                            </div>
                            <i class="fas fa-caret-down toggle-icon"></i>
                        </button>

                        <div class="submenu-items {{ request()->is('productos*') ? 'show' : '' }}">
                            @if($user->hasPermission('inventario.productos'))
                                <a href="{{ route('productos.index') }}" class="{{ request()->routeIs('productos.index') ? 'active' : '' }}">
                                    <i class="fas fa-box-open me-1"></i>
                                    <span class="menu-text">Ver Productos</span>
                                </a>
                            @endif
                            @if($user->hasPermission('inventario.parametros'))
                                <a href="{{ route('productos.parametros') }}" class="{{ request()->routeIs('productos.parametros') ? 'active' : '' }}">
                                    <i class="fas fa-cogs"></i>
                                    <span class="menu-text">Parámetros</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if($user->hasPermission('inventario.resumen') || $user->hasPermission('inventario.lotes'))
                    <div class="submenu">
                        <button class="submenu-toggle {{ request()->is('inventario*') || request()->is('lotes*') ? 'active' : '' }}">
                            <div class="submenu-left">
                                <i class="fas fa-warehouse me-2"></i>
                                <span class="menu-text">Inventario</span>
                            </div>
                            <i class="fas fa-caret-down toggle-icon"></i>
                        </button>

                        <div class="submenu-items {{ request()->is('inventario*') || request()->is('lotes*') ? 'show' : '' }}">
                            @if($user->hasPermission('inventario.resumen'))
                                <a href="{{ route('inventario.resumen') }}" class="{{ request()->routeIs('inventario.resumen') ? 'active' : '' }}">
                                    <i class="fas fa-chart-pie me-1"></i>
                                    <span class="menu-text">Resumen</span>
                                </a>
                            @endif
                            @if($user->hasPermission('inventario.lotes'))
                                <a href="{{ route('inventario.lote') }}" class="{{ request()->routeIs('inventario.lote') ? 'active' : '' }}">
                                    <i class="fas fa-truck-loading me-1"></i>
                                    <span class="menu-text">Ingreso de Lotes</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            @endif

            @if($user->hasPermission('operaciones.ventas') || $user->hasPermission('operaciones.movimientos') || $user->hasPermission('operaciones.gastos'))
                <div class="sidebar-section-title">OPERACIONES</div>

                @if($user->hasPermission('operaciones.ventas'))
                    <a href="{{ route('ventas.index') }}" class="{{ request()->routeIs('ventas.index') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="menu-text">Ventas</span>
                    </a>
                @endif

                @if($user->hasPermission('operaciones.movimientos'))
                    <a href="{{ route('movimientos.index') }}" class="{{ request()->routeIs('movimientos.*') ? 'active' : '' }}">
                        <i class="fas fa-coins"></i>
                        <span class="menu-text">Movimientos</span>
                    </a>
                @endif

                @if($user->hasPermission('operaciones.gastos'))
                    <a href="{{ route('gastos.index') }}" class="{{ request()->routeIs('gastos.*') ? 'active' : '' }}">
                        <i class="fas fa-money-bill-wave"></i>
                        <span class="menu-text">Gastos</span>
                    </a>
                @endif
            @endif

            @if($user->hasPermission('analisis.reportes'))
                <div class="sidebar-section-title">ANÁLISIS</div>
                <a href="{{ route('reportes.index') }}" class="{{ request()->routeIs('reportes.index') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span class="menu-text">Reportes</span>
                </a>
            @endif

            @if($user->hasPermission('sistema.configuracion'))
                <div class="sidebar-section-title">SISTEMA</div>
                <a href="{{ route('configuracion.index') }}" class="{{ request()->routeIs('configuracion.index') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    <span class="menu-text">Configuración</span>
                </a>
            @endif

            @if($user->hasPermission('catalogo.admin') || $user->hasPermission('catalogo.config'))
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
                        @if($user->hasPermission('catalogo.admin'))
                            <a href="{{ route('catalogo.admin.index') }}" class="{{ request()->routeIs('catalogo.admin.index') ? 'active' : '' }}">
                                <i class="fas fa-eye me-1"></i>
                                <span class="menu-text">Vista catálogo</span>
                            </a>
                        @endif
                        @if($user->hasPermission('catalogo.config'))
                            <a href="{{ route('catalogo.admin.config') }}" class="{{ request()->routeIs('catalogo.admin.config') ? 'active' : '' }}">
                                <i class="fas fa-cog me-1"></i>
                                <span class="menu-text">Configurar catálogo</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @endauth

    </div>
</aside>
