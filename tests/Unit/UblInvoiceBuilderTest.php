<?php

namespace Tests\Unit;

use App\Services\Sunat\UblInvoiceBuilder;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class UblInvoiceBuilderTest extends TestCase
{
    public function test_it_builds_an_exonerated_boleta_without_igv(): void
    {
        $data = $this->snapshot();
        $data['document']['type'] = '03';
        $data['document']['series'] = 'B001';
        $data['totals'] = ['taxable'=>0,'exonerated'=>100,'unaffected'=>0,'tax_treatment'=>'exonerada','igv_rate'=>0,'igv'=>0,'payable'=>100];
        $data['lines'][0]['line_total_without_igv'] = 100;
        $data['lines'][0]['unit_price_without_igv'] = 100;
        $data['lines'][0]['tax_treatment'] = 'exonerada';
        $data['payment'] = ['balance'=>0,'due_date'=>null];
        $xml = (new UblInvoiceBuilder)->build($data)->saveXML();
        $this->assertStringContainsString('>9997<', $xml);
        $this->assertStringContainsString('>20<', $xml);
        $this->assertStringContainsString('>EXO<', $xml);
    }
    public function test_it_builds_a_gravada_cash_invoice_in_ubl_21(): void
    {
        $xml = (new UblInvoiceBuilder())->build($this->snapshot());
        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('inv', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        $this->assertSame('2.1', $xpath->evaluate('string(/inv:Invoice/cbc:UBLVersionID)'));
        $this->assertSame('F001-15', $xpath->evaluate('string(/inv:Invoice/cbc:ID)'));
        $this->assertSame('01', $xpath->evaluate('string(/inv:Invoice/cbc:InvoiceTypeCode)'));
        $this->assertSame('20123456789', $xpath->evaluate('string(//cac:AccountingSupplierParty//cac:PartyIdentification/cbc:ID)'));
        $this->assertSame('Contado', $xpath->evaluate('string(//cac:PaymentTerms/cbc:PaymentMeansID)'));
        $this->assertSame('23.60', $xpath->evaluate('string(//cac:LegalMonetaryTotal/cbc:PayableAmount)'));
        $this->assertSame('10', $xpath->evaluate('string(//cac:InvoiceLine//cbc:TaxExemptionReasonCode)'));
    }

    public function test_it_builds_credit_sale_with_balance_and_due_date(): void
    {
        $snapshot = $this->snapshot();
        $snapshot['payment']['balance'] = 10;
        $snapshot['payment']['due_date'] = '2026-09-10';

        $xml = (new UblInvoiceBuilder())->build($snapshot);
        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $this->assertSame('Credito', $xpath->evaluate('string((//cac:PaymentTerms/cbc:PaymentMeansID)[1])'));
        $this->assertSame('Cuota001', $xpath->evaluate('string((//cac:PaymentTerms/cbc:PaymentMeansID)[2])'));
        $this->assertSame('10.00', $xpath->evaluate('string((//cac:PaymentTerms/cbc:Amount)[2])'));
        $this->assertSame('2026-09-10', $xpath->evaluate('string(//cac:PaymentTerms/cbc:PaymentDueDate)'));
    }

    private function snapshot(): array
    {
        return [
            'issuer' => [
                'ruc' => '20123456789',
                'legal_name' => 'DIZANY SAC',
                'establishment' => [
                    'code' => '0000', 'ubigeo' => '220101', 'department' => 'SAN MARTIN',
                    'province' => 'MOYOBAMBA', 'district' => 'MOYOBAMBA', 'address' => 'AV. PRINCIPAL 123',
                ],
            ],
            'customer' => ['document_type' => '6', 'document_number' => '20612345678', 'name' => 'CLIENTE SAC'],
            'document' => [
                'type' => '01', 'series' => 'F001', 'number' => 15,
                'issued_at' => '2026-08-10T10:30:00-05:00', 'currency' => 'PEN',
            ],
            'payment' => ['status' => 'pagado', 'balance' => 0, 'due_date' => null],
            'totals' => ['taxable' => 20, 'igv_rate' => 18, 'igv' => 3.60, 'payable' => 23.60],
            'lines' => [[
                'line' => 1, 'product_id' => 10, 'description' => 'PRODUCTO DE PRUEBA',
                'quantity' => 2, 'unit_price_without_igv' => 10, 'line_total_without_igv' => 20,
            ]],
        ];
    }
}
