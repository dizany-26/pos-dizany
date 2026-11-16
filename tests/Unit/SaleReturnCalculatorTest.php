<?php

namespace Tests\Unit;

use App\Services\SaleReturnCalculator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleReturnCalculatorTest extends TestCase
{
    private SaleReturnCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SaleReturnCalculator();
    }

    public function test_paid_sale_refunds_the_returned_amount(): void
    {
        $this->assertSame(
            ['refund' => 8.0, 'balance_reduction' => 0.0],
            $this->calculator->distribution(24, 24, 0, 0, 8)
        );
    }

    public function test_unpaid_sale_reduces_the_balance_without_refunding_cash(): void
    {
        $this->assertSame(
            ['refund' => 0.0, 'balance_reduction' => 8.0],
            $this->calculator->distribution(24, 0, 0, 0, 8)
        );
    }

    public function test_partial_payment_is_never_refunded_above_the_amount_paid(): void
    {
        $first = $this->calculator->distribution(24, 10, 0, 0, 8);
        $second = $this->calculator->distribution(24, 10, 8, 0, 8);

        $this->assertSame(['refund' => 0.0, 'balance_reduction' => 8.0], $first);
        $this->assertSame(['refund' => 2.0, 'balance_reduction' => 6.0], $second);
    }

    public function test_package_return_restores_the_exact_stock_units(): void
    {
        $this->assertSame(
            ['units' => 6, 'amount' => 35.0],
            $this->calculator->line(3, 18, 105, 1)
        );
    }

    public function test_fractional_stock_allocation_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->calculator->line(3, 10, 30, 1);
    }

    public function test_proportional_return_rounds_only_the_final_amount(): void
    {
        $this->assertSame(
            ['units' => 2, 'amount' => 6.67],
            $this->calculator->line(3, 3, 10, 2)
        );
    }
}
