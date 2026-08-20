<?php

namespace App\Services\Sunat;

use App\Models\ElectronicDocument;
use App\Models\SunatSetting;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SunatEmissionService
{
    public function __construct(
        private readonly ElectronicDocumentPreparer $preparer,
        private readonly ElectronicDocumentGenerator $generator,
        private readonly SunatZipArchive $archive,
        private readonly SunatBillServiceClient $client,
        private readonly SunatCdrReader $cdrReader,
    ) {
    }

    public function send(Venta $sale): ElectronicDocument
    {
        $setting = SunatSetting::current();
        if (! $setting->enabled || $setting->environment !== 'beta') {
            throw new RuntimeException('La emisión automática permanece desactivada hasta configurar y autorizar SUNAT Beta.');
        }
        if ($sale->tipo_comprobante !== 'factura') {
            throw new RuntimeException('Las boletas se enviarán mediante Resumen Diario y no por sendBill individual.');
        }

        $started = microtime(true);
        $document = $this->preparer->prepare($sale);
        $document = $this->generator->generate($document, $setting);
        $xml = Storage::disk('local')->get($document->xml_path);
        $baseName = sprintf('%s-%s-%s-%08d', $document->snapshot['issuer']['ruc'], $document->document_type, $document->series, $document->number);
        $zip = $this->archive->create($baseName.'.xml', $xml);
        $document->update(['status' => ElectronicDocument::STATUS_SENDING]);

        try {
            $cdrZip = $this->client->sendBill($setting, $baseName.'.zip', $zip);
            $cdr = $this->cdrReader->read($cdrZip);
            $cdrPath = 'sunat/cdr/'.now()->format('Y/m').'/R-'.$baseName.'.zip';
            Storage::disk('local')->put($cdrPath, $cdrZip);

            DB::transaction(function () use ($document, $sale, $setting, $cdr, $cdrPath, $started): void {
                $status = match ($cdr['status']) {
                    'accepted' => ElectronicDocument::STATUS_ACCEPTED,
                    'observed' => ElectronicDocument::STATUS_OBSERVED,
                    default => ElectronicDocument::STATUS_REJECTED,
                };
                $document->update([
                    'status' => $status,
                    'cdr_path' => $cdrPath,
                    'sunat_code' => $cdr['code'],
                    'sunat_description' => trim($cdr['description'].' '.implode(' ', $cdr['notes'])),
                    'sent_at' => now(),
                    'accepted_at' => $cdr['accepted'] ? now() : null,
                ]);
                $sale->update(['estado_sunat' => $status]);
                $document->attempts()->create([
                    'attempt_number' => $document->attempts()->count() + 1,
                    'environment' => $setting->environment,
                    'result' => $status,
                    'sunat_code' => $cdr['code'],
                    'message' => $cdr['description'],
                    'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                ]);
            });
        } catch (\Throwable $exception) {
            $document->update(['status' => ElectronicDocument::STATUS_ERROR, 'sunat_description' => $exception->getMessage()]);
            $sale->update(['estado_sunat' => ElectronicDocument::STATUS_ERROR]);
            $document->attempts()->create([
                'attempt_number' => $document->attempts()->count() + 1,
                'environment' => $setting->environment,
                'result' => ElectronicDocument::STATUS_ERROR,
                'message' => $exception->getMessage(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
            throw $exception;
        }

        return $document->refresh();
    }
}
