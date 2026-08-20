<?php

namespace Tests\Unit;

use App\Services\Tax\TaxProfileService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaxProfileServiceTest extends TestCase
{
    public function test_nrus_see_sol_allows_boleta_but_not_factura(): void
    {
        $caps = (new TaxProfileService)->capabilities('nrus', 'see_sol');
        $this->assertContains('issue_boleta', $caps);
        $this->assertContains('manual_sunat_link', $caps);
        $this->assertNotContains('issue_factura', $caps);
        $this->assertNotContains('requires_certificate', $caps);
    }

    public function test_general_see_contribuyente_has_full_direct_capabilities(): void
    {
        $caps = (new TaxProfileService)->capabilities('general', 'see_contribuyente');
        $this->assertContains('issue_factura', $caps);
        $this->assertContains('issue_boleta', $caps);
        $this->assertContains('automatic_submission', $caps);
        $this->assertContains('requires_certificate', $caps);
    }

    public function test_nrus_cannot_claim_direct_contributor_mode(): void
    {
        $this->expectException(ValidationException::class);
        (new TaxProfileService)->validateCombination('nrus', 'see_contribuyente', 'nrus_no_desglosado', 0);
    }

    public function test_nrus_requires_non_itemized_igv_treatment(): void
    {
        $this->expectException(ValidationException::class);
        (new TaxProfileService)->validateCombination('nrus', 'see_sol', 'exonerada', 0);
    }

    public function test_non_itemized_igv_is_exclusive_to_nrus(): void
    {
        $this->expectException(ValidationException::class);
        (new TaxProfileService)->validateCombination('general', 'see_sol', 'nrus_no_desglosado', 0);
    }
}
