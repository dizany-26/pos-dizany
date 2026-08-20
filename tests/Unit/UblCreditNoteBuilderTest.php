<?php

namespace Tests\Unit;

use App\Services\Sunat\UblCreditNoteBuilder;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class UblCreditNoteBuilderTest extends TestCase
{
    public function test_it_builds_a_credit_note_linked_to_an_invoice(): void
    {
        $xml=(new UblCreditNoteBuilder())->build([
            'issuer'=>['ruc'=>'20123456789','legal_name'=>'DIZANY SAC'],
            'customer'=>['document_type'=>'6','document_number'=>'20612345678','name'=>'CLIENTE SAC'],
            'document'=>['type'=>'07','series'=>'FC01','number'=>'00000001','issued_at'=>'2026-08-11 10:00:00','currency'=>'PEN'],
            'reference'=>['document_type'=>'01','series_number'=>'F001-00000015','reason_code'=>'01','reason'=>'ANULACION DE LA OPERACION'],
            'totals'=>['taxable'=>20,'igv_rate'=>18,'igv'=>3.60,'payable'=>23.60],
            'lines'=>[['line'=>1,'description'=>'PRODUCTO','quantity'=>2,'unit_price_without_igv'=>10,'line_total_without_igv'=>20]],
        ]);
        $xp=new DOMXPath($xml);
        $xp->registerNamespace('cn','urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2');
        $xp->registerNamespace('cbc','urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xp->registerNamespace('cac','urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $this->assertSame('FC01-00000001',$xp->evaluate('string(/cn:CreditNote/cbc:ID)'));
        $this->assertSame('01',$xp->evaluate('string(//cac:DiscrepancyResponse/cbc:ResponseCode)'));
        $this->assertSame('F001-00000015',$xp->evaluate('string(//cac:BillingReference//cbc:ID)'));
        $this->assertSame('23.60',$xp->evaluate('string(//cac:LegalMonetaryTotal/cbc:PayableAmount)'));
    }
}
