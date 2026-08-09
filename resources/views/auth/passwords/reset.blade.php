<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva contraseña | {{ $config->nombre_empresa ?? 'DIZANY' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="{{ asset('css/auth-recovery.css') }}" rel="stylesheet">
</head>
<body class="auth-recovery-page">
    <main class="recovery-card">
        <img src="{{ asset(($config && $config->logo) ? $config->logo : 'images/logo.png') }}"
             alt="Logo de {{ $config->nombre_empresa ?? 'DIZANY' }}"
             class="recovery-logo">

        <div class="recovery-icon"><i class="fa-solid fa-shield-halved"></i></div>
        <h1 class="recovery-title">Crea una nueva contraseña</h1>
        <p class="recovery-subtitle">Elige una clave segura y diferente a las que utilizas en otros servicios.</p>

        @if($errors->any())
            <div class="alert alert-danger recovery-alert">
                <i class="fa-solid fa-circle-exclamation me-1"></i>{{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update', [], false) }}" id="resetPasswordForm" class="recovery-form" autocomplete="off" data-form-type="other">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="password" id="resetPasswordPayload">
            <input type="hidden" name="password_confirmation" id="resetPasswordConfirmationPayload">

            <div class="mb-3">
                <label for="email" class="form-label">Cuenta</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="{{ $email ?? old('email') }}" required readonly autocomplete="off">
            </div>

            <div class="mb-3">
                <label for="resetPasswordVisible" class="form-label">Nueva contraseña</label>
                <div class="recovery-input-group">
                    <input type="text" id="resetPasswordVisible"
                           class="form-control recovery-sensitive-input" minlength="8" required
                           autocomplete="one-time-code" spellcheck="false" autocapitalize="none"
                           data-lpignore="true" data-1p-ignore data-bwignore>
                    <button type="button" class="recovery-password-toggle" data-password-target="resetPasswordVisible" aria-label="Mostrar contraseña">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <div class="password-security-help">
                    <i class="fa-solid fa-shield-halved me-1"></i>
                    Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo. Evita datos personales y contraseñas anteriores.
                </div>
            </div>

            <div class="mb-3">
                <label for="resetPasswordConfirmationVisible" class="form-label">Confirmar contraseña</label>
                <div class="recovery-input-group">
                    <input type="text" id="resetPasswordConfirmationVisible"
                           class="form-control recovery-sensitive-input" minlength="8" required
                           autocomplete="one-time-code" spellcheck="false" autocapitalize="none"
                           data-lpignore="true" data-1p-ignore data-bwignore>
                    <button type="button" class="recovery-password-toggle" data-password-target="resetPasswordConfirmationVisible" aria-label="Mostrar contraseña">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn recovery-btn w-100">
                <i class="fa-solid fa-check me-1"></i> Guardar nueva contraseña
            </button>
        </form>

        <a href="{{ route('login', [], false) }}" class="recovery-back">
            <i class="fa-solid fa-arrow-left"></i> Volver al inicio de sesión
        </a>
    </main>

    <script>
        document.querySelectorAll('[data-password-target]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordTarget);
                const icon = button.querySelector('i');
                const show = !input.classList.contains('is-visible');
                input.classList.toggle('is-visible', show);
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
                button.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
        });

        document.getElementById('resetPasswordForm').addEventListener('submit', () => {
            document.getElementById('resetPasswordPayload').value = document.getElementById('resetPasswordVisible').value;
            document.getElementById('resetPasswordConfirmationPayload').value = document.getElementById('resetPasswordConfirmationVisible').value;
        });
    </script>
</body>
</html>
