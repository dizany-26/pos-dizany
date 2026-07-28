<?php

namespace Tests\Unit;

use App\Services\DocumentNumberService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DocumentNumberServiceTest extends TestCase
{
    public function test_it_maps_document_types_to_their_series(): void
    {
        $service = new DocumentNumberService();

        $this->assertSame('B001', $service->seriesFor('boleta'));
        $this->assertSame('F001', $service->seriesFor('factura'));
        $this->assertSame('NV01', $service->seriesFor('nota_venta'));
    }

    public function test_it_rejects_unknown_document_types(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DocumentNumberService())->seriesFor('desconocido');
    }
}
