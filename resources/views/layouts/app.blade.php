<!DOCTYPE html>
<html lang="es" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google" content="notranslate">
    @php
        $sectionTitle = trim($__env->yieldContent('title'));

        if ($sectionTitle === '' || strcasecmp($sectionTitle, 'Vista - Panel') === 0) {
            $sectionTitle = match (true) {
                request()->is('admin/dashboard') => 'Dashboard',
                request()->is('usuarios*') => 'Usuarios',
                request()->is('clientes*') => 'Clientes',
                request()->is('proveedores*') => 'Proveedores',
                request()->is('productos/create') => 'Nuevo producto',
                request()->is('productos/*/edit') => 'Editar producto',
                request()->is('productos*') => 'Productos',
                request()->is('ventas*') => 'Nueva venta',
                request()->is('movimientos*') => 'Movimientos',
                request()->is('gastos*') => 'Gastos',
                request()->is('reportes*') => 'Reportes',
                request()->is('inventario/resumen*') => 'Resumen de inventario',
                request()->is('inventario/lote*') => 'Ingreso de inventario',
                request()->is('inventario/lotes*') => 'Lotes registrados',
                request()->is('inventario/compras*') => 'Historial de compras',
                request()->is('configuracion/facturacion-electronica*') => 'Facturación electrónica',
                request()->is('configuracion/copias-seguridad*') => 'Copias de seguridad',
                request()->is('configuracion*') => 'Configuración general',
                request()->is('catalogo/configuracion*') => 'Configurar catálogo',
                request()->is('catalogo*') => 'Catálogo',
                default => 'Panel',
            };
        }

        $sectionTitle = trim((string) preg_replace('/^(?:DIZANY\s*\|\s*|Vista\s*-\s*)/i', '', $sectionTitle));
        $browserTitle = 'DIZANY | '.($sectionTitle !== '' ? $sectionTitle : 'Panel');
    @endphp
    <title>{{ $browserTitle }}</title>
    <link id="dizany-favicon" rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <script>
        (() => {
            const expectedTitle = @json($browserTitle);
            document.title = expectedTitle;

            const titleElement = document.querySelector('title');
            if (titleElement) {
                new MutationObserver(() => {
                    if (document.title !== expectedTitle) {
                        document.title = expectedTitle;
                    }
                }).observe(titleElement, { childList: true });
            }

            document.addEventListener('DOMContentLoaded', () => {
                const favicon = document.getElementById('dizany-favicon');
                if (!favicon) return;

                const companyLogo = [...document.images].find((image) => {
                    const identity = `${image.alt || ''} ${image.className || ''} ${image.src || ''}`;
                    return /(?:logo|dizany)/i.test(identity) && !/(?:producto|product)/i.test(identity);
                }) || document.querySelector('header img');

                const applyLogoAsFavicon = () => {
                    const source = companyLogo?.currentSrc || companyLogo?.src;
                    if (source) favicon.href = source;
                };

                if (companyLogo) {
                    companyLogo.complete
                        ? applyLogoAsFavicon()
                        : companyLogo.addEventListener('load', applyLogoAsFavicon, { once: true });
                }
            });
        })();
    </script>

    <script>
        (function () {
            const savedTheme = localStorage.getItem('dizany-theme');
            const theme = savedTheme === 'dark' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/header-actions.css') }}?v={{ filemtime(public_path('css/header-actions.css')) }}">
    
    <link href="{{ asset('css/ui/ui-botones.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-table.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-modal.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-inputs.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-card.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-variables.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-search.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-responsive.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <link rel="stylesheet" href="{{ asset('css/calendar-theme.css') }}?v={{ filemtime(public_path('css/calendar-theme.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/modern-controls.css') }}?v={{ filemtime(public_path('css/modern-controls.css')) }}">

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ✅ Cada vista inyecta su CSS --}}
    @stack('styles')

    <link rel="stylesheet" href="{{ asset('css/theme-global.css') }}?v={{ filemtime(public_path('css/theme-global.css')) }}">
</head>

<body class="theme-shell">

    {{-- HEADER --}}
    @include('components.header')

    {{-- SIDEBAR --}}
    @include('components.sidebar')

    {{-- contenido --}}
    <div id="layout-wrapper">

        <main id="content">
            @yield('content')
        </main>

        {{-- FOOTER --}}
        @include('components.footer')

    </div>

    <!-- Bootstrap (una sola vez) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert + jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JS HEADER ACTIONS (NUEVO SISTEMA) -->
    <script src="{{ asset('js/header-actions.js') }}?v={{ filemtime(public_path('js/header-actions.js')) }}"></script>

    <!-- Script: Toggle Sidebar -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnToggleSidebar = document.getElementById('btn-toggle-sidebar');
            if (!btnToggleSidebar) return;

            btnToggleSidebar.addEventListener('click', () => {
                const mobile = window.innerWidth <= 768;

                if (mobile) document.body.classList.toggle('sidebar-visible');
                else document.body.classList.toggle('sidebar-collapsed');
            });

            document.querySelectorAll('#sidebar a').forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 768) document.body.classList.remove('sidebar-visible');
                });
            });

            if (window.innerWidth > 768) document.body.classList.remove('sidebar-visible');
            window.addEventListener('resize', () => {
                if (window.innerWidth > 768) document.body.classList.remove('sidebar-visible');
            });
        });
    </script>

    <!-- Script: Submenús -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.submenu-toggle').forEach(btn => {
                btn.addEventListener('click', () => {
                    const submenu = btn.nextElementSibling;
                    const icon = btn.querySelector('.toggle-icon');
                    if (!submenu) return;

                    submenu.classList.toggle('show');
                    if (icon) icon.classList.toggle('rotated');
                });
            });

            document.querySelectorAll('.submenu-items.show').forEach(sub => {
                const icon = sub.previousElementSibling?.querySelector('.toggle-icon');
                if (icon) icon.classList.add('rotated');
            });
        });
    </script>

    @if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: @json(session('success')),
            timer: 2000,
            showConfirmButton: false
        });
    </script>
    @endif

    @if($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `{!! implode('<br>', $errors->all()) !!}`,
        });
    </script>
    @endif

    {{-- Dependencias compartidas: se cargan antes de los scripts de cada vista. --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    {{-- ✅ Cada vista inyecta sus scripts --}}
    @stack('scripts')
    <script src="{{ asset('js/modern-controls.js') }}?v={{ filemtime(public_path('js/modern-controls.js')) }}"></script>

</body>
</html>
