<?php

namespace App\Services\Sunat;

use App\Models\SunatDailySummary;
use DOMDocument;
use DOMElement;
use RuntimeException;

class UblDailySummaryBuilder
{
    private const ROOT = 'urn:sunat:names:specification:ubl:peru:schema:xsd:SummaryDocuments-1';
    private const EXT = 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';
    private const CBC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    private const CAC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
    private const SAC = 'urn:sunat:names:specification:ubl:peru:schema:xsd:SunatAggregateComponents-1';

    public function build(SunatDailySummary $summary): DOMDocument
    {
        $summary->loadMissing('items');
        if ($summary->items->isEmpty()) {
            throw new RuntimeException('El Resumen Diario no contiene boletas.');
        }

        $issuer = $summary->items->first()->snapshot['issuer'] ?? null;
        if (! is_array($issuer)) {
            throw new RuntimeException('No se encontró la identidad del emisor del Resumen Diario.');
        }

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = false;
        $root = $xml->createElementNS(self::ROOT, 'SummaryDocuments');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ext', self::EXT);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', self::CBC);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', self::CAC);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:sac', self::SAC);
        $xml->appendChild($root);

        $extensions = $this->node($xml, $root, self::EXT, 'ext:UBLExtensions');
        $extension = $this->node($xml, $extensions, self::EXT, 'ext:UBLExtension');
        $this->node($xml, $extension, self::EXT, 'ext:ExtensionContent');
        $this->value($xml, $root, self::CBC, 'cbc:UBLVersionID', '2.0');
        $this->value($xml, $root, self::CBC, 'cbc:CustomizationID', '1.1');
        $this->value($xml, $root, self::CBC, 'cbc:ID', $summary->identifier);
        $this->value($xml, $root, self::CBC, 'cbc:ReferenceDate', $summary->reference_date->format('Y-m-d'));
        $this->value($xml, $root, self::CBC, 'cbc:IssueDate', $summary->issue_date->format('Y-m-d'));

        $signature = $this->node($xml, $root, self::CAC, 'cac:Signature');
        $this->value($xml, $signature, self::CBC, 'cbc:ID', $issuer['ruc']);
        $party = $this->node($xml, $signature, self::CAC, 'cac:SignatoryParty');
        $partyId = $this->node($xml, $party, self::CAC, 'cac:PartyIdentification');
        $this->value($xml, $partyId, self::CBC, 'cbc:ID', $issuer['ruc']);
        $partyName = $this->node($xml, $party, self::CAC, 'cac:PartyName');
        $this->value($xml, $partyName, self::CBC, 'cbc:Name', $issuer['legal_name']);
        $attachment = $this->node($xml, $signature, self::CAC, 'cac:DigitalSignatureAttachment');
        $external = $this->node($xml, $attachment, self::CAC, 'cac:ExternalReference');
        $this->value($xml, $external, self::CBC, 'cbc:URI', '#SignatureKG');

        $supplier = $this->node($xml, $root, self::CAC, 'cac:AccountingSupplierParty');
        $this->value($xml, $supplier, self::CBC, 'cbc:CustomerAssignedAccountID', $issuer['ruc']);
        $this->value($xml, $supplier, self::CBC, 'cbc:AdditionalAccountID', '6');
        $supplierParty = $this->node($xml, $supplier, self::CAC, 'cac:Party');
        $legal = $this->node($xml, $supplierParty, self::CAC, 'cac:PartyLegalEntity');
        $this->value($xml, $legal, self::CBC, 'cbc:RegistrationName', $issuer['legal_name']);

        foreach ($summary->items as $index => $item) {
            $this->appendLine($xml, $root, $index + 1, $item->snapshot, $item->condition_code);
        }

        return $xml;
    }

    private function appendLine(DOMDocument $xml, DOMElement $root, int $lineNumber, array $data, string $condition): void
    {
        $document = $data['document'];
        $customer = $data['customer'];
        $totals = $data['totals'];
        $line = $this->node($xml, $root, self::SAC, 'sac:SummaryDocumentsLine');
        $this->value($xml, $line, self::CBC, 'cbc:LineID', (string) $lineNumber);
        $this->value($xml, $line, self::CBC, 'cbc:DocumentTypeCode', '03');
        $this->value($xml, $line, self::CBC, 'cbc:ID', $document['series'].'-'.str_pad((string) $document['number'], 8, '0', STR_PAD_LEFT));

        if (filled($customer['document_number'] ?? null)) {
            $customerParty = $this->node($xml, $line, self::CAC, 'cac:AccountingCustomerParty');
            $this->value($xml, $customerParty, self::CBC, 'cbc:CustomerAssignedAccountID', $customer['document_number']);
            $this->value($xml, $customerParty, self::CBC, 'cbc:AdditionalAccountID', $customer['document_type'] ?: '0');
        }

        $status = $this->node($xml, $line, self::CAC, 'cac:Status');
        $this->value($xml, $status, self::CBC, 'cbc:ConditionCode', $condition);
        $total = $this->value($xml, $line, self::SAC, 'sac:TotalAmount', $this->money($totals['payable']));
        $total->setAttribute('currencyID', 'PEN');
        $payment = $this->node($xml, $line, self::SAC, 'sac:BillingPayment');
        $paid = $this->value($xml, $payment, self::CBC, 'cbc:PaidAmount', $this->money($totals['taxable']));
        $paid->setAttribute('currencyID', 'PEN');
        $this->value($xml, $payment, self::CBC, 'cbc:InstructionID', '01');

        $taxTotal = $this->node($xml, $line, self::CAC, 'cac:TaxTotal');
        $tax = $this->value($xml, $taxTotal, self::CBC, 'cbc:TaxAmount', $this->money($totals['igv']));
        $tax->setAttribute('currencyID', 'PEN');
        $subtotal = $this->node($xml, $taxTotal, self::CAC, 'cac:TaxSubtotal');
        $taxAmount = $this->value($xml, $subtotal, self::CBC, 'cbc:TaxAmount', $this->money($totals['igv']));
        $taxAmount->setAttribute('currencyID', 'PEN');
        $category = $this->node($xml, $subtotal, self::CAC, 'cac:TaxCategory');
        $scheme = $this->node($xml, $category, self::CAC, 'cac:TaxScheme');
        $this->value($xml, $scheme, self::CBC, 'cbc:ID', '1000');
        $this->value($xml, $scheme, self::CBC, 'cbc:Name', 'IGV');
        $this->value($xml, $scheme, self::CBC, 'cbc:TaxTypeCode', 'VAT');
    }

    private function node(DOMDocument $xml, DOMElement $parent, string $namespace, string $name): DOMElement
    {
        $node = $xml->createElementNS($namespace, $name);
        $parent->appendChild($node);
        return $node;
    }

    private function value(DOMDocument $xml, DOMElement $parent, string $namespace, string $name, string $value): DOMElement
    {
        $node = $this->node($xml, $parent, $namespace, $name);
        $node->appendChild($xml->createTextNode($value));
        return $node;
    }

    private function money(float|int|string $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
