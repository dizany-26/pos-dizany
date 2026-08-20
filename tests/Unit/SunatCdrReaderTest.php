<?php

namespace Tests\Unit;

use App\Services\Sunat\SunatCdrReader;
use App\Services\Sunat\SunatZipArchive;
use PHPUnit\Framework\TestCase;

class SunatCdrReaderTest extends TestCase
{
    public function test_it_reads_an_accepted_cdr_with_observations(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<ApplicationResponse xmlns="urn:oasis:names:specification:ubl:schema:xsd:ApplicationResponse-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">
  <cbc:Note>Observación de prueba</cbc:Note>
  <cac:DocumentResponse><cac:Response><cbc:ResponseCode>0</cbc:ResponseCode><cbc:Description>Aceptado</cbc:Description></cac:Response></cac:DocumentResponse>
</ApplicationResponse>
XML;
        $zip = (new SunatZipArchive())->create('R-20123456789-03-B001-00000001.xml', $xml);
        $result = (new SunatCdrReader())->read($zip);

        $this->assertTrue($result['accepted']);
        $this->assertSame('observed', $result['status']);
        $this->assertSame('0', $result['code']);
        $this->assertSame(['Observación de prueba'], $result['notes']);
    }
}
