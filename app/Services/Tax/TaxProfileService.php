<?php

namespace App\Services\Tax;

use App\Models\Configuracion;
use App\Models\TaxProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxProfileService
{
    public const REGIMES = ['nrus', 'rer', 'rmt', 'general'];
    public const SYSTEMS = ['see_sol', 'see_contribuyente', 'pse_ose', 'see_cf'];
    public const TREATMENTS = ['gravada', 'exonerada', 'inafecta', 'nrus_no_desglosado'];

    public function current(): ?TaxProfile
    {
        return TaxProfile::with('capabilities')->where('active', true)
            ->whereDate('valid_from', '<=', today())
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))
            ->latest('valid_from')->latest('id')->first();
    }

    public function capabilities(string $regime, string $system): array
    {
        $caps = ['issue_note_sale'];
        if ($regime === 'nrus') $caps[] = 'issue_boleta';
        else array_push($caps, 'issue_boleta', 'issue_factura', 'issue_credit_note');

        if ($system === 'see_contribuyente') array_push($caps, 'automatic_submission', 'requires_certificate', 'daily_summary');
        if ($system === 'see_sol') $caps[] = 'manual_sunat_link';
        if ($system === 'pse_ose') $caps[] = 'provider_submission';
        if ($system === 'see_cf') $caps[] = 'consumer_final_provider';
        return array_values(array_unique($caps));
    }

    public function validateCombination(string $regime, string $system, string $treatment, float $igvRate): void
    {
        if (!in_array($regime, self::REGIMES, true) || !in_array($system, self::SYSTEMS, true) || !in_array($treatment, self::TREATMENTS, true)) {
            throw ValidationException::withMessages(['tax_profile' => 'La configuración tributaria seleccionada no es válida.']);
        }
        if ($regime === 'nrus' && $system === 'see_contribuyente') {
            throw ValidationException::withMessages(['emission_system' => 'Nuevo RUS no puede activar SEE del Contribuyente en esta configuración. Usa SEE-SOL, SEE-CF o un proveedor autorizado.']);
        }
        if ($regime === 'nrus' && $treatment !== 'nrus_no_desglosado') {
            throw ValidationException::withMessages(['default_tax_treatment' => 'Nuevo RUS debe usar el tratamiento "IGV no desglosado". No corresponde declararlo como gravado, exonerado o inafecto.']);
        }
        if ($regime !== 'nrus' && $treatment === 'nrus_no_desglosado') {
            throw ValidationException::withMessages(['default_tax_treatment' => 'El tratamiento "IGV no desglosado" se utiliza exclusivamente con Nuevo RUS.']);
        }
        if ($treatment === 'gravada' && $igvRate <= 0) {
            throw ValidationException::withMessages(['igv_rate' => 'Una operación gravada necesita una tasa de IGV mayor que cero.']);
        }
    }

    public function activate(array $data, ?int $userId): TaxProfile
    {
        $rate = $data['default_tax_treatment'] === 'gravada' ? (float) $data['igv_rate'] : 0;
        $this->validateCombination($data['tax_regime'], $data['emission_system'], $data['default_tax_treatment'], $rate);

        $confirmedBy = $userId && User::whereKey($userId)->exists() ? $userId : null;

        return DB::transaction(function () use ($data, $rate, $confirmedBy) {
            TaxProfile::where('active', true)->update(['active' => false, 'valid_until' => today()->subDay()]);
            $profile = TaxProfile::create([
                'name' => $data['name'], 'tax_regime' => $data['tax_regime'],
                'emission_system' => $data['emission_system'], 'environment' => $data['environment'] ?? 'beta',
                'default_tax_treatment' => $data['default_tax_treatment'], 'igv_rate' => $rate,
                'active' => true, 'valid_from' => today(), 'confirmed_by' => $confirmedBy, 'confirmed_at' => now(),
            ]);
            foreach ($this->capabilities($profile->tax_regime, $profile->emission_system) as $capability) {
                $profile->capabilities()->create(['capability' => $capability, 'enabled' => true]);
            }
            if ($config = Configuracion::first()) {
                $config->igv = $rate;
                $config->save();
            }
            return $profile->load('capabilities');
        });
    }

    public function has(?TaxProfile $profile, string $capability): bool
    {
        return $profile?->capabilities->firstWhere('capability', $capability)?->enabled === true;
    }
}
