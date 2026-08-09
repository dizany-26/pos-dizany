<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function __construct(string $token, private readonly string $baseUrl)
    {
        parent::__construct($token);
    }

    public function toMail($notifiable): MailMessage
    {
        $path = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');

        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 30);

        return (new MailMessage)
            ->subject('Recuperación segura de contraseña - DIZANY')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
            ->action('Crear una nueva contraseña', $url)
            ->line("Este enlace es personal, solo puede usarse una vez y expirará en {$minutes} minutos.")
            ->line('Si no solicitaste este cambio, ignora este correo y no compartas el enlace con nadie.')
            ->salutation('DIZANY');
    }
}
