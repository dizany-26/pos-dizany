<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#071a35">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Catálogo DIZANY">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/pwa/icon-192.png') }}?v={{ filemtime(public_path('images/pwa/icon-192.png')) }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ filemtime(public_path('favicon.ico')) }}">
    <link rel="manifest" href="{{ asset('catalogo.webmanifest') }}?v={{ filemtime(public_path('catalogo.webmanifest')) }}">
    <script>
        window.__dizanyInstallPrompt = null;
        window.addEventListener('beforeinstallprompt', event => {
            event.preventDefault();
            window.__dizanyInstallPrompt = event;
            window.dispatchEvent(new CustomEvent('dizany-install-ready'));
        });
    </script>
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/pwa/apple-touch-icon.png') }}?v={{ filemtime(public_path('images/pwa/apple-touch-icon.png')) }}">
    <link rel="stylesheet" href="{{ asset('css/pwa.css') }}?v={{ filemtime(public_path('css/pwa.css')) }}">
    <script src="{{ asset('js/catalogo-pwa.js') }}?v={{ filemtime(public_path('js/catalogo-pwa.js')) }}" defer></script>
    <script>
        (() => {
            const saved = localStorage.getItem('dizany-catalog-theme');
            document.documentElement.setAttribute('data-catalog-theme', saved === 'dark' ? 'dark' : 'light');
        })();
    </script>
    <title>@yield('title', 'Catálogo DIZANY')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @stack('styles')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
