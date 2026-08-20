<?php

namespace App\Services\Sunat;

use App\Models\ElectronicCreditNote;
use App\Models\SunatSetting;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ElectronicCreditNoteGenerator
{
    public function __construct(private readonly UblCreditNoteBuilder $builder, private readonly XmlDigitalSigner $signer) {}

    public function generate(ElectronicCreditNote $note, SunatSetting $setting): ElectronicCreditNote
    {
        if (blank($setting->certificate_path) || ! Storage::disk('local')->exists($setting->certificate_path)) {
            throw new RuntimeException('No se encontró el certificado digital configurado.');
        }
        $xml = $this->builder->build($note->snapshot);
        $signed = $this->signer->sign($xml, Storage::disk('local')->get($setting->certificate_path), (string) $setting->certificate_password)->saveXML();
        if ($signed === false) throw new RuntimeException('No se pudo serializar la nota de crédito firmada.');
        $name = sprintf('%s-07-%s-%08d.xml', $note->snapshot['issuer']['ruc'], $note->series, $note->number);
        $path = 'sunat/credit-notes/'.now()->format('Y/m').'/'.$name;
        Storage::disk('local')->put($path, $signed);
        $note->update(['xml_path'=>$path, 'xml_hash'=>hash('sha256',$signed), 'status'=>ElectronicCreditNote::STATUS_READY, 'sunat_code'=>null, 'sunat_description'=>null]);
        return $note->refresh();
    }
}
