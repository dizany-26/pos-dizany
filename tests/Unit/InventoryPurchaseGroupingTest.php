<?php

namespace Tests\Unit;

use App\Http\Controllers\InventarioController;
use App\Models\Lote;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class InventoryPurchaseGroupingTest extends TestCase
{
    public function test_lots_from_the_same_purchase_group_share_the_same_key_without_receipt(): void
    {
        $first = $this->lot(10, 'grupo-compra');
        $second = $this->lot(11, 'grupo-compra');

        $this->assertSame($this->groupKey($first), $this->groupKey($second));
    }

    public function test_legacy_lots_without_receipt_remain_separate(): void
    {
        $this->assertNotSame(
            $this->groupKey($this->lot(10)),
            $this->groupKey($this->lot(11))
        );
    }

    public function test_changing_supplier_starts_a_different_purchase_signature(): void
    {
        $base = [
            'proveedor_id' => 3,
            'tipo_comprobante' => null,
            'codigo_comprobante' => null,
            'fecha_ingreso' => '2026-10-23',
            'condicion_pago' => 'contado',
            'metodo_pago' => 'efectivo',
        ];

        $this->assertNotSame(
            $this->signature($base),
            $this->signature(array_merge($base, ['proveedor_id' => 4]))
        );
    }

    private function lot(int $id, ?string $purchaseGroupKey = null): Lote
    {
        $lot = new Lote([
            'purchase_group_key' => $purchaseGroupKey,
            'proveedor_id' => 3,
            'fecha_ingreso' => '2026-10-23',
        ]);
        $lot->id = $id;

        return $lot;
    }

    private function groupKey(Lote $lot): string
    {
        $method = new ReflectionMethod(InventarioController::class, 'claveCompra');

        return $method->invoke(new InventarioController(), $lot);
    }

    private function signature(array $data): string
    {
        $method = new ReflectionMethod(InventarioController::class, 'firmaCompra');

        return $method->invoke(new InventarioController(), Request::create('/', 'POST', $data)->all());
    }
}
