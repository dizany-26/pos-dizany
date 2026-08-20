<?php

namespace App\Services\Sunat;

use App\Models\ElectronicDocument;
use App\Models\SunatDailySummary;
use App\Models\SunatSetting;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SunatDailySummaryService
{
    public function __construct(
        private readonly UblDailySummaryBuilder $builder,
        private readonly XmlDigitalSigner $signer,
        private readonly SunatZipArchive $zip,
        private readonly SunatBillServiceClient $client,
        private readonly SunatCdrReader $cdrReader,
    ) {
    }

    public function send(SunatDailySummary $summary): SunatDailySummary
    {
        if (in_array($summary->status, [SunatDailySummary::STATUS_ACCEPTED, SunatDailySummary::STATUS_OBSERVED], true)) {
            return $summary;
        }
        if ($summary->ticket) {
            return $this->check($summary);
        }

        $setting = SunatSetting::current();
        $started = microtime(true);
        $summary->update(['status' => SunatDailySummary::STATUS_SENDING]);
        try {
            $xml = $this->signedXml($summary, $setting);
            $baseName = preg_replace('/\D+/', '', (string) $setting->fiscal_ruc).'-'.$summary->identifier;
            $xmlName = $baseName.'.xml';
            $xmlPath = 'sunat/summaries/'.$summary->issue_date->format('Y/m').'/'.$xmlName;
            Storage::disk('local')->put($xmlPath, $xml);
            $ticket = $this->client->sendSummary($setting, $baseName.'.zip', $this->zip->create($xmlName, $xml));
            $summary->update([
                'status' => SunatDailySummary::STATUS_TICKET,
                'ticket' => $ticket,
                'xml_path' => $xmlPath,
                'xml_hash' => hash('sha256', $xml),
                'sent_at' => now(),
                'sunat_code' => '98',
                'sunat_description' => 'Ticket recibido; esperando procesamiento de SUNAT.',
            ]);
            $this->attempt($summary, 'sendSummary', 'ticket', '98', 'Ticket '.$ticket, $started);
            return $summary->refresh();
        } catch (Throwable $exception) {
            $summary->update(['status' => SunatDailySummary::STATUS_ERROR, 'sunat_description' => $exception->getMessage()]);
            $this->attempt($summary, 'sendSummary', 'error', null, $exception->getMessage(), $started);
            throw $exception;
        }
    }

    public function check(SunatDailySummary $summary): SunatDailySummary
    {
        if (! $summary->ticket) {
            throw new RuntimeException('El Resumen Diario todavía no tiene ticket SUNAT.');
        }
        $setting = SunatSetting::current();
        $started = microtime(true);
        try {
            $response = $this->client->getStatus($setting, $summary->ticket);
            if ($response['status_code'] === '98') {
                $summary->update(['status' => SunatDailySummary::STATUS_TICKET, 'sunat_code' => '98', 'sunat_description' => 'SUNAT aún está procesando el Resumen Diario.']);
                $this->attempt($summary, 'getStatus', 'pending', '98', $summary->sunat_description, $started);
                return $summary->refresh();
            }
            if ($response['status_code'] === '99') {
                $summary->update([
                    'status' => SunatDailySummary::STATUS_REJECTED,
                    'sunat_code' => '99',
                    'sunat_description' => 'SUNAT informó un error al procesar el ticket.',
                    'processed_at' => now(),
                ]);
                $this->attempt($summary, 'getStatus', 'rejected', '99', $summary->sunat_description, $started);
                return $summary->refresh();
            }
            if ($response['status_code'] !== '0' || ! is_string($response['cdr'])) {
                throw new RuntimeException('SUNAT no procesó el ticket (código '.$response['status_code'].').');
            }

            $cdr = $this->cdrReader->read($response['cdr']);
            $status = match ($cdr['status']) {
                'accepted' => SunatDailySummary::STATUS_ACCEPTED,
                'observed' => SunatDailySummary::STATUS_OBSERVED,
                default => SunatDailySummary::STATUS_REJECTED,
            };
            $path = 'sunat/cdr/summaries/'.$summary->issue_date->format('Y/m').'/R-'.basename((string) $summary->xml_path, '.xml').'.zip';
            Storage::disk('local')->put($path, $response['cdr']);
            $summary->update([
                'status' => $status,
                'cdr_path' => $path,
                'sunat_code' => $cdr['code'],
                'sunat_description' => $cdr['description'].(empty($cdr['notes']) ? '' : ' | '.implode(' | ', $cdr['notes'])),
                'processed_at' => now(),
            ]);
            if (in_array($status, [SunatDailySummary::STATUS_ACCEPTED, SunatDailySummary::STATUS_OBSERVED], true)) {
                foreach ($summary->load('items.venta.electronicDocument')->items as $item) {
                    if ($item->condition_code === '3') {
                        $item->venta->update(['estado'=>'anulada','estado_sunat'=>'anulada']);
                        continue;
                    }
                    $item->venta->electronicDocument?->update([
                        'status' => $status === SunatDailySummary::STATUS_ACCEPTED ? ElectronicDocument::STATUS_ACCEPTED : ElectronicDocument::STATUS_OBSERVED,
                        'sunat_code' => $cdr['code'],
                        'sunat_description' => $cdr['description'],
                        'accepted_at' => now(),
                    ]);
                    $item->venta->update(['estado_sunat' => $status]);
                }
            }
            $this->attempt($summary, 'getStatus', $status, $cdr['code'], $cdr['description'], $started);
            return $summary->refresh();
        } catch (Throwable $exception) {
            // Si SUNAT ya entregó un ticket no debe reenviarse el ZIP. Se conserva
            // el estado pendiente para que el siguiente sondeo consulte el mismo ticket.
            $summary->update([
                'status' => $summary->ticket ? SunatDailySummary::STATUS_TICKET : SunatDailySummary::STATUS_ERROR,
                'sunat_description' => $exception->getMessage(),
            ]);
            $this->attempt($summary, 'getStatus', 'error', null, $exception->getMessage(), $started);
            throw $exception;
        }
    }

    private function signedXml(SunatDailySummary $summary, SunatSetting $setting): string
    {
        if (blank($setting->certificate_path) || ! Storage::disk('local')->exists($setting->certificate_path)) {
            throw new RuntimeException('No se encontró el certificado digital configurado.');
        }
        $signed = $this->signer->sign(
            $this->builder->build($summary),
            Storage::disk('local')->get($setting->certificate_path),
            (string) $setting->certificate_password,
        )->saveXML();
        if ($signed === false) {
            throw new RuntimeException('No se pudo serializar el Resumen Diario firmado.');
        }
        return $signed;
    }

    private function attempt(SunatDailySummary $summary, string $operation, string $result, ?string $code, string $message, float $started): void
    {
        $summary->attempts()->create([
            'attempt_number' => ((int) $summary->attempts()->max('attempt_number')) + 1,
            'operation' => $operation,
            'result' => $result,
            'sunat_code' => $code,
            'message' => $message,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ]);
    }
}
