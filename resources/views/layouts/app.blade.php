<!DOCTYPE html>
<html lang="es" translate="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="google" content="notranslate">
    <title>@yield('title', 'Vista - Panel')</title>

    <!-- Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/header-actions.css') }}">
    <link href="{{ asset('css/ui/ui-botones.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-table.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-modal.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-inputs.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-card.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/ui/ui-variables.css') }}" rel="stylesheet" />

    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ✅ Cada vista inyecta su CSS --}}
    @stack('styles')
</head>

<body class="{{ $tema == 'oscuro' ? 'theme-dark' : 'theme-light' }}">

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
    <script src="{{ asset('js/header-actions.js') }}"></script>

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

    {{-- ✅ Cada vista inyecta sus scripts --}}
    @stack('scripts')

</body>
</html>
