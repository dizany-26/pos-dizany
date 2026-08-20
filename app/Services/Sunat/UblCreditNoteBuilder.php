<?php

namespace App\Services\Sunat;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

class UblCreditNoteBuilder
{
    private const NS = [
        'cn' => 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2',
        'cac' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
        'cbc' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
        'ext' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2',
        'ds' => 'http://www.w3.org/2000/09/xmldsig#',
    ];

    public function build(array $data): DOMDocument
    {
        if (($data['document']['type'] ?? null) !== '07' || empty($data['reference']['series_number'])) {
            throw new InvalidArgumentException('La nota de crédito no contiene una referencia válida.');
        }
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $root = $xml->createElementNS(self::NS['cn'], 'CreditNote');
        $xml->appendChild($root);
        foreach (['cac', 'cbc', 'ext', 'ds'] as $prefix) {
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$prefix, self::NS[$prefix]);
        }
        $extensions = $this->el($xml, $root, 'ext:UBLExtensions');
        $extension = $this->el($xml, $extensions, 'ext:UBLExtension');
        $this->el($xml, $extension, 'ext:ExtensionContent');
        $this->txt($xml, $root, 'cbc:UBLVersionID', '2.1');
        $this->txt($xml, $root, 'cbc:CustomizationID', '2.0');
        $this->txt($xml, $root, 'cbc:ID', $data['document']['series'].'-'.$data['document']['number']);
        $this->txt($xml, $root, 'cbc:IssueDate', substr($data['document']['issued_at'], 0, 10));
        $currency = $this->txt($xml, $root, 'cbc:DocumentCurrencyCode', 'PEN');
        $currency->setAttribute('listID', 'ISO 4217 Alpha');
        $discrepancy = $this->el($xml, $root, 'cac:DiscrepancyResponse');
        $this->txt($xml, $discrepancy, 'cbc:ReferenceID', $data['reference']['series_number']);
        $code = $this->txt($xml, $discrepancy, 'cbc:ResponseCode', $data['reference']['reason_code']);
        $code->setAttribute('listAgencyName', 'PE:SUNAT');
        $code->setAttribute('listName', 'Tipo de nota de credito');
        $this->txt($xml, $discrepancy, 'cbc:Description', $data['reference']['reason'], true);
        $billing = $this->el($xml, $root, 'cac:BillingReference');
        $invoiceRef = $this->el($xml, $billing, 'cac:InvoiceDocumentReference');
        $this->txt($xml, $invoiceRef, 'cbc:ID', $data['reference']['series_number']);
        $this->txt($xml, $invoiceRef, 'cbc:DocumentTypeCode', $data['reference']['document_type']);
        $this->signature($xml, $root, $data);
        $this->party($xml, $root, 'cac:AccountingSupplierParty', $data['issuer']['ruc'], $data['issuer']['legal_name'], '6');
        $this->party($xml, $root, 'cac:AccountingCustomerParty', $data['customer']['document_number'], $data['customer']['name'], $data['customer']['document_type']);
        $this->tax($xml, $root, (float) $data['totals']['igv'], (float) $data['totals']['taxable'], (float) $data['totals']['igv_rate']);
        $legal = $this->el($xml, $root, 'cac:LegalMonetaryTotal');
        $this->money($xml, $legal, 'cbc:LineExtensionAmount', (float) $data['totals']['taxable']);
        $this->money($xml, $legal, 'cbc:TaxInclusiveAmount', (float) $data['totals']['payable']);
        $this->money($xml, $legal, 'cbc:PayableAmount', (float) $data['totals']['payable']);
        foreach ($data['lines'] as $line) {
            $node = $this->el($xml, $root, 'cac:CreditNoteLine');
            $this->txt($xml, $node, 'cbc:ID', (string) $line['line']);
            $qty = $this->txt($xml, $node, 'cbc:CreditedQuantity', (string) $line['quantity']);
            $qty->setAttribute('unitCode', 'NIU');
            $this->money($xml, $node, 'cbc:LineExtensionAmount', (float) $line['line_total_without_igv']);
            $lineTax = round((float) $line['line_total_without_igv'] * (float) $data['totals']['igv_rate'] / 100, 2);
            $this->tax($xml, $node, $lineTax, (float) $line['line_total_without_igv'], (float) $data['totals']['igv_rate']);
            $item = $this->el($xml, $node, 'cac:Item');
            $this->txt($xml, $item, 'cbc:Description', $line['description'], true);
            $price = $this->el($xml, $node, 'cac:Price');
            $this->money($xml, $price, 'cbc:PriceAmount', (float) $line['unit_price_without_igv']);
        }
        return $xml;
    }

    private function signature(DOMDocument $x, DOMElement $root, array $data): void
    {
        $s=$this->el($x,$root,'cac:Signature'); $this->txt($x,$s,'cbc:ID','IDSignKG');
        $p=$this->el($x,$s,'cac:SignatoryParty'); $pi=$this->el($x,$p,'cac:PartyIdentification'); $this->txt($x,$pi,'cbc:ID',$data['issuer']['ruc']);
        $pn=$this->el($x,$p,'cac:PartyName'); $this->txt($x,$pn,'cbc:Name',$data['issuer']['legal_name'],true);
        $a=$this->el($x,$s,'cac:DigitalSignatureAttachment'); $e=$this->el($x,$a,'cac:ExternalReference'); $this->txt($x,$e,'cbc:URI','#SignatureKG');
    }
    private function party(DOMDocument $x, DOMElement $root, string $tag, string $idValue, string $name, string $scheme): void
    {
        $a=$this->el($x,$root,$tag); $p=$this->el($x,$a,'cac:Party'); $pi=$this->el($x,$p,'cac:PartyIdentification');
        $id=$this->txt($x,$pi,'cbc:ID',$idValue); $id->setAttribute('schemeID',$scheme); $legal=$this->el($x,$p,'cac:PartyLegalEntity'); $this->txt($x,$legal,'cbc:RegistrationName',$name,true);
    }
    private function tax(DOMDocument $x, DOMElement $p, float $tax, float $base, float $rate): void
    {
        $t=$this->el($x,$p,'cac:TaxTotal'); $this->money($x,$t,'cbc:TaxAmount',$tax); $st=$this->el($x,$t,'cac:TaxSubtotal');
        $this->money($x,$st,'cbc:TaxableAmount',$base); $this->money($x,$st,'cbc:TaxAmount',$tax); $c=$this->el($x,$st,'cac:TaxCategory');
        $this->txt($x,$c,'cbc:ID','S'); $this->txt($x,$c,'cbc:Percent',(string)$rate); $s=$this->el($x,$c,'cac:TaxScheme');
        $this->txt($x,$s,'cbc:ID','1000'); $this->txt($x,$s,'cbc:Name','IGV'); $this->txt($x,$s,'cbc:TaxTypeCode','VAT');
    }
    private function el(DOMDocument $x, DOMElement $p, string $name): DOMElement { [$prefix]=explode(':',$name,2); $n=$x->createElementNS(self::NS[$prefix],$name); $p->appendChild($n); return $n; }
    private function txt(DOMDocument $x, DOMElement $p, string $name, mixed $v, bool $cdata=false): DOMElement { $n=$this->el($x,$p,$name); $n->appendChild($cdata?$x->createCDATASection((string)$v):$x->createTextNode((string)$v)); return $n; }
    private function money(DOMDocument $x, DOMElement $p, string $name, float $v): DOMElement { $n=$this->txt($x,$p,$name,number_format($v,2,'.','')); $n->setAttribute('currencyID','PEN'); return $n; }
}
