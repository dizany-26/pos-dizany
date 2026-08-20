<?php

namespace App\Services\Sunat;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

class UblInvoiceBuilder
{
    private const NS = [
        'inv' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
        'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
        'ext' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2',
        'ds' => 'http://www.w3.org/2000/09/xmldsig#',
    ];

    public function build(array $data): DOMDocument
    {
        $this->validate($data);

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $root = $xml->createElementNS(self::NS['inv'], 'Invoice');
        $xml->appendChild($root);
        foreach (['cac', 'cbc', 'ext', 'ds'] as $prefix) {
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$prefix, self::NS[$prefix]);
        }

        $extensions = $this->element($xml, $root, 'ext:UBLExtensions');
        $extension = $this->element($xml, $extensions, 'ext:UBLExtension');
        $this->element($xml, $extension, 'ext:ExtensionContent');

        $this->text($xml, $root, 'cbc:UBLVersionID', '2.1');
        $this->text($xml, $root, 'cbc:CustomizationID', '2.0');
        $this->text($xml, $root, 'cbc:ID', $data['document']['series'].'-'.$data['document']['number']);
        $this->text($xml, $root, 'cbc:IssueDate', substr($data['document']['issued_at'], 0, 10));
        $this->text($xml, $root, 'cbc:IssueTime', substr($data['document']['issued_at'], 11, 8) ?: '00:00:00');
        $type = $this->text($xml, $root, 'cbc:InvoiceTypeCode', $data['document']['type']);
        $type->setAttribute('listAgencyName', 'PE:SUNAT');
        $type->setAttribute('listName', 'Tipo de Documento');
        $type->setAttribute('listURI', 'urn:pe:gob:sunat:cpe:see:gem:catalogos:catalogo01');
        $type->setAttribute('listID', '0101');
        $note = $this->text($xml, $root, 'cbc:Note', (new AmountInWords())->soles($data['totals']['payable']));
        $note->setAttribute('languageLocaleID', '1000');
        if (! empty($data['demo'])) {
            $demoNote = $this->text($xml, $root, 'cbc:Note', 'DOCUMENTO DE DEMOSTRACIÓN SIN VALIDEZ TRIBUTARIA. NO ENVIAR A SUNAT.');
            $demoNote->setAttribute('languageLocaleID', '2000');
        }
        $currency = $this->text($xml, $root, 'cbc:DocumentCurrencyCode', $data['document']['currency']);
        $currency->setAttribute('listID', 'ISO 4217 Alpha');
        $currency->setAttribute('listName', 'Currency');
        $currency->setAttribute('listAgencyName', 'United Nations Economic Commission for Europe');

        $this->appendSignatureReference($xml, $root, $data);
        $this->appendSupplier($xml, $root, $data['issuer']);
        $this->appendCustomer($xml, $root, $data['customer']);

        $payment = $this->element($xml, $root, 'cac:PaymentTerms');
        $paymentId = $this->text($xml, $payment, 'cbc:ID', 'FormaPago');
        $paymentId->setAttribute('schemeName', 'SUNAT:Identificador de Tipo de Pago');
        $isCredit = (float) ($data['payment']['balance'] ?? 0) > 0;
        $this->text($xml, $payment, 'cbc:PaymentMeansID', $isCredit ? 'Credito' : 'Contado');
        if ($isCredit) {
            $this->money($xml, $payment, 'cbc:Amount', (float) $data['payment']['balance'], $data['document']['currency']);
            $installment = $this->element($xml, $root, 'cac:PaymentTerms');
            $installmentId = $this->text($xml, $installment, 'cbc:ID', 'FormaPago');
            $installmentId->setAttribute('schemeName', 'SUNAT:Identificador de Tipo de Pago');
            $this->text($xml, $installment, 'cbc:PaymentMeansID', 'Cuota001');
            $this->money($xml, $installment, 'cbc:Amount', (float) $data['payment']['balance'], $data['document']['currency']);
            $this->text($xml, $installment, 'cbc:PaymentDueDate', $data['payment']['due_date']);
        }

        $treatment = $data['totals']['tax_treatment'] ?? 'gravada';
        $operationBase = (float) (($data['totals']['taxable'] ?? 0) + ($data['totals']['exonerated'] ?? 0) + ($data['totals']['unaffected'] ?? 0));
        $this->appendTaxTotal($xml, $root, (float) $data['totals']['igv'], $operationBase, (float) $data['totals']['igv_rate'], $treatment);

        $legal = $this->element($xml, $root, 'cac:LegalMonetaryTotal');
        $this->money($xml, $legal, 'cbc:LineExtensionAmount', $operationBase, $data['document']['currency']);
        $this->money($xml, $legal, 'cbc:TaxInclusiveAmount', $data['totals']['payable'], $data['document']['currency']);
        $this->money($xml, $legal, 'cbc:PayableAmount', $data['totals']['payable'], $data['document']['currency']);

        $lineTaxes = $this->distributeTax($data['lines'], (float) $data['totals']['igv'], (float) $data['totals']['igv_rate']);
        foreach ($data['lines'] as $index => $line) {
            $this->appendLine($xml, $root, $line, $lineTaxes[$index], $data['document']['currency'], (float) $data['totals']['igv_rate'], $line['tax_treatment'] ?? $treatment);
        }

        return $xml;
    }

    private function appendSignatureReference(DOMDocument $xml, DOMElement $root, array $data): void
    {
        $signature = $this->element($xml, $root, 'cac:Signature');
        $this->text($xml, $signature, 'cbc:ID', 'IDSignKG');
        $party = $this->element($xml, $signature, 'cac:SignatoryParty');
        $identification = $this->element($xml, $party, 'cac:PartyIdentification');
        $this->text($xml, $identification, 'cbc:ID', $data['issuer']['ruc']);
        $name = $this->element($xml, $party, 'cac:PartyName');
        $this->text($xml, $name, 'cbc:Name', $data['issuer']['legal_name'], true);
        $attachment = $this->element($xml, $signature, 'cac:DigitalSignatureAttachment');
        $reference = $this->element($xml, $attachment, 'cac:ExternalReference');
        $this->text($xml, $reference, 'cbc:URI', '#SignatureKG');
    }

    private function appendSupplier(DOMDocument $xml, DOMElement $root, array $issuer): void
    {
        $supplier = $this->element($xml, $root, 'cac:AccountingSupplierParty');
        $party = $this->element($xml, $supplier, 'cac:Party');
        $idNode = $this->element($xml, $party, 'cac:PartyIdentification');
        $id = $this->text($xml, $idNode, 'cbc:ID', $issuer['ruc']);
        $id->setAttribute('schemeID', '6');
        $id->setAttribute('schemeName', 'Documento de Identidad');
        $id->setAttribute('schemeAgencyName', 'PE:SUNAT');
        $legal = $this->element($xml, $party, 'cac:PartyLegalEntity');
        $this->text($xml, $legal, 'cbc:RegistrationName', $issuer['legal_name'], true);
        $this->appendAddress($xml, $legal, $issuer['establishment']);
    }

    private function appendAddress(DOMDocument $xml, DOMElement $parent, array $location): void
    {
        $address = $this->element($xml, $parent, 'cac:RegistrationAddress');
        $id = $this->text($xml, $address, 'cbc:ID', $location['ubigeo']);
        $id->setAttribute('schemeName', 'Ubigeos');
        $id->setAttribute('schemeAgencyName', 'PE:INEI');
        $type = $this->text($xml, $address, 'cbc:AddressTypeCode', $location['code']);
        $type->setAttribute('listAgencyName', 'PE:SUNAT');
        $type->setAttribute('listName', 'Establecimientos anexos');
        $this->text($xml, $address, 'cbc:CitySubdivisionName', '-');
        $this->text($xml, $address, 'cbc:CityName', $location['province']);
        $this->text($xml, $address, 'cbc:CountrySubentity', $location['department']);
        $this->text($xml, $address, 'cbc:District', $location['district']);
        $line = $this->element($xml, $address, 'cac:AddressLine');
        $this->text($xml, $line, 'cbc:Line', $location['address']);
        $country = $this->element($xml, $address, 'cac:Country');
        $countryId = $this->text($xml, $country, 'cbc:IdentificationCode', 'PE');
        $countryId->setAttribute('listID', 'ISO 3166-1');
    }

    private function appendCustomer(DOMDocument $xml, DOMElement $root, array $customer): void
    {
        $accounting = $this->element($xml, $root, 'cac:AccountingCustomerParty');
        $party = $this->element($xml, $accounting, 'cac:Party');
        $identification = $this->element($xml, $party, 'cac:PartyIdentification');
        $id = $this->text($xml, $identification, 'cbc:ID', $customer['document_number']);
        $id->setAttribute('schemeID', $customer['document_type']);
        $id->setAttribute('schemeName', 'Documento de Identidad');
        $id->setAttribute('schemeAgencyName', 'PE:SUNAT');
        $legal = $this->element($xml, $party, 'cac:PartyLegalEntity');
        $this->text($xml, $legal, 'cbc:RegistrationName', $customer['name'], true);
    }

    private function appendTaxTotal(DOMDocument $xml, DOMElement $parent, float $tax, float $taxable, float $rate, string $treatment = 'gravada'): void
    {
        $total = $this->element($xml, $parent, 'cac:TaxTotal');
        $this->money($xml, $total, 'cbc:TaxAmount', $tax, 'PEN');
        $subtotal = $this->element($xml, $total, 'cac:TaxSubtotal');
        $this->money($xml, $subtotal, 'cbc:TaxableAmount', $taxable, 'PEN');
        $this->money($xml, $subtotal, 'cbc:TaxAmount', $tax, 'PEN');
        $category = $this->element($xml, $subtotal, 'cac:TaxCategory');
        $meta = $this->taxMeta($treatment);
        $this->text($xml, $category, 'cbc:ID', $meta['category']);
        $this->text($xml, $category, 'cbc:Percent', $this->decimal($rate));
        $scheme = $this->element($xml, $category, 'cac:TaxScheme');
        $this->text($xml, $scheme, 'cbc:ID', $meta['scheme']);
        $this->text($xml, $scheme, 'cbc:Name', $meta['name']);
        $this->text($xml, $scheme, 'cbc:TaxTypeCode', $meta['type']);
    }

    private function appendLine(DOMDocument $xml, DOMElement $root, array $data, float $tax, string $currency, float $rate, string $treatment): void
    {
        $line = $this->element($xml, $root, 'cac:InvoiceLine');
        $this->text($xml, $line, 'cbc:ID', (string) $data['line']);
        $quantity = $this->text($xml, $line, 'cbc:InvoicedQuantity', $this->decimal($data['quantity']));
        $quantity->setAttribute('unitCode', 'NIU');
        $this->money($xml, $line, 'cbc:LineExtensionAmount', $data['line_total_without_igv'], $currency);
        $pricing = $this->element($xml, $line, 'cac:PricingReference');
        $alternative = $this->element($xml, $pricing, 'cac:AlternativeConditionPrice');
        $grossUnitPrice = (float) $data['unit_price_without_igv'] * (1 + $rate / 100);
        $this->money($xml, $alternative, 'cbc:PriceAmount', $grossUnitPrice, $currency);
        $this->text($xml, $alternative, 'cbc:PriceTypeCode', '01');

        $this->appendTaxTotal($xml, $line, $tax, (float) $data['line_total_without_igv'], $rate, $treatment);
        $item = $this->element($xml, $line, 'cac:Item');
        $this->text($xml, $item, 'cbc:Description', $data['description'], true);
        $seller = $this->element($xml, $item, 'cac:SellersItemIdentification');
        $this->text($xml, $seller, 'cbc:ID', (string) $data['product_id']);
        $taxCategory = $this->element($xml, $item, 'cac:ClassifiedTaxCategory');
        $meta = $this->taxMeta($treatment);
        $this->text($xml, $taxCategory, 'cbc:ID', $meta['category']);
        $this->text($xml, $taxCategory, 'cbc:Percent', $this->decimal($rate));
        $reason = $this->text($xml, $taxCategory, 'cbc:TaxExemptionReasonCode', $meta['reason']);
        $reason->setAttribute('listAgencyName', 'PE:SUNAT');
        $reason->setAttribute('listName', 'Afectacion del IGV');
        $scheme = $this->element($xml, $taxCategory, 'cac:TaxScheme');
        $this->text($xml, $scheme, 'cbc:ID', $meta['scheme']);
        $this->text($xml, $scheme, 'cbc:Name', $meta['name']);
        $this->text($xml, $scheme, 'cbc:TaxTypeCode', $meta['type']);
        $price = $this->element($xml, $line, 'cac:Price');
        $this->money($xml, $price, 'cbc:PriceAmount', $data['unit_price_without_igv'], $currency);
    }

    private function distributeTax(array $lines, float $totalTax, float $rate): array
    {
        $taxes = [];
        $accumulated = 0.0;
        foreach ($lines as $index => $line) {
            $tax = $index === array_key_last($lines)
                ? round($totalTax - $accumulated, 2)
                : round((float) $line['line_total_without_igv'] * $rate / 100, 2);
            $taxes[] = $tax;
            $accumulated += $tax;
        }
        return $taxes;
    }

    private function validate(array $data): void
    {
        if (! in_array($data['document']['type'] ?? null, ['01', '03'], true)) {
            throw new InvalidArgumentException('Solo se admiten facturas y boletas UBL 2.1.');
        }
        if (($data['document']['currency'] ?? null) !== 'PEN') {
            throw new InvalidArgumentException('La primera versión admite únicamente moneda PEN.');
        }
        $treatment = $data['totals']['tax_treatment'] ?? 'gravada';
        if (! in_array($treatment, ['gravada', 'exonerada', 'inafecta'], true)) {
            throw new InvalidArgumentException('Tratamiento tributario no compatible.');
        }
        if ($treatment === 'gravada' && (($data['totals']['igv_rate'] ?? 0) <= 0 || ($data['totals']['igv'] ?? 0) <= 0)) {
            throw new InvalidArgumentException('Una operación gravada requiere IGV mayor que cero.');
        }
        if (($data['payment']['balance'] ?? 0) > 0 && empty($data['payment']['due_date'])) {
            throw new InvalidArgumentException('Las ventas al crédito requieren una fecha de vencimiento para Cuota001.');
        }
    }

    private function taxMeta(string $treatment): array
    {
        return match ($treatment) {
            'exonerada' => ['category' => 'E', 'scheme' => '9997', 'name' => 'EXO', 'type' => 'VAT', 'reason' => '20'],
            'inafecta' => ['category' => 'O', 'scheme' => '9998', 'name' => 'INA', 'type' => 'FRE', 'reason' => '30'],
            default => ['category' => 'S', 'scheme' => '1000', 'name' => 'IGV', 'type' => 'VAT', 'reason' => '10'],
        };
    }

    private function element(DOMDocument $xml, DOMElement $parent, string $qualifiedName): DOMElement
    {
        [$prefix] = explode(':', $qualifiedName, 2);
        $element = $xml->createElementNS(self::NS[$prefix], $qualifiedName);
        $parent->appendChild($element);
        return $element;
    }

    private function text(DOMDocument $xml, DOMElement $parent, string $name, mixed $value, bool $cdata = false): DOMElement
    {
        $node = $this->element($xml, $parent, $name);
        $node->appendChild($cdata ? $xml->createCDATASection((string) $value) : $xml->createTextNode((string) $value));
        return $node;
    }

    private function money(DOMDocument $xml, DOMElement $parent, string $name, float $value, string $currency): DOMElement
    {
        $node = $this->text($xml, $parent, $name, number_format($value, 2, '.', ''));
        $node->setAttribute('currencyID', $currency);
        return $node;
    }

    private function decimal(float|int $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    }
}
