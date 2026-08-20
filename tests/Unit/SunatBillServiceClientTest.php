<?php

namespace Tests\Unit;

use App\Models\SunatSetting;
use App\Services\Sunat\SunatBillServiceClient;
use App\Services\Sunat\SunatZipArchive;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SunatBillServiceClientTest extends TestCase
{
    public function test_beta_uses_moddatos_and_extracts_the_cdr(): void
    {
        $cdr = (new SunatZipArchive())->create('R-20123456789-01-F001-00000001.xml', '<ApplicationResponse/>');
        $soap = '<?xml version="1.0"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><sendBillResponse><applicationResponse>'.base64_encode($cdr).'</applicationResponse></sendBillResponse></soap:Body></soap:Envelope>';
        Http::fake(['e-beta.sunat.gob.pe/*' => Http::response($soap, 200, ['Content-Type' => 'text/xml'])]);
        $setting = new SunatSetting([
            'environment' => 'beta', 'fiscal_ruc' => '20123456789',
            'sol_user' => 'NO_USAR', 'sol_password' => 'NO_USAR',
        ]);

        $result = (new SunatBillServiceClient())->sendBill($setting, 'archivo.zip', 'zip-content');

        $this->assertSame($cdr, $result);
        Http::assertSent(function (Request $request): bool {
            return str_contains($request->body(), '20123456789MODDATOS')
                && str_contains($request->body(), '<wsse:Password>MODDATOS</wsse:Password>')
                && ! str_contains($request->body(), 'NO_USAR');
        });
    }

    public function test_sends_summary_and_reads_ticket(): void
    {
        Http::fake(fn () => Http::response('<?xml version="1.0"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><sendSummaryResponse><ticket>202608110001</ticket></sendSummaryResponse></soap:Body></soap:Envelope>'));
        config(['sunat.environments.beta' => ['bill_service' => 'https://beta.test', 'sol_user' => 'MODDATOS', 'sol_password' => 'MODDATOS']]);
        $setting = new SunatSetting(['environment' => 'beta', 'fiscal_ruc' => '20123456789']);

        $ticket = (new SunatBillServiceClient())->sendSummary($setting, 'resumen.zip', 'zip');

        $this->assertSame('202608110001', $ticket);
        Http::assertSent(fn ($request) => str_contains($request->body(), '<ser:sendSummary>') && str_contains($request->body(), '<fileName>resumen.zip</fileName>'));
    }

    public function test_reads_pending_ticket_status(): void
    {
        Http::fake(fn () => Http::response('<?xml version="1.0"?><soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><getStatusResponse><status><statusCode>98</statusCode></status></getStatusResponse></soap:Body></soap:Envelope>'));
        config(['sunat.environments.beta' => ['bill_service' => 'https://beta.test', 'sol_user' => 'MODDATOS', 'sol_password' => 'MODDATOS']]);
        $setting = new SunatSetting(['environment' => 'beta', 'fiscal_ruc' => '20123456789']);

        $status = (new SunatBillServiceClient())->getStatus($setting, 'ticket-1');

        $this->assertSame(['status_code' => '98', 'cdr' => null], $status);
        Http::assertSent(fn ($request) => str_contains($request->body(), '<ser:getStatus>') && str_contains($request->body(), '<ticket>ticket-1</ticket>'));
    }
}
