<?php

namespace App\Services\Sunat;

use App\Models\SunatSetting;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SunatBillServiceClient
{
    public function sendBill(SunatSetting $setting, string $zipFileName, string $zipContents): string
    {
        $xpath = $this->request($setting, 'sendBill', [
            'fileName' => $zipFileName,
            'contentFile' => base64_encode($zipContents),
        ]);
        $encoded = trim($xpath->evaluate('string(//*[local-name()="applicationResponse"])'));
        $cdr = base64_decode($encoded, true);
        if ($encoded === '' || $cdr === false) {
            throw new RuntimeException('SUNAT no devolvió un CDR reconocible.');
        }
        return $cdr;
    }

    public function sendSummary(SunatSetting $setting, string $zipFileName, string $zipContents): string
    {
        $xpath = $this->request($setting, 'sendSummary', [
            'fileName' => $zipFileName,
            'contentFile' => base64_encode($zipContents),
        ]);
        $ticket = trim($xpath->evaluate('string(//*[local-name()="ticket"])'));
        if ($ticket === '') {
            throw new RuntimeException('SUNAT no devolvió el ticket del Resumen Diario.');
        }
        return $ticket;
    }

    public function getStatus(SunatSetting $setting, string $ticket): array
    {
        $xpath = $this->request($setting, 'getStatus', ['ticket' => $ticket]);
        $statusCode = trim($xpath->evaluate('string(//*[local-name()="statusCode"])'));
        $encoded = trim($xpath->evaluate('string(//*[local-name()="content"])'));
        if ($statusCode === '') {
            throw new RuntimeException('SUNAT no devolvió el estado del ticket.');
        }
        $cdr = $encoded === '' ? null : base64_decode($encoded, true);
        if ($encoded !== '' && $cdr === false) {
            throw new RuntimeException('SUNAT devolvió un CDR inválido para el ticket.');
        }
        return ['status_code' => $statusCode, 'cdr' => $cdr];
    }

    private function request(SunatSetting $setting, string $operation, array $parameters): DOMXPath
    {
        $environment = config('sunat.environments.'.$setting->environment);
        if (! is_array($environment) || blank($environment['bill_service'] ?? null)) {
            throw new RuntimeException('El ambiente SUNAT seleccionado no tiene un servicio configurado.');
        }
        $solUser = $setting->environment === 'beta' ? $environment['sol_user'] : $setting->sol_user;
        $solPassword = $setting->environment === 'beta' ? $environment['sol_password'] : $setting->sol_password;
        $username = preg_replace('/\D+/', '', (string) $setting->fiscal_ruc).$solUser;
        $envelope = $this->envelope($username, (string) $solPassword, $operation, $parameters);

        try {
            $response = Http::timeout(45)->connectTimeout(15)
                ->withHeaders(['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => 'urn:'.$operation])
                ->withBody($envelope, 'text/xml; charset=utf-8')
                ->post($environment['bill_service']);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('No se pudo conectar con SUNAT. El documento queda pendiente para reintento.', 0, $exception);
        }
        if (! $response->successful()) {
            throw new RuntimeException('SUNAT respondió por HTTP con el estado '.$response->status().'.');
        }

        $xml = new DOMDocument();
        if (! $xml->loadXML($response->body(), LIBXML_NONET)) {
            throw new RuntimeException('SUNAT devolvió una respuesta SOAP inválida.');
        }
        $xpath = new DOMXPath($xml);
        $fault = trim($xpath->evaluate('string(//*[local-name()="Fault"]/*[local-name()="faultstring"])'));
        if ($fault !== '') {
            $code = trim($xpath->evaluate('string(//*[local-name()="Fault"]/*[local-name()="faultcode"])'));
            throw new RuntimeException('SUNAT rechazó la operación'.($code !== '' ? " ($code)" : '').": $fault");
        }
        return $xpath;
    }

    private function envelope(string $username, string $password, string $operation, array $parameters): string
    {
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $body = '';
        foreach ($parameters as $name => $value) {
            $body .= '<'.$name.'>'.$escape((string) $value).'</'.$name.'>';
        }
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">'
            .'<soapenv:Header><wsse:Security><wsse:UsernameToken>'
            .'<wsse:Username>'.$escape($username).'</wsse:Username><wsse:Password>'.$escape($password).'</wsse:Password>'
            .'</wsse:UsernameToken></wsse:Security></soapenv:Header>'
            .'<soapenv:Body><ser:'.$operation.'>'.$body.'</ser:'.$operation.'></soapenv:Body></soapenv:Envelope>';
    }
}
