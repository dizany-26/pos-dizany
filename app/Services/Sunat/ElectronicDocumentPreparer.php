<?php

namespace App\Services\Sunat;

use App\Models\ElectronicDocument;
use App\Models\SunatEstablishment;
use App\Models\SunatSetting;
use App\Models\Venta;
use Illuminate\Validation\ValidationException;

class ElectronicDocumentPreparer
{
    public function prepare(Venta $venta): ElectronicDocument
    {
        $venta->loadMissing(['cliente', 'detalleVentas.producto']);
        $setting = SunatSetting::current();
        $establishment = SunatEstablishment::defaultLocation();
        $snapshot = $this->snapshot($venta, $setting, $establishment);
        $type = $snapshot['document']['type'];

        return ElectronicDocument::updateOrCreate(
            ['venta_id' => $venta->id],
            [
                'document_type' => $type,
                'series' => $venta->serie,
                'number' => $venta->correlativo,
                'status' => ElectronicDocument::STATUS_DRAFT,
                'snapshot' => $snapshot,
            ]
        );
    }

    public function snapshot(Venta $venta, SunatSetting $setting, ?SunatEstablishment $establishment): array
    {
        $this->validate($venta, $setting, $establishment);
        $type = $venta->tipo_comprobante === 'factura' ? '01' : '03';

        return [
                    'version' => 1,
                    'issuer' => [
                        'ruc' => preg_replace('/\D+/', '', (string) $setting->fiscal_ruc),
                        'legal_name' => $setting->legal_name,
                        'trade_name' => $setting->trade_name,
                        'establishment' => [
                            'code' => $establishment->code,
                            'name' => $establishment->name,
                            'ubigeo' => $establishment->ubigeo,
                            'department' => $establishment->department,
                            'province' => $establishment->province,
                            'district' => $establishment->district,
                            'address' => $establishment->address,
                        ],
                    ],
                    'customer' => $venta->cliente ? [
                        'document_type' => filled($venta->cliente->ruc) ? '6' : '1',
                        'document_number' => $venta->cliente->ruc ?: $venta->cliente->dni,
                        'name' => $venta->cliente->nombre,
                        'address' => $venta->cliente->direccion,
                    ] : [
                        'document_type' => '0',
                        'document_number' => '',
                        'name' => 'PÚBLICO GENERAL',
                        'address' => '',
                    ],
                    'document' => [
                        'type' => $type,
                        'series' => $venta->serie,
                        'number' => (int) $venta->correlativo,
                        'issued_at' => $venta->fecha->toIso8601String(),
                        'currency' => 'PEN',
                    ],
                    'payment' => [
                        'status' => $venta->estado,
                        'balance' => (float) $venta->saldo,
                        'due_date' => optional($venta->credit_due_date)->format('Y-m-d'),
                    ],
                    'totals' => [
                        'taxable' => (float) $venta->op_gravadas,
                        'exonerated' => (float) ($venta->op_exoneradas ?? 0),
                        'unaffected' => (float) ($venta->op_inafectas ?? 0),
                        'tax_treatment' => $venta->tax_treatment ?: 'gravada',
                        'igv_rate' => (float) ($venta->op_gravadas > 0 ? round(($venta->igv / $venta->op_gravadas) * 100, 4) : 0),
                        'igv' => (float) $venta->igv,
                        'payable' => (float) $venta->total,
                    ],
                    'lines' => $venta->detalleVentas->values()->map(fn ($line, $index) => [
                        'line' => $index + 1,
                        'product_id' => $line->producto_id,
                        'description' => $line->producto->nombre,
                        'quantity' => (int) $line->cantidad,
                        'presentation' => $line->presentacion,
                        'unit_price_without_igv' => (float) $line->precio_presentacion,
                        'line_total_without_igv' => (float) $line->subtotal,
                        'tax_treatment' => $venta->tax_treatment ?: 'gravada',
                    ])->all(),
                ];
    }

    private function validate(Venta $venta, SunatSetting $setting, ?SunatEstablishment $establishment): void
    {
        $errors = [];
        $ruc = preg_replace('/\D+/', '', (string) $setting->fiscal_ruc);

        if (strlen($ruc) !== 11) {
            $errors['issuer.ruc'][] = 'El RUC del emisor debe tener 11 dígitos.';
        }

        if (blank($setting->legal_name)) {
            $errors['issuer.name'][] = 'Falta la razón social en la configuración general.';
        }

        if (! $establishment || blank($establishment->address) || strlen((string) $establishment->ubigeo) !== 6) {
            $errors['issuer.establishment'][] = 'Falta configurar un establecimiento emisor con dirección y ubigeo válidos.';
        }

        if (! in_array($venta->tipo_comprobante, ['factura', 'boleta'], true)) {
            $errors['document.type'][] = 'Solo las facturas y boletas se preparan para SUNAT.';
        }

        if ($venta->tipo_comprobante === 'factura' && strlen((string) optional($venta->cliente)->ruc) !== 11) {
            $errors['customer.ruc'][] = 'Una factura requiere un cliente con RUC de 11 dígitos.';
        }

        if ($venta->detalleVentas->isEmpty()) {
            $errors['lines'][] = 'El comprobante debe tener al menos un producto.';
        }

        if ((float) $venta->saldo > 0 && ! $venta->credit_due_date) {
            $errors['payment'][] = 'La venta al crédito requiere una fecha de vencimiento para su cuota.';
        }

        $treatment = $venta->tax_treatment ?: 'gravada';
        if (! in_array($treatment, ['gravada', 'exonerada', 'inafecta'], true)) {
            $errors['taxes'][] = 'El tratamiento tributario de la venta no es compatible.';
        }
        if ($treatment === 'gravada' && ((float) $venta->igv <= 0 || (float) $venta->op_gravadas <= 0)) {
            $errors['taxes'][] = 'La operación gravada requiere base imponible e IGV.';
        }

        $base = (float) $venta->op_gravadas + (float) ($venta->op_exoneradas ?? 0) + (float) ($venta->op_inafectas ?? 0);
        if (abs(round($base + (float) $venta->igv, 2) - (float) $venta->total) > 0.01) {
            $errors['totals'][] = 'Los totales de la venta no cuadran para la emisión electrónica.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
