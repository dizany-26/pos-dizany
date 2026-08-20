<?php

namespace Tests\Unit;

use App\Models\SunatDailySummary;
use App\Models\SunatDailySummaryItem;
use App\Services\Sunat\UblDailySummaryBuilder;
use Carbon\CarbonImmutable;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class UblDailySummaryBuilderTest extends TestCase
{
    public function test_builds_official_daily_summary_structure(): void
    {
        $summary = new SunatDailySummary([
            'reference_date' => CarbonImmutable::parse('2026-08-10'),
            'issue_date' => CarbonImmutable::parse('2026-08-11'),
            'sequence' => 1,
            'identifier' => 'RC-20260811-001',
        ]);
        $summary->setRelation('items', new Collection([
            new SunatDailySummaryItem(['condition_code' => '1', 'snapshot' => $this->snapshot()]),
        ]));

        $xml = (new UblDailySummaryBuilder())->build($summary);
        $xpath = new DOMXPath($xml);
        $this->assertSame('2.0', $xpath->evaluate('string(//*[local-name()="UBLVersionID"])'));
        $this->assertSame('RC-20260811-001', $xpath->evaluate('string(/*[local-name()="SummaryDocuments"]/*[local-name()="ID"])'));
        $this->assertSame('2026-08-10', $xpath->evaluate('string(//*[local-name()="ReferenceDate"])'));
        $this->assertSame('B001-00000012', $xpath->evaluate('string(//*[local-name()="SummaryDocumentsLine"]/*[local-name()="ID"])'));
        $this->assertSame('03', $xpath->evaluate('string(//*[local-name()="DocumentTypeCode"])'));
        $this->assertSame('11.80', $xpath->evaluate('string(//*[local-name()="TotalAmount"])'));
        $this->assertSame('10.00', $xpath->evaluate('string(//*[local-name()="PaidAmount"])'));
        $this->assertSame('1.80', $xpath->evaluate('string(//*[local-name()="TaxAmount"][1])'));
        $this->assertSame('1', $xpath->evaluate('string(//*[local-name()="ConditionCode"])'));
    }

    public function test_builds_a_boleta_cancellation_line(): void
    {
        $summary=new SunatDailySummary(['reference_date'=>CarbonImmutable::parse('2026-08-10'),'issue_date'=>CarbonImmutable::parse('2026-08-11'),'sequence'=>2,'identifier'=>'RC-20260811-002']);
        $summary->setRelation('items',new Collection([new SunatDailySummaryItem(['condition_code'=>'3','snapshot'=>$this->snapshot()])]));
        $xml=(new UblDailySummaryBuilder())->build($summary); $xp=new DOMXPath($xml);
        $this->assertSame('3',$xp->evaluate('string(//*[local-name()="ConditionCode"])'));
        $this->assertSame('B001-00000012',$xp->evaluate('string(//*[local-name()="SummaryDocumentsLine"]/*[local-name()="ID"])'));
    }

    private function snapshot(): array
    {
        return [
            'issuer' => ['ruc' => '20123456789', 'legal_name' => 'DIZANY SAC'],
            'customer' => ['document_type' => '1', 'document_number' => '12345678', 'name' => 'Cliente'],
            'document' => ['type' => '03', 'series' => 'B001', 'number' => 12, 'currency' => 'PEN'],
            'totals' => ['taxable' => 10, 'igv' => 1.8, 'payable' => 11.8],
        ];
    }
}
