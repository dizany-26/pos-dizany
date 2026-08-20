<?php

namespace App\Http\Controllers;

use App\Models\SunatEstablishment;
use App\Models\SunatSetting;
use App\Models\Configuracion;
use App\Models\ManualTaxDocument;
use App\Models\User;
use App\Models\Venta;
use App\Models\ElectronicDocument;
use App\Models\SunatDailySummary;
use App\Models\ElectronicCreditNote;
use App\Jobs\SendElectronicCreditNote;
use App\Services\Sunat\ElectronicCreditNotePreparer;
use App\Jobs\SendElectronicInvoice;
use App\Jobs\SendSunatDailySummary;
use App\Services\Sunat\SunatDailySummaryPreparer;
use App\Services\Sunat\SunatDailySummaryService;
use Carbon\CarbonImmutable;
use App\Services\Sunat\ElectronicDocumentGenerator;
use App\Services\Sunat\ElectronicDocumentPreparer;
use App\Services\Sunat\SunatDemoXml;
use App\Services\Sunat\SunatZipArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\Tax\TaxProfileService;

class SunatConfigurationController extends Controller
{
    public function edit(TaxProfileService $taxProfiles)
    {
        $setting = SunatSetting::current();
        $establishment = SunatEstablishment::defaultLocation() ?? new SunatEstablishment([
            'code' => '0000',
            'is_default' => true,
            'active' => true,
        ]);
        $companyRuc = preg_replace('/\D+/', '', (string) $setting->fiscal_ruc);
        $taxProfile = $taxProfiles->current();

        $readiness = [
            ['label' => 'RUC del emisor (11 dígitos)', 'ready' => strlen($companyRuc) === 11, 'value' => $companyRuc ?: 'Pendiente'],
            ['label' => 'Razón social tributaria', 'ready' => filled($setting->legal_name), 'value' => $setting->legal_name ?: 'Pendiente'],
            ['label' => 'Establecimiento emisor', 'ready' => $establishment->exists && filled($establishment->address) && strlen((string) $establishment->ubigeo) === 6, 'value' => $establishment->exists ? ($establishment->code.' · '.$establishment->address) : 'Pendiente'],
            ['label' => 'Credenciales de SUNAT Beta', 'ready' => true, 'value' => 'MODDATOS · exclusivo para pruebas'],
            ['label' => 'Certificado digital vigente', 'ready' => filled($setting->certificate_path) && (bool) $setting->certificate_expires_at?->isFuture(), 'value' => $setting->certificate_expires_at ? 'Vence '.$setting->certificate_expires_at->format('d/m/Y') : 'Pendiente'],
        ];

        return view('configuracion.sunat', [
            'setting' => $setting,
            'config' => Configuracion::first(),
            'establishment' => $establishment,
            'readiness' => $readiness,
            'readyCount' => collect($readiness)->where('ready', true)->count(),
            'environments' => config('sunat.environments'),
            'sales' => Venta::with(['cliente', 'electronicDocument', 'manualTaxDocument', 'taxProfile'])
                ->whereIn('tipo_comprobante', ['factura', 'boleta'])
                ->latest('fecha')
                ->limit(8)
                ->get(),
            'electronicDocuments' => ElectronicDocument::with('venta.cliente')
                ->latest('id')->limit(20)->get(),
            'dailySummaries' => SunatDailySummary::withCount('items')
                ->latest('id')->limit(20)->get(),
            'creditNotes' => ElectronicCreditNote::with(['venta.cliente','originalDocument'])->latest('id')->limit(20)->get(),
            'manualDocuments' => ManualTaxDocument::with('venta.cliente')->latest('issued_at')->limit(20)->get(),
            'sunatStats' => [
                'accepted' => ElectronicDocument::whereIn('status', ['accepted', 'observed'])->count(),
                'pending' => ElectronicDocument::whereIn('status', ['draft', 'ready', 'sending', 'pending_summary', 'summary_ticket', 'error'])->count(),
                'rejected' => ElectronicDocument::where('status', 'rejected')->count(),
                'summaries_pending' => SunatDailySummary::whereIn('status', ['draft', 'ready', 'sending', 'ticket', 'error'])->count(),
            ],
            'taxProfile' => $taxProfile,
            'taxRegimes' => [
                'nrus' => 'Nuevo RUS', 'rer' => 'Régimen Especial (RER)',
                'rmt' => 'Régimen MYPE Tributario', 'general' => 'Régimen General',
            ],
            'emissionSystems' => [
                'see_sol' => 'SEE-SOL · registro manual gratuito',
                'see_contribuyente' => 'SEE del Contribuyente · conexión directa',
                'pse_ose' => 'Proveedor PSE/OSE',
                'see_cf' => 'SEE Consumidor Final · proveedor autorizado',
            ],
        ]);
    }

    public function activateTaxProfile(Request $request, TaxProfileService $profiles)
    {
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'tax_regime' => ['required','in:nrus,rer,rmt,general'],
            'emission_system' => ['required','in:see_sol,see_contribuyente,pse_ose,see_cf'],
            'default_tax_treatment' => ['required','in:gravada,exonerada,inafecta,nrus_no_desglosado'],
            'igv_rate' => ['nullable','numeric','min:0','max:100'],
        ]);
        $data['environment'] = 'beta';
        $data['igv_rate'] = (float) ($data['igv_rate'] ?? 0);
        $profile = $profiles->activate($data, auth()->id());
        return back()->with('success', 'Perfil tributario activado: '.$profile->name.'. Las ventas nuevas conservarán esta configuración como historial.');
    }

    public function linkSolDocument(Request $request, Venta $venta, TaxProfileService $profiles)
    {
        $profile = $venta->taxProfile ?: $profiles->current();
        abort_unless($profile && $profiles->has($profile, 'manual_sunat_link'), 422, 'Esta venta no pertenece a una modalidad manual SEE-SOL.');
        abort_unless($venta->tipo_comprobante === 'boleta' && $venta->emission_system === 'see_sol', 422, 'Solo se pueden vincular boletas correspondientes a SEE-SOL.');
        abort_if($venta->manualTaxDocument()->exists(), 422, 'Esta venta ya tiene una boleta oficial vinculada. El registro no puede reemplazarse.');
        $data = $request->validate([
            'series' => ['required','regex:/^(?:EB01|B[A-Z0-9]{3})$/','max:4'],
            'number' => ['required','integer','min:1'],
            'issued_at' => ['required','date','before_or_equal:now'],
            'notes' => ['nullable','string','max:500'],
        ]);

        $duplicado = ManualTaxDocument::where('series', strtoupper($data['series']))
            ->where('number', $data['number'])
            ->exists();
        if ($duplicado) {
            throw ValidationException::withMessages(['number' => 'Esa serie y número de boleta ya fueron vinculados a otra venta.']);
        }

        ManualTaxDocument::create([
            'venta_id'=>$venta->id,
            'document_type'=>'boleta','series'=>strtoupper($data['series']),'number'=>$data['number'],
            'issued_at'=>$data['issued_at'],'total'=>$venta->total,'status'=>'issued',
            'notes'=>$data['notes'] ?? null,
            'linked_by'=>User::whereKey(auth()->id())->exists() ? auth()->id() : null,
        ]);
        $venta->update(['estado_sunat'=>'registrado_sol']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'La boleta oficial de SUNAT SOL quedó vinculada correctamente.',
            ]);
        }
        return back()->with('success','La boleta oficial de SUNAT SOL quedó vinculada a la venta sin alterar su historial interno.');
    }

    public function retryDocument(ElectronicDocument $document)
    {
        abort_unless($document->document_type === '01', 422, 'Las boletas se reintentan mediante su Resumen Diario.');
        abort_if(in_array($document->status, ['accepted', 'observed'], true), 422, 'El comprobante ya fue aceptado por SUNAT.');
        SendElectronicInvoice::dispatch($document->venta_id);
        return back()->with('success', 'La factura fue enviada a la cola segura de reintentos.');
    }

    public function createCreditNote(Request $request, Venta $venta, ElectronicCreditNotePreparer $preparer)
    {
        $data=$request->validate([
            'reason_code'=>['required','in:01,02,03,06,07'],
            'reason'=>['required','string','min:5','max:250'],
        ]);
        $note=$preparer->prepare($venta,$data['reason_code'],$data['reason']);
        SendElectronicCreditNote::dispatch($note->id);
        return back()->with('success', 'La nota de crédito '.$note->series.'-'.str_pad((string)$note->number,8,'0',STR_PAD_LEFT).' fue creada y enviada a la cola Beta.');
    }

    public function retryCreditNote(ElectronicCreditNote $note)
    {
        abort_if(in_array($note->status,['accepted','observed'],true),422,'La nota ya fue aceptada por SUNAT.');
        SendElectronicCreditNote::dispatch($note->id);
        return back()->with('success','La nota de crédito fue enviada nuevamente a la cola segura.');
    }

    public function downloadCreditNote(ElectronicCreditNote $note, string $kind)
    {
        abort_unless(in_array($kind,['xml','cdr'],true),404);
        return $this->privateDownload($kind==='xml'?$note->xml_path:$note->cdr_path);
    }

    public function createDailySummary(Request $request, SunatDailySummaryPreparer $preparer)
    {
        $data = $request->validate(['reference_date' => ['required', 'date', 'before_or_equal:today']]);
        $summary = $preparer->prepare(CarbonImmutable::parse($data['reference_date']));
        if (! $summary) {
            return back()->with('success', 'No hay boletas pendientes de informar para esa fecha.');
        }
        SendSunatDailySummary::dispatch($summary->id);
        return back()->with('success', $summary->identifier.' fue preparado con '.$summary->items->count().' boleta(s).');
    }

    public function cancelBoleta(Venta $venta, SunatDailySummaryPreparer $preparer)
    {
        $summary=$preparer->prepareCancellation($venta);
        SendSunatDailySummary::dispatch($summary->id);
        return back()->with('success','La baja de la boleta fue incluida en '.$summary->identifier.' y enviada a la cola Beta.');
    }

    public function retrySummary(SunatDailySummary $summary, SunatDailySummaryService $service)
    {
        abort_if(in_array($summary->status, ['accepted', 'observed'], true), 422, 'El Resumen Diario ya fue procesado.');
        if ($summary->ticket) {
            $service->check($summary);
            return back()->with('success', 'Se consultó nuevamente el ticket '.$summary->ticket.'.');
        }
        SendSunatDailySummary::dispatch($summary->id);
        return back()->with('success', 'El Resumen Diario fue enviado a la cola segura.');
    }

    public function downloadDocument(ElectronicDocument $document, string $kind)
    {
        abort_unless(in_array($kind, ['xml', 'cdr'], true), 404);
        return $this->privateDownload($kind === 'xml' ? $document->xml_path : $document->cdr_path);
    }

    public function downloadSummary(SunatDailySummary $summary, string $kind)
    {
        abort_unless(in_array($kind, ['xml', 'cdr'], true), 404);
        return $this->privateDownload($kind === 'xml' ? $summary->xml_path : $summary->cdr_path);
    }

    private function privateDownload(?string $path)
    {
        abort_if(blank($path) || ! Storage::disk('local')->exists($path), 404, 'El archivo privado todavía no está disponible.');
        return Storage::disk('local')->download($path, basename($path), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function prepareXml(
        Venta $venta,
        ElectronicDocumentPreparer $preparer,
        ElectronicDocumentGenerator $generator,
    ) {
        try {
            $document = $preparer->prepare($venta);
            $generator->generate($document, SunatSetting::current());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'xml' => 'No se pudo generar el XML firmado: '.$exception->getMessage(),
            ]);
        }

        return back()->with('success', sprintf(
            'XML UBL 2.1 firmado para %s-%08d. Se guardó de forma privada y no fue enviado a SUNAT.',
            $document->series,
            $document->number,
        ));
    }

    public function downloadDemo(SunatDemoXml $demo)
    {
        try {
            $xml = $demo->generate();
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['demo' => 'No se pudo crear la demostración: '.$exception->getMessage()]);
        }

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="DEMO-SIN-VALIDEZ-TRIBUTARIA.xml"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadDemoZip(SunatDemoXml $demo, SunatZipArchive $archive)
    {
        try {
            $xml = $demo->generate();
            $zip = $archive->create('20123456789-03-BDEM-00000001.xml', $xml);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['demo' => 'No se pudo crear el ZIP de demostración: '.$exception->getMessage()]);
        }

        return response($zip, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="DEMO-SUNAT-SIN-VALIDEZ.zip"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request, TaxProfileService $taxProfiles)
    {
        $setting = SunatSetting::current();
        $taxProfile = $taxProfiles->current();
        abort_unless($taxProfile, 422, 'Primero activa un perfil tributario.');
        $directMode = $taxProfile->emission_system === 'see_contribuyente';
        $data = $request->validate([
            'environment' => ['nullable', 'in:beta'],
            'fiscal_ruc' => ['required', 'digits:11'],
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'establishment_code' => ['required', 'digits:4'],
            'establishment_name' => ['nullable', 'string', 'max:255'],
            'ubigeo' => ['required', 'digits:6'],
            'department' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'fiscal_address' => ['required', 'string', 'max:255'],
            'sol_user' => ['nullable', 'string', 'max:100'],
            'sol_password' => ['nullable', 'string', 'max:255'],
            'certificate' => ['nullable', 'file', 'mimes:p12,pfx', 'max:5120'],
            'certificate_password' => ['nullable', 'string', 'max:255'],
            'enabled' => ['nullable', 'boolean'],
        ]);
        $data['environment'] = 'beta';

        $establishmentData = [
            'code' => $data['establishment_code'],
            'name' => $data['establishment_name'] ?? null,
            'ubigeo' => $data['ubigeo'],
            'department' => $data['department'],
            'province' => $data['province'],
            'district' => $data['district'],
            'address' => $data['fiscal_address'],
            'is_default' => true,
            'active' => true,
        ];
        unset($data['establishment_code'], $data['establishment_name'], $data['ubigeo'], $data['department'], $data['province'], $data['district'], $data['fiscal_address']);

        if ($request->hasFile('certificate')) {
            if (blank($data['certificate_password'] ?? null)) {
                throw ValidationException::withMessages([
                    'certificate_password' => 'Escribe la clave del certificado para comprobarlo antes de guardarlo.',
                ]);
            }

            $certificateContents = file_get_contents($request->file('certificate')->getRealPath());
            $certificateData = [];
            if (! openssl_pkcs12_read($certificateContents, $certificateData, $data['certificate_password'])) {
                throw ValidationException::withMessages([
                    'certificate' => 'El certificado no pudo abrirse. Revisa el archivo y su contraseña.',
                ]);
            }

            if (empty($certificateData['pkey']) || empty($certificateData['cert'])) {
                throw ValidationException::withMessages([
                    'certificate' => 'El archivo debe contener el certificado y su clave privada.',
                ]);
            }

            $certificateInfo = openssl_x509_parse($certificateData['cert']);
            $expiresAt = $certificateInfo['validTo_time_t'] ?? null;
            if (! $expiresAt || $expiresAt <= now()->timestamp) {
                throw ValidationException::withMessages([
                    'certificate' => 'El certificado digital está vencido o no informa una vigencia válida.',
                ]);
            }

            if ($setting->certificate_path) {
                Storage::disk('local')->delete($setting->certificate_path);
            }
            $data['certificate_path'] = $request->file('certificate')
                ->storeAs('sunat/certificates', 'certificate.'.strtolower($request->file('certificate')->getClientOriginalExtension()), 'local');
            $data['certificate_expires_at'] = date('Y-m-d H:i:s', $expiresAt);
        }

        foreach (['sol_password', 'certificate_password'] as $secret) {
            if (blank($data[$secret] ?? null)) {
                unset($data[$secret]);
            }
        }

        $enableBeta = $directMode && $request->boolean('enabled');
        if ($enableBeta) {
            $effectiveCertificate = $data['certificate_path'] ?? $setting->certificate_path;
            $effectiveExpiry = $data['certificate_expires_at'] ?? optional($setting->certificate_expires_at)->toDateTimeString();
            if (blank($effectiveCertificate) || blank($effectiveExpiry) || now()->gte($effectiveExpiry)) {
                throw ValidationException::withMessages([
                    'enabled' => 'Para activar Beta debes cargar un certificado digital vigente con su clave.',
                ]);
            }
        }
        $data['enabled'] = $enableBeta;
        unset($data['certificate']);
        $setting->update($data);
        SunatEstablishment::where('is_default', true)->update(['is_default' => false]);
        SunatEstablishment::updateOrCreate(
            ['code' => $establishmentData['code']],
            $establishmentData
        );

        return back()->with('success', $enableBeta
            ? 'SUNAT Beta quedó activo. Las nuevas facturas compatibles se procesarán automáticamente; producción continúa bloqueada.'
            : 'Configuración guardada de forma privada. La emisión automática permanece desactivada.');
    }
}
