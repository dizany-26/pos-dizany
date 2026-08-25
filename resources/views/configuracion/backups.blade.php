@extends('layouts.app')

@section('title', 'Copias de seguridad | DIZANY')

@section('header-back')
<button class="btn-header-back" type="button" onclick="history.back()">
    <i class="fas fa-chevron-left"></i>
</button>
@endsection

@section('header-title', 'Copias de seguridad')

@section('header-buttons')
<form method="POST" action="{{ route('backups.store') }}" id="header-backup-form">
    @csrf
    <button class="btn-movimientos-outline" type="submit" data-backup-create>
        <i class="fas fa-database"></i>
        <span>Crear copia</span>
    </button>
</form>
@endsection

@section('content')
@php
    $lastBackup = $backups->first();
    $lastBackupAt = $lastBackup ? \Carbon\Carbon::createFromTimestamp($lastBackup['created_at']) : null;
    $backupIsOld = ! $lastBackupAt || $lastBackupAt->lt(now()->subDays(7));
@endphp

<section class="backup-page">
    <div class="backup-heading">
        <div>
            <span class="backup-eyebrow">PROTECCIÓN DE DATOS</span>
            <h1>Respalda la información de DIZANY</h1>
            <p>Guarda una copia privada de productos, inventario, ventas, clientes y configuración.</p>
        </div>
    </div>

    <div class="backup-summary-grid">
        <article class="backup-summary-card {{ $backupIsOld ? 'is-warning' : 'is-ok' }}">
            <span class="backup-summary-icon"><i class="fas {{ $backupIsOld ? 'fa-triangle-exclamation' : 'fa-shield-alt' }}"></i></span>
            <div>
                <small>Última copia</small>
                <strong>{{ $lastBackupAt ? $lastBackupAt->format('d/m/Y · H:i') : 'Todavía no existe' }}</strong>
                <span>{{ $backupIsOld ? 'Es recomendable crear una copia ahora.' : 'La base de datos está respaldada recientemente.' }}</span>
            </div>
        </article>

        <article class="backup-summary-card is-neutral">
            <span class="backup-summary-icon"><i class="fas fa-lock"></i></span>
            <div>
                <small>Almacenamiento</small>
                <strong>Privado y local</strong>
                <span>Los archivos no son accesibles desde el catálogo público.</span>
            </div>
        </article>
    </div>

    <div class="backup-panel">
        <div class="backup-panel-header">
            <div>
                <h2><i class="fas fa-clock-rotate-left"></i> Historial de copias</h2>
                <p>{{ $backups->count() }} {{ $backups->count() === 1 ? 'archivo guardado' : 'archivos guardados' }}</p>
            </div>
            <form method="POST" action="{{ route('backups.store') }}" class="backup-mobile-create">
                @csrf
                <button class="backup-primary-button" type="submit" data-backup-create>
                    <i class="fas fa-plus"></i> Crear copia
                </button>
            </form>
        </div>

        @if($backups->isEmpty())
            <div class="backup-empty">
                <span><i class="fas fa-database"></i></span>
                <h3>Aún no tienes copias de seguridad</h3>
                <p>Crea la primera para proteger la información actual del sistema.</p>
            </div>
        @else
            <div class="backup-table-wrap">
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th>Fecha y hora</th>
                            <th>Tamaño</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($backups as $backup)
                            @php
                                $createdAt = \Carbon\Carbon::createFromTimestamp($backup['created_at']);
                                $bytes = $backup['size'];
                                $size = $bytes >= 1048576
                                    ? number_format($bytes / 1048576, 2) . ' MB'
                                    : number_format(max($bytes / 1024, 0.01), 2) . ' KB';
                            @endphp
                            <tr class="{{ $backup['emergency'] ? 'is-emergency' : '' }}">
                                <td data-label="Archivo">
                                    <div class="backup-file-cell {{ $backup['emergency'] ? 'is-emergency' : '' }}">
                                        <span><i class="fas {{ $backup['emergency'] ? 'fa-shield-alt' : 'fa-file-code' }}"></i></span>
                                        <div>
                                            <strong>{{ $backup['name'] }}</strong>
                                            <small>{{ $backup['emergency'] ? 'Estado anterior a una restauración' : 'Base de datos MySQL' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Fecha">{{ $createdAt->format('d/m/Y') }} <small>{{ $createdAt->format('H:i') }}</small></td>
                                <td data-label="Tamaño">{{ $size }}</td>
                                <td data-label="Estado">
                                    <span class="backup-status {{ $backup['emergency'] ? 'is-emergency' : '' }}">
                                        <i class="fas {{ $backup['emergency'] ? 'fa-shield-alt' : 'fa-check' }}"></i>
                                        {{ $backup['emergency'] ? 'Copia de emergencia' : 'Disponible' }}
                                    </span>
                                </td>
                                <td data-label="Acciones">
                                    <div class="backup-actions">
                                        <a href="{{ route('backups.download', $backup['name']) }}" title="Descargar copia">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        @if($backup['restorable'])
                                            <button type="button" class="backup-restore-button"
                                                    data-backup-restore
                                                    data-url="{{ route('backups.restore', $backup['name']) }}"
                                                    data-name="{{ $backup['name'] }}"
                                                    title="Restaurar esta copia">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                        @else
                                            <button type="button" disabled title="Esta copia solo puede descargarse">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                        @endif
                                        <form method="POST" action="{{ route('backups.destroy', $backup['name']) }}" data-backup-delete>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Eliminar copia"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/backups.css') }}?v={{ filemtime(public_path('css/backups.css')) }}">
@endpush

@push('scripts')
<script>
document.querySelectorAll('[data-backup-create]').forEach((button) => {
    button.closest('form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        const buttons = document.querySelectorAll('[data-backup-create]');
        buttons.forEach(item => item.disabled = true);

        let progress = 8;
        let timer;

        const modal = Swal.fire({
            title: 'Creando copia de seguridad',
            html: `
                <div class="backup-progress-modal">
                    <div class="backup-progress-icon"><i class="fas fa-database"></i></div>
                    <p id="backup-progress-message">Preparando la información del sistema…</p>
                    <div class="backup-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="8">
                        <span id="backup-progress-bar" style="width:8%"></span>
                    </div>
                    <strong id="backup-progress-value">8%</strong>
                    <small>No cierres esta ventana hasta que finalice el proceso.</small>
                </div>`,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            keydownListenerCapture: true,
            didOpen: () => {
                timer = window.setInterval(() => {
                    progress = Math.min(92, progress + (progress < 55 ? 7 : progress < 80 ? 4 : 1));
                    updateBackupProgress(progress);
                }, 450);
            }
        });

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'No se pudo crear la copia.');

            window.clearInterval(timer);
            updateBackupProgress(100, '¡Copia completada correctamente!');
            document.querySelector('.backup-progress-icon')?.classList.add('is-complete');
            await new Promise(resolve => window.setTimeout(resolve, 900));
            Swal.close();
            await modal;
            window.location.reload();
        } catch (error) {
            window.clearInterval(timer);
            Swal.fire({
                icon: 'error',
                title: 'No se pudo crear la copia',
                text: error.message || 'Ocurrió un error inesperado.',
                confirmButtonText: 'Entendido'
            });
            buttons.forEach(item => item.disabled = false);
        }
    });
});

function updateBackupProgress(value, message = null) {
    const bar = document.getElementById('backup-progress-bar');
    const label = document.getElementById('backup-progress-value');
    const track = document.querySelector('.backup-progress-track');
    const messageElement = document.getElementById('backup-progress-message');
    if (bar) bar.style.width = `${value}%`;
    if (label) label.textContent = `${value}%`;
    if (track) track.setAttribute('aria-valuenow', String(value));
    if (message && messageElement) messageElement.textContent = message;
}

document.querySelectorAll('[data-backup-delete]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const result = await Swal.fire({
            icon: 'warning',
            title: '¿Eliminar esta copia?',
            text: 'Esta acción no se puede deshacer.',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545'
        });
        if (result.isConfirmed) form.submit();
    });
});

document.querySelectorAll('[data-backup-restore]').forEach((button) => {
    button.addEventListener('click', async () => {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Restaurar base de datos',
            html: `
                <div class="backup-restore-warning">
                    <p>El sistema volverá al estado de <strong>${button.dataset.name}</strong>.</p>
                    <p>Antes se creará automáticamente una copia de emergencia.</p>
                    <label for="restore-password">Contraseña del administrador</label>
                    <input id="restore-password" class="swal2-input backup-sensitive-input" type="text"
                           autocomplete="off" autocapitalize="none" spellcheck="false"
                           data-form-type="other" data-lpignore="true" data-1p-ignore data-bwignore>
                    <label for="restore-confirmation">Escribe <strong>RESTAURAR</strong></label>
                    <input id="restore-confirmation" class="swal2-input" type="text" autocomplete="off">
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'Restaurar ahora',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            focusConfirm: false,
            preConfirm: () => {
                const password = document.getElementById('restore-password').value;
                const confirmation = document.getElementById('restore-confirmation').value;
                if (!password || confirmation !== 'RESTAURAR') {
                    Swal.showValidationMessage('Ingresa tu contraseña y escribe RESTAURAR exactamente.');
                    return false;
                }
                return { password, confirmation };
            }
        });

        if (!result.isConfirmed) return;

        let progress = 6;
        let timer;

        const progressModal = Swal.fire({
            title: 'Restaurando copia de seguridad',
            html: `
                <div class="backup-progress-modal">
                    <div class="backup-progress-icon is-restore"><i class="fas fa-rotate-left"></i></div>
                    <p id="backup-progress-message">Creando una copia de emergencia…</p>
                    <div class="backup-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="6">
                        <span id="backup-progress-bar" style="width:6%"></span>
                    </div>
                    <strong id="backup-progress-value">6%</strong>
                    <small>No cierres ni recargues esta ventana durante la restauración.</small>
                </div>`,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false,
            keydownListenerCapture: true,
            didOpen: () => {
                timer = window.setInterval(() => {
                    progress = Math.min(92, progress + (progress < 45 ? 6 : progress < 75 ? 3 : 1));
                    const message = progress < 45
                        ? 'Creando una copia de emergencia…'
                        : progress < 82
                            ? 'Restaurando la base de datos…'
                            : 'Verificando la información restaurada…';
                    updateBackupProgress(progress, message);
                }, 550);
            }
        });

        try {
            const payload = new FormData();
            payload.set('_token', '{{ csrf_token() }}');
            payload.set('password', result.value.password);
            payload.set('confirmation', result.value.confirmation);

            const response = await fetch(button.dataset.url, {
                method: 'POST',
                body: payload,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'No se pudo restaurar la copia.');

            window.clearInterval(timer);
            updateBackupProgress(100, '¡Restauración completada! Cerrando sesiones…');
            document.querySelector('.backup-progress-icon')?.classList.add('is-complete');
            await new Promise(resolve => window.setTimeout(resolve, 1100));
            Swal.close();
            await progressModal;
            window.location.href = data.redirect_to || '{{ route('login', [], false) }}?restored=1';
        } catch (error) {
            window.clearInterval(timer);
            Swal.fire({
                icon: 'error',
                title: 'No se pudo restaurar la copia',
                text: error.message || 'La base anterior fue protegida con una copia de emergencia.',
                confirmButtonText: 'Entendido',
                allowOutsideClick: false
            });
        }
    });
});
</script>
@endpush
