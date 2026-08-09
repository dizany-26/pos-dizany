<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar contraseña | {{ $config->nombre_empresa ?? 'DIZANY' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/auth-recovery.css') }}" rel="stylesheet">
</head>
<body class="auth-recovery-page">
    <main class="recovery-card">
        <img src="{{ asset(($config && $config->logo) ? $config->logo : 'images/logo.png') }}"
             alt="Logo de {{ $config->nombre_empresa ?? 'DIZANY' }}"
             class="recovery-logo">

        <div class="recovery-icon"><i class="fa-solid fa-key"></i></div>
        <h1 class="recovery-title">Recupera tu acceso</h1>
        <p class="recovery-subtitle">Ingresa el correo registrado en tu cuenta. Te enviaremos un enlace privado para crear una nueva contraseña.</p>

        @if(session('success'))
            <div class="alert alert-success recovery-alert">
                <i class="fa-solid fa-circle-check me-1"></i>{{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger recovery-alert">
                <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email', [], false) }}" class="recovery-form" autocomplete="off" data-form-type="other">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" name="email" id="email" class="form-control"
                       value="{{ old('email') }}" placeholder="nombre@correo.com" required autofocus
                       autocomplete="off" data-lpignore="true" data-1p-ignore>
            </div>
            <button type="submit" class="btn recovery-btn w-100">
                <i class="fa-solid fa-paper-plane me-1"></i> Enviar enlace seguro
            </button>
        </form>

        <a href="{{ route('login', [], false) }}" class="recovery-back">
            <i class="fa-solid fa-arrow-left"></i> Volver al inicio de sesión
        </a>
    </main>
</body>
</html>
