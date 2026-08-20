<?php

namespace App\Services\Sunat;

use RuntimeException;

class SunatDemoXml
{
    private const PASSWORD = 'dizany-demo-temporal';

    public function __construct(
        private readonly UblInvoiceBuilder $builder,
        private readonly XmlDigitalSigner $signer,
    ) {
    }

    public function generate(): string
    {
        $xml = $this->builder->build($this->snapshot());
        $signed = $this->signer->sign($xml, $this->temporaryCertificate(), self::PASSWORD)->saveXML();

        if ($signed === false) {
            throw new RuntimeException('No se pudo generar el XML de demostración.');
        }

        return $signed;
    }

    private function temporaryCertificate(): string
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
        $key = openssl_pkey_new($options);
        $request = $key ? openssl_csr_new([
            'countryName' => 'PE',
            'organizationName' => 'DIZANY DEMOSTRACIÓN',
            'commonName' => 'CERTIFICADO TEMPORAL NO TRIBUTARIO',
        ], $key, $options) : false;
        $certificate = $request ? openssl_csr_sign($request, null, $key, 1, $options) : false;

        if (! $certificate || ! openssl_pkcs12_export($certificate, $pkcs12, $key, self::PASSWORD)) {
            throw new RuntimeException('OpenSSL no pudo crear el certificado temporal de demostración.');
        }

        return $pkcs12;
    }

    private function snapshot(): array
    {
        return [
            'demo' => true,
            'issuer' => [
                'ruc' => '20123456789',
                'legal_name' => 'EMISOR DE DEMOSTRACIÓN SIN VALIDEZ TRIBUTARIA',
                'establishment' => [
                    'code' => '0000', 'ubigeo' => '220101', 'department' => 'SAN MARTÍN',
                    'province' => 'MOYOBAMBA', 'district' => 'MOYOBAMBA', 'address' => 'DIRECCIÓN DE DEMOSTRACIÓN',
                ],
            ],
            'customer' => ['document_type' => '1', 'document_number' => '12345678', 'name' => 'CLIENTE DE DEMOSTRACIÓN'],
            'document' => [
                'type' => '03', 'series' => 'BDEM', 'number' => 1,
                'issued_at' => now()->toIso8601String(), 'currency' => 'PEN',
            ],
            'payment' => ['status' => 'pagado', 'balance' => 0],
            'totals' => ['taxable' => 10, 'igv_rate' => 18, 'igv' => 1.80, 'payable' => 11.80],
            'lines' => [[
                'line' => 1, 'product_id' => 'DEMO-001', 'description' => 'PRODUCTO DE DEMOSTRACIÓN',
                'quantity' => 1, 'unit_price_without_igv' => 10, 'line_total_without_igv' => 10,
            ]],
        ];
    }
}
