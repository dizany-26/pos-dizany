<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class SecurePassword
{
    public static function rule(): Password
    {
        return Password::min(8)->mixedCase()->letters()->numbers()->symbols();
    }

    public static function messages(string $field = 'password'): array
    {
        return [
            "{$field}.required" => 'Ingresa una contraseña.',
            "{$field}.confirmed" => 'La confirmación de contraseña no coincide.',
            "{$field}.min" => 'La contraseña debe tener al menos 8 caracteres.',
            "{$field}.mixed" => 'La contraseña debe incluir mayúsculas y minúsculas.',
            "{$field}.letters" => 'La contraseña debe incluir letras.',
            "{$field}.numbers" => 'La contraseña debe incluir al menos un número.',
            "{$field}.symbols" => 'La contraseña debe incluir al menos un símbolo.',
        ];
    }
}
