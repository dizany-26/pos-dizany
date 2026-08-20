<?php

namespace Tests\Unit;

use App\Models\Caja;
use PHPUnit\Framework\TestCase;

class CajaPaymentReconciliationTest extends TestCase
{
    public function test_it_normalizes_supported_payment_methods(): void
    {
        $this->assertSame('efectivo', Caja::normalizarMetodo('Efectivo'));
        $this->assertSame('yape', Caja::normalizarMetodo('YAPE'));
        $this->assertSame('plin', Caja::normalizarMetodo('Plin'));
        $this->assertSame('tarjeta', Caja::normalizarMetodo('Tarjeta de débito'));
        $this->assertSame('transferencia', Caja::normalizarMetodo('Transferencia'));
        $this->assertSame('otro', Caja::normalizarMetodo('Billetera nueva'));
    }

    public function test_it_excludes_credit_from_the_cash_reconciliation(): void
    {
        $this->assertSame('pendiente', Caja::normalizarMetodo('fiado'));
        $this->assertSame('pendiente', Caja::normalizarMetodo('crédito'));
    }
}
