<?php

namespace Tests\Unit;

use App\Models\PasswordResetLinkAudit;
use App\Support\SecurePassword;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurePasswordTest extends TestCase
{
    public function test_password_reset_tokens_are_fingerprinted_without_storing_the_original(): void
    {
        $token = 'token-secreto-de-prueba';
        $fingerprint = PasswordResetLinkAudit::fingerprint($token);

        $this->assertSame(64, strlen($fingerprint));
        $this->assertNotSame($token, $fingerprint);
        $this->assertSame(hash('sha256', $token), $fingerprint);
    }

    public function test_it_accepts_a_password_that_meets_the_policy(): void
    {
        $validator = Validator::make(
            ['password' => 'Clave#Fuerte2026', 'password_confirmation' => 'Clave#Fuerte2026'],
            ['password' => ['required', 'confirmed', SecurePassword::rule()]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_it_rejects_weak_passwords(): void
    {
        foreach (['Cor1#', 'solominusculas1#', 'SinNumeros#', 'SinSimbolo2026'] as $password) {
            $validator = Validator::make(
                ['password' => $password, 'password_confirmation' => $password],
                ['password' => ['required', 'confirmed', SecurePassword::rule()]]
            );

            $this->assertTrue($validator->fails(), "La contraseña débil fue aceptada: {$password}");
        }
    }
}
