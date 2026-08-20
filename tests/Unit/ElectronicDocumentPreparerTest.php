<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\SunatEstablishment;
use App\Models\SunatSetting;
use App\Models\Venta;
use App\Services\Sunat\ElectronicDocumentPreparer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ElectronicDocumentPreparerTest extends TestCase
{
    public function test_it_builds_an_immutable_invoice_snapshot(): void
    {
        $issuer = new SunatSetting([
            'fiscal_ruc' => '20123456789',
            'legal_name' => 'DIZANY SAC',
        ]);
        $establishment = new SunatEstablishment([
            'code' => '0000', 'ubigeo' => '220101', 'department' => 'San Martín',
            'province' => 'Moyobamba', 'district' => 'Moyobamba', 'address' => 'Av. Principal 123',
        ]);
        $customer = new Cliente(['ruc' => '20612345678', 'nombre' => 'CLIENTE SAC']);
        $product = new Producto(['nombre' => 'Producto de prueba']);
        $line = new DetalleVenta([
            'producto_id' => 10,
            'presentacion' => 'unidad',
            'cantidad' => 2,
            'precio_presentacion' => 10,
            'subtotal' => 20,
        ]);
        $line->setRelation('producto', $product);

        $sale = new Venta([
            'tipo_comprobante' => 'factura',
            'serie' => 'F001',
            'correlativo' => 15,
            'op_gravadas' => 20,
            'igv' => 3.60,
            'total' => 23.60,
        ]);
        $sale->fecha = Carbon::parse('2026-08-10 10:30:00');
        $sale->setRelation('cliente', $customer);
        $sale->setRelation('detalleVentas', new Collection([$line]));

        $snapshot = (new ElectronicDocumentPreparer())->snapshot($sale, $issuer, $establishment);

        $this->assertSame('01', $snapshot['document']['type']);
        $this->assertSame('F001', $snapshot['document']['series']);
        $this->assertSame('6', $snapshot['customer']['document_type']);
        $this->assertSame(18.0, $snapshot['totals']['igv_rate']);
        $this->assertSame('220101', $snapshot['issuer']['establishment']['ubigeo']);
        $this->assertSame('Producto de prueba', $snapshot['lines'][0]['description']);
    }

    public function test_invoice_rejects_a_customer_without_ruc(): void
    {
        $issuer = new SunatSetting(['fiscal_ruc' => '20123456789', 'legal_name' => 'DIZANY']);
        $establishment = new SunatEstablishment([
            'code' => '0000', 'ubigeo' => '220101', 'department' => 'San Martín',
            'province' => 'Moyobamba', 'district' => 'Moyobamba', 'address' => 'Av. Principal 123',
        ]);
        $sale = new Venta([
            'tipo_comprobante' => 'factura', 'serie' => 'F001', 'correlativo' => 1,
            'op_gravadas' => 10, 'igv' => 1.8, 'total' => 11.8,
        ]);
        $sale->fecha = Carbon::now();
        $sale->setRelation('cliente', new Cliente(['dni' => '12345678', 'nombre' => 'Persona']));
        $sale->setRelation('detalleVentas', new Collection([new DetalleVenta()]));

        $this->expectException(ValidationException::class);
        (new ElectronicDocumentPreparer())->snapshot($sale, $issuer, $establishment);
    }
}
