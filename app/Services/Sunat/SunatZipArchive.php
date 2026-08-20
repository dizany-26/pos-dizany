<?php

namespace App\Services\Sunat;

use RuntimeException;
use ZipArchive;

class SunatZipArchive
{
    public function create(string $xmlFileName, string $xmlContents): string
    {
        if (! str_ends_with(strtolower($xmlFileName), '.xml') || basename($xmlFileName) !== $xmlFileName) {
            throw new RuntimeException('El nombre del comprobante XML no es válido.');
        }

        $temporary = tempnam(sys_get_temp_dir(), 'dizany-sunat-');
        if ($temporary === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal para SUNAT.');
        }

        $zip = new ZipArchive();
        $isOpen = false;
        try {
            if ($zip->open($temporary, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo iniciar el paquete ZIP para SUNAT.');
            }
            $isOpen = true;
            if (! $zip->addFromString($xmlFileName, $xmlContents)) {
                throw new RuntimeException('No se pudo agregar el XML al paquete ZIP.');
            }
            $zip->close();
            $isOpen = false;
            $contents = file_get_contents($temporary);
            if ($contents === false) {
                throw new RuntimeException('No se pudo leer el paquete ZIP generado.');
            }

            return $contents;
        } finally {
            if ($isOpen) {
                $zip->close();
            }
            @unlink($temporary);
        }
    }
}
