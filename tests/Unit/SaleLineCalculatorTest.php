<?php

namespace Tests\Unit;

use App\Models\Lote;
use App\Models\Producto;
use App\Services\SaleLineCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SaleLineCalculatorTest extends TestCase
{
    private SaleLineCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new SaleLineCalculator();
    }

    public function test_units_use_one_public_price_while_costs_remain_per_lot(): void
    {
        $product = $this->product();
        $lots = collect([
            $this->lot(1, 'L-01', 2, 3, 5, 30, 60),
            $this->lot(2, 'L-02', 3, 4, 7, 42, 84),
        ]);

        $result = $this->calculator->calculate($product, $lots, 4, 'unidad');

        $this->assertSame(4, $result['required_units']);
        $this->assertSame(20.0, $result['subtotal']);
        $this->assertSame(14.0, $result['cost']);
        $this->assertSame(6.0, $result['profit']);
        $this->assertSame(5.0, $result['average_presentation_price']);
        $this->assertSame([2, 2], array_column($result['allocations'], 'units'));
    }

    public function test_a_package_crossing_lots_keeps_one_public_price(): void
    {
        $product = $this->product();
        $lots = collect([
            $this->lot(1, 'L-01', 3, 1, 3, 18, 36),
            $this->lot(2, 'L-02', 3, 2, 4, 24, 48),
        ]);

        $result = $this->calculator->calculate($product, $lots, 1, 'paquete');

        $this->assertSame(6, $result['required_units']);
        $this->assertSame(18.0, $result['subtotal']);
        $this->assertSame(9.0, $result['cost']);
        $this->assertSame(9.0, $result['profit']);
        $this->assertSame(18.0, $result['average_presentation_price']);
        $this->assertSame(3.0, $result['average_unit_price']);
        $this->assertSame([9.0, 9.0], array_column($result['allocations'], 'subtotal'));
    }

    public function test_direct_units_per_box_take_precedence(): void
    {
        $product = $this->product();
        $product->unidades_por_caja = 12;
        $product->paquetes_por_caja = 3;
        $lots = collect([$this->lot(1, 'L-01', 12, 1, 2, 12, 30)]);

        $result = $this->calculator->calculate($product, $lots, 1, 'caja');

        $this->assertSame(12, $result['units_per_presentation']);
        $this->assertSame(30.0, $result['subtotal']);
    }

    public function test_it_rejects_sales_without_enough_stock(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('STOCK|10|Producto prueba|5|6');

        $this->calculator->calculate(
            $this->product(),
            collect([$this->lot(1, 'L-01', 5, 1, 2, 12, 24)]),
            1,
            'paquete'
        );
    }

    public function test_it_rejects_an_unconfigured_presentation(): void
    {
        $product = $this->product();
        $product->unidades_por_paquete = 0;

        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate(
            $product,
            collect([$this->lot(1, 'L-01', 5, 1, 2, 12, 24)]),
            1,
            'paquete'
        );
    }

    private function product(): Producto
    {
        $product = new Producto([
            'nombre' => 'Producto prueba',
            'unidades_por_paquete' => 6,
            'paquetes_por_caja' => 2,
            'unidades_por_caja' => 0,
            'precio_venta' => 5,
            'precio_paquete' => 18,
            'precio_caja' => 30,
        ]);
        $product->id = 10;

        return $product;
    }

    private function lot(
        int $id,
        string $number,
        int $stock,
        float $cost,
        float $unitPrice,
        float $packagePrice,
        float $boxPrice
    ): Lote {
        $lot = new Lote([
            'numero_lote' => $number,
            'stock_actual' => $stock,
            'precio_compra' => $cost,
            'precio_unidad' => $unitPrice,
            'precio_paquete' => $packagePrice,
            'precio_caja' => $boxPrice,
        ]);
        $lot->id = $id;

        return $lot;
    }
}
