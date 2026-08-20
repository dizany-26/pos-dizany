<?php

namespace App\Services\Sunat;

use NumberFormatter;

class AmountInWords
{
    public function soles(float $amount): string
    {
        $whole = (int) floor(round($amount, 2));
        $cents = (int) round((round($amount, 2) - $whole) * 100);
        if ($cents === 100) {
            $whole++;
            $cents = 0;
        }

        $formatter = new NumberFormatter('es_PE', NumberFormatter::SPELLOUT);
        $words = mb_strtoupper((string) $formatter->format($whole), 'UTF-8');

        return trim($words).' CON '.str_pad((string) $cents, 2, '0', STR_PAD_LEFT).'/100 SOLES';
    }
}
