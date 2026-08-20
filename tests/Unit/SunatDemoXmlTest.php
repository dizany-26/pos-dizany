<?php

namespace Tests\Unit;

use App\Services\Sunat\SunatDemoXml;
use DOMDocument;
use DOMXPath;
use Tests\TestCase;

class SunatDemoXmlTest extends TestCase
{
    public function test_demo_xml_is_signed_and_clearly_marked_as_non_tax_document(): void
    {
        $contents = app(SunatDemoXml::class)->generate();
        $xml = new DOMDocument();
        $this->assertTrue($xml->loadXML($contents));
        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $this->assertStringContainsString('SIN VALIDEZ TRIBUTARIA', $xpath->evaluate('string(//cbc:Note[@languageLocaleID="2000"])'));
        $this->assertSame(1, $xpath->query('//ds:Signature')->length);
    }
}
