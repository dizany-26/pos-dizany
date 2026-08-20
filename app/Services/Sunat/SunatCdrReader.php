<?php

namespace App\Services\Sunat;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class SunatCdrReader
{
    public function read(string $zipContents): array
    {
        $temporary = tempnam(sys_get_temp_dir(), 'dizany-cdr-');
        if ($temporary === false || file_put_contents($temporary, $zipContents) === false) {
            throw new RuntimeException('No se pudo preparar el CDR recibido.');
        }

        $zip = new ZipArchive();
        $isOpen = false;
        try {
            if ($zip->open($temporary) !== true) {
                throw new RuntimeException('SUNAT devolvió un CDR que no es un ZIP válido.');
            }
            $isOpen = true;
            if ($zip->numFiles !== 1) {
                throw new RuntimeException('El CDR debe contener exactamente un archivo XML.');
            }
            $entry = $zip->getNameIndex(0);
            if (! is_string($entry) || ! str_ends_with(strtolower($entry), '.xml')) {
                throw new RuntimeException('El CDR no contiene una respuesta XML válida.');
            }
            $xmlContents = $zip->getFromIndex(0);
            if ($xmlContents === false) {
                throw new RuntimeException('No se pudo leer el XML de respuesta del CDR.');
            }
        } finally {
            if ($isOpen) {
                $zip->close();
            }
            @unlink($temporary);
        }

        $xml = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $xml->loadXML($xmlContents, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            throw new RuntimeException('El CDR contiene un XML mal formado.');
        }

        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $code = trim($xpath->evaluate('string(//*[local-name()="DocumentResponse"]//*[local-name()="ResponseCode"][1])'));
        $description = trim($xpath->evaluate('string(//*[local-name()="DocumentResponse"]//*[local-name()="Description"][1])'));
        $notes = [];
        foreach ($xpath->query('//*[local-name()="Note"]') as $note) {
            $value = trim($note->textContent);
            if ($value !== '') {
                $notes[] = $value;
            }
        }

        if ($code === '') {
            throw new RuntimeException('El CDR no contiene el código de respuesta de SUNAT.');
        }

        return [
            'code' => $code,
            'description' => $description,
            'notes' => array_values(array_unique($notes)),
            'accepted' => $code === '0',
            'status' => $code === '0' ? (empty($notes) ? 'accepted' : 'observed') : 'rejected',
            'xml' => $xmlContents,
            'file_name' => $entry,
        ];
    }
}
