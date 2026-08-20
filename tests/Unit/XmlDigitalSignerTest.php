<?php

namespace Tests\Unit;

use App\Services\Sunat\UblInvoiceBuilder;
use App\Services\Sunat\XmlDigitalSigner;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class XmlDigitalSignerTest extends TestCase
{
    public function test_it_creates_a_verifiable_enveloped_rsa_sha256_signature(): void
    {
        [$pkcs12, $certificate] = $this->certificate('clave-segura');
        $xml = (new UblInvoiceBuilder())->build($this->snapshot());
        $signed = (new XmlDigitalSigner())->sign($xml, $pkcs12, 'clave-segura');
        $xpath = new DOMXPath($signed);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $signedInfo = $xpath->query('//ds:Signature/ds:SignedInfo')->item(0);
        $signatureValue = $xpath->evaluate('string(//ds:Signature/ds:SignatureValue)');
        $this->assertNotNull($signedInfo);
        $this->assertSame(
            1,
            openssl_verify($signedInfo->C14N(true, false), base64_decode($signatureValue), $certificate, OPENSSL_ALGO_SHA256)
        );

        $copy = new DOMDocument();
        $copy->preserveWhiteSpace = true;
        $copy->loadXML($signed->saveXML());
        $copyXpath = new DOMXPath($copy);
        $copyXpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $signature = $copyXpath->query('//ds:Signature')->item(0);
        $signature->parentNode->removeChild($signature);
        $digest = base64_encode(hash('sha256', $copy->documentElement->C14N(true, false), true));
        $this->assertSame($xpath->evaluate('string(//ds:DigestValue)'), $digest);
    }

    private function certificate(string $password): array
    {
        $configPath = collect([
            'C:/xampp/apache/conf/openssl.cnf',
            'C:/xampp/php/extras/openssl/openssl.cnf',
            '/etc/ssl/openssl.cnf',
        ])->first(fn (string $path) => is_file($path));
        $options = array_filter([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $configPath,
        ]);
        $privateKey = openssl_pkey_new($options);
        $request = openssl_csr_new(['commonName' => 'DIZANY TEST'], $privateKey, $options);
        $certificate = openssl_csr_sign($request, null, $privateKey, 1, $options);
        openssl_x509_export($certificate, $certificatePem);
        openssl_pkcs12_export($certificate, $pkcs12, $privateKey, $password);

        return [$pkcs12, $certificatePem];
    }

    private function snapshot(): array
    {
        return [
            'issuer' => [
                'ruc' => '20123456789', 'legal_name' => 'DIZANY SAC',
                'establishment' => [
                    'code' => '0000', 'ubigeo' => '220101', 'department' => 'SAN MARTIN',
                    'province' => 'MOYOBAMBA', 'district' => 'MOYOBAMBA', 'address' => 'AV. PRINCIPAL 123',
                ],
            ],
            'customer' => ['document_type' => '6', 'document_number' => '20612345678', 'name' => 'CLIENTE SAC'],
            'document' => ['type' => '01', 'series' => 'F001', 'number' => 15, 'issued_at' => '2026-08-10T10:30:00-05:00', 'currency' => 'PEN'],
            'payment' => ['status' => 'pagado', 'balance' => 0],
            'totals' => ['taxable' => 20, 'igv_rate' => 18, 'igv' => 3.60, 'payable' => 23.60],
            'lines' => [['line' => 1, 'product_id' => 10, 'description' => 'PRODUCTO', 'quantity' => 2, 'unit_price_without_igv' => 10, 'line_total_without_igv' => 20]],
        ];
    }
}
