<?php

namespace App\Services\Sunat;

use App\Models\ElectronicDocument;
use App\Models\SunatSetting;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ElectronicDocumentGenerator
{
    public function __construct(
        private readonly UblInvoiceBuilder $builder,
        private readonly XmlDigitalSigner $signer,
    ) {
    }

    public function generate(ElectronicDocument $document, SunatSetting $setting): ElectronicDocument
    {
        if (blank($setting->certificate_path) || ! Storage::disk('local')->exists($setting->certificate_path)) {
            throw new RuntimeException('No se encontró el certificado digital configurado.');
        }

        if (blank($setting->certificate_password)) {
            throw new RuntimeException('Falta la clave del certificado digital.');
        }

        $xml = $this->builder->build($document->snapshot);
        $signedXml = $this->signer->sign(
            $xml,
            Storage::disk('local')->get($setting->certificate_path),
            $setting->certificate_password,
        )->saveXML();

        if ($signedXml === false) {
            throw new RuntimeException('No se pudo convertir el comprobante firmado a XML.');
        }

        $snapshot = $document->snapshot;
        $fileName = sprintf(
            '%s-%s-%s-%08d.xml',
            $snapshot['issuer']['ruc'],
            $document->document_type,
            $document->series,
            $document->number,
        );
        $path = sprintf('sunat/documents/%s/%s', now()->format('Y/m'), $fileName);

        if (! Storage::disk('local')->put($path, $signedXml)) {
            throw new RuntimeException('No se pudo guardar el XML firmado en el almacenamiento privado.');
        }

        $document->forceFill([
            'xml_path' => $path,
            'xml_hash' => hash('sha256', $signedXml),
            'status' => ElectronicDocument::STATUS_READY,
            'sunat_code' => null,
            'sunat_description' => null,
        ])->save();

        return $document->refresh();
    }
}
