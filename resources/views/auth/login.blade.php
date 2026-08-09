<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Iniciar Sesión | {{ $config->nombre_empresa ?? 'Dizany' }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ===============================
   BASE
================================ */
body {
    background: linear-gradient(135deg, #0f172a, #1e3a8a);
    height: 100vh;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
}

/* ===============================
   LOGIN CARD
================================ */
.login-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 42px 34px;
    width: 100%;
    max-width: 420px;
    text-align: center;
    box-shadow:
        0 20px 40px rgba(0,0,0,0.18),
        0 4px 10px rgba(0,0,0,0.08);
    animation: fadeIn 0.6s ease-in-out;
}

/* ===============================
   LOGO
================================ */
.login-logo {
    height: 110px;
    width: auto;
    max-width: 240px;
    object-fit: contain;
    margin-bottom: 12px;
    filter: drop-shadow(0 6px 12px rgba(0,0,0,0.18));
}

/* Separador bajo el logo */
.logo-divider {
    width: 48px;
    height: 3px;
    background: #84cc16; /* verde del logo */
    border-radius: 2px;
    margin: 10px auto 18px;
}

/* ===============================
   TEXTOS
================================ */
.login-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 6px;
    color: #1f2937;
}

.login-subtitle {
    font-size: 0.95rem;
    color: #6b7280;
    margin-bottom: 26px;
}

.authorized-access-notice {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: -10px 0 22px;
    padding: 12px 14px;
    border: 1px solid #bfdbfe;
    border-radius: 11px;
    background: #eff6ff;
    color: #1e4f91;
    text-align: left;
}

.authorized-access-notice i {
    margin-top: 2px;
    color: #2563eb;
}

.authorized-access-notice span {
    display: grid;
    gap: 2px;
}

.authorized-access-notice strong {
    font-size: .84rem;
}

.authorized-access-notice small {
    color: #55739d;
    font-size: .72rem;
    line-height: 1.4;
}

.footer-text {
    font-size: 0.85rem;
    color: #9ca3af;
    margin-top: 22px;
}

/* ===============================
   FORM
================================ */
.form-control {
    border-radius: 8px;
    height: 44px;
}

.input-group-text {
    background-color: #f3f4f6;
    border-radius: 8px 0 0 8px;
}

.password-toggle {
    cursor: pointer;
}

/* Campo visualmente protegido sin exponerlo al gestor de contraseñas. */
.login-sensitive-input {
    -webkit-text-security: disc;
}

.login-sensitive-input.is-visible {
    -webkit-text-security: none;
}

/* ===============================
   BUTTON
================================ */
.btn-login {
    background-color: #2563eb;
    color: white;
    font-weight: 600;
    border-radius: 8px;
    padding: 12px;
    transition: all 0.2s ease-in-out;
}

.btn-login:hover {
    background-color: #1d4ed8;
    transform: translateY(-1px);
}

/* ===============================
   LINKS
================================ */
a.text-primary {
    color: #2563eb !important;
}

a.text-primary:hover {
    text-decoration: underline;
}

/* ===============================
   ERROR
================================ */
.error-message {
    color: #dc2626;
    font-size: 0.9rem;
    margin-bottom: 12px;
    display: none;
}

/* ===============================
   ANIMATIONS
================================ */
@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    50% { transform: translateX(6px); }
    75% { transform: translateX(-6px); }
    100% { transform: translateX(0); }
}

.shake {
    animation: shake 0.3s ease-in-out 2;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Diseño compacto y fondo profesional */
body {
    position: relative;
    min-height: 100dvh;
    height: auto;
    padding: 22px;
    overflow-x: hidden;
    background:
        radial-gradient(circle at 14% 18%, rgba(37, 99, 235, .34), transparent 27%),
        radial-gradient(circle at 86% 82%, rgba(14, 165, 233, .22), transparent 30%),
        linear-gradient(135deg, #071426 0%, #0c2140 48%, #102d57 100%);
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    pointer-events: none;
    opacity: .22;
    background-image:
        linear-gradient(rgba(255, 255, 255, .055) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, .055) 1px, transparent 1px);
    background-size: 42px 42px;
    mask-image: linear-gradient(to bottom right, #000, transparent 72%);
}

body::after {
    content: "";
    position: fixed;
    right: -110px;
    top: -130px;
    width: 380px;
    height: 380px;
    border: 70px solid rgba(96, 165, 250, .09);
    border-radius: 50%;
    pointer-events: none;
}

.login-card {
    position: relative;
    z-index: 1;
    max-width: 410px;
    padding: 24px 30px 20px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .75);
    border-radius: 22px;
    box-shadow: 0 30px 80px rgba(0, 9, 28, .45), 0 8px 24px rgba(0, 16, 48, .18);
}

.login-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, #1677e8, #38bdf8, #22c55e);
}

.login-logo {
    height: 72px;
    max-width: 190px;
    margin-bottom: 3px;
    filter: drop-shadow(0 5px 10px rgba(15, 35, 65, .14));
}

.logo-divider {
    width: 42px;
    height: 3px;
    margin: 3px auto 10px;
    background: linear-gradient(90deg, #1677e8, #22c55e);
}

.login-title {
    margin-bottom: 3px;
    font-size: 1.6rem;
    letter-spacing: .01em;
}

.login-subtitle {
    margin-bottom: 15px;
    font-size: .86rem;
}

.authorized-access-notice {
    margin: 0 0 14px;
    padding: 10px 12px;
    border-color: #c9ddf8;
    border-radius: 12px;
    background: linear-gradient(135deg, #f1f7ff, #eaf4ff);
}

.authorized-access-notice strong {
    font-size: .78rem;
}

.authorized-access-notice small {
    font-size: .67rem;
}

.login-card .mb-3 {
    margin-bottom: 12px !important;
}

.form-control,
.input-group-text {
    height: 42px;
}

.form-control {
    border-color: #d7e1ee;
    background: #f8fbff;
}

.input-group-text {
    color: #31506f;
    border-color: #d7e1ee;
    background: #eef4fb;
}

.btn-login {
    min-height: 44px;
    padding: 9px 12px;
    border: 0;
    border-radius: 10px;
    background: linear-gradient(135deg, #1677e8, #2463db);
    box-shadow: 0 9px 20px rgba(37, 99, 235, .22);
}

.btn-login:hover {
    background: linear-gradient(135deg, #0c67d2, #1d4ed8);
}

.login-card form .mt-3 {
    margin-top: 10px !important;
}

.footer-text {
    margin: 12px 0 0;
    font-size: .72rem;
}

@media (max-height: 640px) and (min-width: 481px) {
    body {
        align-items: flex-start;
        padding-block: 12px;
    }

    .login-logo {
        height: 58px;
    }

    .login-card {
        padding-top: 17px;
        padding-bottom: 14px;
    }
}

@media (max-width: 480px) {
    body {
        padding: 14px;
    }

    .login-card {
        padding: 22px 20px 18px;
        border-radius: 19px;
    }

    .login-logo {
        height: 66px;
    }
}

    </style>
</head>
<body>

        <div class="login-card" id="loginCard">
        {{-- Logo dinámico --}}
        @if($config && $config->logo)
            <img src="{{ asset($config->logo) }}" alt="Logo" class="login-logo">
        @else
            <img src="{{ asset('images/logo.png') }}" alt="Logo por defecto" class="login-logo">
        @endif

        <div class="logo-divider"></div>

        {{-- Nombre de empresa dinámico --}}
        <h1 class="login-title">
            {{ $config->nombre_empresa ?? 'Dizany' }}
        </h1>

        <p class="login-subtitle">
            Inicia sesión con tus credenciales
        </p>

        @if(request('restored') === '1')
            <div class="alert alert-success py-2 small" role="status">
                <i class="fas fa-check-circle me-1"></i>
                Base de datos restaurada correctamente. Inicia sesión nuevamente.
            </div>
        @endif

        <div class="authorized-access-notice" role="note">
            <i class="fas fa-shield-halved" aria-hidden="true"></i>
            <span>
                <strong>Acceso exclusivo para personal autorizado</strong>
                <small>Esta área está destinada únicamente a administradores y empleados de {{ $config->nombre_empresa ?? 'Dizany' }}.</small>
            </span>
        </div>


        <div id="error-message" class="error-message"></div>

        <form id="loginForm" method="POST" autocomplete="off" data-form-type="other">
            @csrf
            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" name="usuario" required placeholder="Usuario"
                           autocomplete="off" autocapitalize="none" spellcheck="false" data-lpignore="true" data-1p-ignore>
                </div>
            </div>

            <div class="mb-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="text" class="form-control login-sensitive-input" id="access-key" name="access_key" required placeholder="Contraseña"
                           autocomplete="off" autocapitalize="none" spellcheck="false" data-form-type="other" data-lpignore="true" data-1p-ignore data-bwignore>
                    <span class="input-group-text password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggle-icon"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-login w-100">Iniciar Sesión</button>

            <div class="mt-3 text-end">
                <a href="{{ route('password.request', [], false) }}" class="text-decoration-none text-primary small">¿Olvidaste tu contraseña?</a>
            </div>
        </form>

        <p class="footer-text">&copy; {{ date('Y') }} {{ $config->nombre_empresa ?? 'Dizany' }}. Todos los derechos reservados.</p>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById("access-key");
            const icon = document.getElementById("toggle-icon");
            input.classList.toggle("is-visible");
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        }

        window.addEventListener('pageshow', function () {
            const form = document.getElementById('loginForm');
            if (!form) return;
            form.querySelectorAll('input[name="usuario"], input[name="access_key"]').forEach(input => {
                input.value = '';
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById("loginForm");
            const errorDiv = document.getElementById("error-message");
            const loginCard = document.getElementById("loginCard");

            // No conservar valores al volver con el historial del navegador.
            form.reset();

            form.addEventListener("submit", function (e) {
                e.preventDefault();

                const formData = new FormData(form);
                formData.set('password', formData.get('access_key') || '');
                formData.delete('access_key');

                fetch("{{ route('login.ajax', [], false) }}", {
                    method: "POST",
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect_to;
                    } else {
                        showError(data.message || 'Credenciales incorrectas');
                    }
                })
                .catch(() => showError("Error de servidor o conexión"));
            });

            function showError(msg) {
                errorDiv.textContent = msg;
                errorDiv.style.display = "block";
                loginCard.classList.add("shake");

                setTimeout(() => {
                    errorDiv.style.display = "none";
                    loginCard.classList.remove("shake");
                }, 3000);
            }
        });
    </script>

</body>
</html>
