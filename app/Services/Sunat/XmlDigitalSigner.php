<?php

namespace App\Services\Sunat;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class XmlDigitalSigner
{
    private const DS = 'http://www.w3.org/2000/09/xmldsig#';

    public function sign(DOMDocument $xml, string $pkcs12, string $password): DOMDocument
    {
        // El XML firmado debe serializarse sin insertar espacios nuevos, porque
        // esos nodos alterarían el digest cuando SUNAT vuelva a leer el archivo.
        $xml->formatOutput = false;

        $credentials = [];
        if (! openssl_pkcs12_read($pkcs12, $credentials, $password)) {
            throw new RuntimeException('No se pudo abrir el certificado digital con la clave proporcionada.');
        }
        if (empty($credentials['pkey']) || empty($credentials['cert'])) {
            throw new RuntimeException('El certificado no contiene la clave privada necesaria para firmar.');
        }

        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('ext', 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2');
        $content = $xpath->query('//ext:UBLExtensions/ext:UBLExtension[1]/ext:ExtensionContent')->item(0);
        if (! $content) {
            throw new RuntimeException('El XML no contiene el espacio reservado para la firma UBL.');
        }

        $digest = base64_encode(hash('sha256', $xml->documentElement->C14N(true, false), true));
        $signature = $xml->createElementNS(self::DS, 'ds:Signature');
        $signature->setAttribute('Id', 'SignatureKG');
        $signedInfo = $this->append($xml, $signature, 'ds:SignedInfo');
        $canonicalization = $this->append($xml, $signedInfo, 'ds:CanonicalizationMethod');
        $canonicalization->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
        $method = $this->append($xml, $signedInfo, 'ds:SignatureMethod');
        $method->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
        $reference = $this->append($xml, $signedInfo, 'ds:Reference');
        $reference->setAttribute('URI', '');
        $transforms = $this->append($xml, $reference, 'ds:Transforms');
        $transform = $this->append($xml, $transforms, 'ds:Transform');
        $transform->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $canonicalTransform = $this->append($xml, $transforms, 'ds:Transform');
        $canonicalTransform->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');
        $digestMethod = $this->append($xml, $reference, 'ds:DigestMethod');
        $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
        $this->append($xml, $reference, 'ds:DigestValue', $digest);
        $content->appendChild($signature);

        $canonicalSignedInfo = $signedInfo->C14N(true, false);
        if (! openssl_sign($canonicalSignedInfo, $signedValue, $credentials['pkey'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('OpenSSL no pudo firmar el XML.');
        }
        $this->append($xml, $signature, 'ds:SignatureValue', base64_encode($signedValue));
        $keyInfo = $this->append($xml, $signature, 'ds:KeyInfo');
        $x509 = $this->append($xml, $keyInfo, 'ds:X509Data');
        $certificate = preg_replace('/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/', '', $credentials['cert']);
        $this->append($xml, $x509, 'ds:X509Certificate', $certificate);

        return $xml;
    }

    private function append(DOMDocument $xml, \DOMElement $parent, string $name, ?string $value = null): \DOMElement
    {
        $node = $xml->createElementNS(self::DS, $name);
        if ($value !== null) {
            $node->appendChild($xml->createTextNode($value));
        }
        $parent->appendChild($node);
        return $node;
    }
}
