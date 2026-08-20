@extends('layouts.app')

@section('header-back')
<button class="btn-header-back" onclick="history.back()"><i class="fas fa-chevron-left"></i></button>
@endsection

@section('header-title')
Facturación electrónica
@endsection

@push('styles')
<style>
    .sunat-page{max-width:1050px;margin:28px auto;padding:0 14px}.sunat-hero{display:flex;justify-content:space-between;gap:22px;align-items:center;margin-bottom:20px}.sunat-eyebrow{color:#1769ff;font-weight:800;font-size:.75rem;letter-spacing:.16em}.sunat-hero h2{font-weight:800;color:var(--text-color,#10213b);margin:4px 0}.sunat-status{border:1px solid #b8e6cb;background:#edfff4;color:#08763a;border-radius:14px;padding:12px 16px;font-weight:700}.sunat-panel{background:var(--card-bg,#fff);border:1px solid var(--border-color,#dbe4f0);border-radius:18px;box-shadow:0 10px 28px rgba(17,46,84,.08);padding:24px}.sunat-warning{display:flex;gap:12px;border:1px solid #ffe1a6;background:#fff9e9;color:#805500;border-radius:14px;padding:14px;margin-bottom:22px}.sunat-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 22px}.sunat-field label{font-weight:700;margin-bottom:7px}.sunat-field small{display:block;color:#718096;margin-top:6px}.sunat-field.full{grid-column:1/-1}.sunat-secret{display:flex;align-items:center;gap:9px;color:#16854d;font-size:.84rem;margin-top:8px}.sunat-actions{display:flex;justify-content:flex-end;border-top:1px solid var(--border-color,#e2e8f0);margin-top:24px;padding-top:18px}.sunat-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px}.sunat-step{border:1px solid var(--border-color,#dbe4f0);border-radius:14px;padding:14px}.sunat-step b{display:block;margin-bottom:4px}.sunat-step span{color:#718096;font-size:.86rem}.sunat-checklist{border:1px solid var(--border-color,#dbe4f0);border-radius:16px;margin-bottom:22px;overflow:hidden}.sunat-checklist-head{display:flex;align-items:center;justify-content:space-between;gap:15px;background:rgba(23,105,255,.08);padding:14px 16px}.sunat-checklist-head strong{display:block}.sunat-checklist-head small{color:#718096}.sunat-checklist-grid{display:grid;grid-template-columns:1fr 1fr}.sunat-check{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-top:1px solid var(--border-color,#e2e8f0)}.sunat-check:nth-child(odd){border-right:1px solid var(--border-color,#e2e8f0)}.sunat-check i{margin-top:3px}.sunat-check.ready i{color:#13a05f}.sunat-check.pending i{color:#e99b00}.sunat-check b{display:block;font-size:.9rem}.sunat-check span{display:block;color:#718096;font-size:.82rem;overflow-wrap:anywhere}.sunat-progress{height:7px;background:#e8eef7;border-radius:10px;overflow:hidden;width:150px}.sunat-progress span{display:block;height:100%;background:#19ad68}.sunat-test{margin-top:22px}.sunat-table-wrap{overflow-x:auto}.sunat-table{width:100%;min-width:720px;border-collapse:collapse}.sunat-table th,.sunat-table td{padding:11px;border-bottom:1px solid var(--border-color,#e2e8f0);text-align:left;font-size:.88rem}.sunat-badge{display:inline-flex;padding:5px 9px;border-radius:999px;background:#eaf1fb;color:#52647d;font-size:.75rem;font-weight:700}.sunat-badge.ready{background:#ddf8e9;color:#08763a}@media(max-width:760px){.sunat-hero{align-items:flex-start;flex-direction:column}.sunat-grid,.sunat-steps,.sunat-checklist-grid{grid-template-columns:1fr}.sunat-check:nth-child(odd){border-right:0}.sunat-field.full{grid-column:auto}.sunat-panel{padding:18px}.sunat-checklist-head{align-items:flex-start;flex-direction:column}.sunat-progress{width:100%}}body.dark-mode .sunat-panel{background:#101d32;border-color:#294260;color:#e8f0fb;box-shadow:0 12px 30px rgba(0,0,0,.28)}body.dark-mode .sunat-warning{background:#352b13;border-color:#6f5720;color:#ffd875}body.dark-mode .sunat-status{background:#102f25;border-color:#21674b;color:#7ce3ae}body.dark-mode .sunat-hero h2,body.dark-mode .sunat-panel h5,body.dark-mode .sunat-field label,body.dark-mode .sunat-step b,body.dark-mode .sunat-check b,body.dark-mode .sunat-checklist-head strong{color:#f1f5f9}body.dark-mode .sunat-checklist,body.dark-mode .sunat-step{background:#12223a;border-color:#304966}body.dark-mode .sunat-checklist-head{background:#182d4c}body.dark-mode .sunat-check,body.dark-mode .sunat-actions{border-color:#304966}body.dark-mode .sunat-check:nth-child(odd){border-right-color:#304966}body.dark-mode .sunat-field small,body.dark-mode .sunat-step span,body.dark-mode .sunat-check span,body.dark-mode .sunat-checklist-head small{color:#9fb0c7}body.dark-mode .sunat-progress{background:#263b58}body.dark-mode .sunat-page .form-control,body.dark-mode .sunat-page .form-select{background-color:#0c192c;border-color:#365373;color:#f3f7fc}body.dark-mode .sunat-page .form-control::placeholder{color:#71839b}body.dark-mode .sunat-page .input-group .btn-outline-primary{border-color:#4d8eff;color:#7fb0ff}body.dark-mode .sunat-page .input-group .btn-outline-primary:hover{background:#276fe8;color:#fff}body.dark-mode .sunat-secret{color:#72dda4}
</style>
<style>
    :root[data-theme='dark'] .sunat-panel{background:#101d32;border-color:#294260;color:#e8f0fb;box-shadow:0 12px 30px rgba(0,0,0,.28)}
    :root[data-theme='dark'] .sunat-warning{background:#352b13;border-color:#6f5720;color:#ffd875}
    :root[data-theme='dark'] .sunat-status{background:#102f25;border-color:#21674b;color:#7ce3ae}
    :root[data-theme='dark'] .sunat-hero h2,:root[data-theme='dark'] .sunat-panel h5,:root[data-theme='dark'] .sunat-field label,:root[data-theme='dark'] .sunat-step b,:root[data-theme='dark'] .sunat-check b,:root[data-theme='dark'] .sunat-checklist-head strong{color:#f1f5f9}
    :root[data-theme='dark'] .sunat-checklist,:root[data-theme='dark'] .sunat-step{background:#12223a;border-color:#304966}
    :root[data-theme='dark'] .sunat-checklist-head{background:#182d4c}
    :root[data-theme='dark'] .sunat-check,:root[data-theme='dark'] .sunat-actions{border-color:#304966}
    :root[data-theme='dark'] .sunat-check:nth-child(odd){border-right-color:#304966}
    :root[data-theme='dark'] .sunat-field small,:root[data-theme='dark'] .sunat-step span,:root[data-theme='dark'] .sunat-check span,:root[data-theme='dark'] .sunat-checklist-head small{color:#9fb0c7}
    :root[data-theme='dark'] .sunat-progress{background:#263b58}
    :root[data-theme='dark'] .sunat-page .form-control,:root[data-theme='dark'] .sunat-page .form-select{background-color:#0c192c;border-color:#365373;color:#f3f7fc}
    :root[data-theme='dark'] .sunat-page .form-control::placeholder{color:#71839b}
    :root[data-theme='dark'] .sunat-page .input-group .btn-outline-primary{border-color:#4d8eff;color:#7fb0ff}
    :root[data-theme='dark'] .sunat-page .input-group .btn-outline-primary:hover{background:#276fe8;color:#fff}
    :root[data-theme='dark'] .sunat-secret{color:#72dda4}
    :root[data-theme='dark'] .sunat-table th,:root[data-theme='dark'] .sunat-table td{border-color:#304966;color:#e8f0fb}
    :root[data-theme='dark'] .sunat-badge{background:#263b58;color:#cbd8e8}
    :root[data-theme='dark'] .sunat-badge.ready{background:#143e31;color:#7ce3ae}
    .sunat-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:24px 0}.sunat-kpi{border:1px solid var(--border-color,#dbe4f0);border-radius:14px;padding:15px;background:var(--card-bg,#fff)}.sunat-kpi span{display:block;color:#718096;font-size:.78rem}.sunat-kpi strong{font-size:1.55rem}.sunat-monitor{margin-top:26px}.sunat-monitor-head{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:10px}.sunat-inline{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.sunat-icon-btn{display:inline-flex;align-items:center;justify-content:center;width:35px;height:35px;border:1px solid #cbd8e8;border-radius:10px;color:#1769ff;background:transparent;text-decoration:none}.sunat-icon-btn:hover{background:#eaf2ff}.sunat-badge.accepted{background:#ddf8e9;color:#08763a}.sunat-badge.pending{background:#fff3cd;color:#8a6500}.sunat-badge.rejected{background:#ffe1e1;color:#b4232c}:root[data-theme='dark'] .sunat-kpi{background:#12223a;border-color:#304966}:root[data-theme='dark'] .sunat-kpi span{color:#9fb0c7}:root[data-theme='dark'] .sunat-icon-btn{border-color:#365373;color:#7fb0ff}:root[data-theme='dark'] .sunat-icon-btn:hover{background:#1b3151}@media(max-width:760px){.sunat-kpis{grid-template-columns:1fr 1fr}.sunat-monitor-head{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const regime = document.getElementById('tax_regime');
    const system = document.getElementById('emission_system');
    const treatment = document.getElementById('default_tax_treatment');
    const rate = document.getElementById('profile_igv_rate');
    const title = document.getElementById('tax-profile-title');
    const description = document.getElementById('tax-profile-description');
    if (!regime || !system) return;

    const refresh = () => {
        [...system.options].forEach(option => option.disabled = regime.value === 'nrus' && option.value === 'see_contribuyente');
        if (system.selectedOptions[0]?.disabled) system.value = 'see_sol';
        [...treatment.options].forEach(option => option.disabled = regime.value === 'nrus'
            ? option.value !== 'nrus_no_desglosado'
            : option.value === 'nrus_no_desglosado');
        if (regime.value === 'nrus') treatment.value = 'nrus_no_desglosado';
        else if (treatment.value === 'nrus_no_desglosado') treatment.value = 'gravada';
        rate.disabled = treatment.value !== 'gravada';
        if (rate.disabled) rate.value = 0; else if (Number(rate.value) <= 0) rate.value = 18;
        const messages = {
            see_sol: ['SEE-SOL', 'DIZANY registra la venta y luego vincula la boleta oficial emitida gratuitamente en SUNAT SOL. No requiere certificado.'],
            see_contribuyente: ['SEE del Contribuyente', 'Emisión automática mediante XML UBL, firma digital, envío y CDR. Requiere certificado y credenciales compatibles.'],
            pse_ose: ['Proveedor PSE/OSE', 'La integración y sus credenciales dependen del proveedor contratado. No se simulará como conexión directa.'],
            see_cf: ['SEE Consumidor Final', 'Requiere un proveedor autorizado para Ticket POS y una configuración específica de SUNAT.'],
        };
        [title.textContent, description.textContent] = messages[system.value];
    };
    [regime, system, treatment].forEach(el => el.addEventListener('change', refresh));
    refresh();
});
</script>
@endpush

@section('content')
<div class="sunat-page">
    <div class="sunat-hero">
        <div>
            <div class="sunat-eyebrow">SUNAT · CONEXIÓN DIRECTA</div>
            <h2>Preparación de comprobantes electrónicos</h2>
            <p class="text-muted mb-0">Primero configuraremos y validaremos todo en Beta. Producción permanecerá bloqueada hasta superar las pruebas.</p>
        </div>
        <div class="sunat-status"><i class="fas fa-file-signature me-2"></i>Etapa 2 · XML y firma</div>
    </div>

    @if(session('success'))
        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger"><strong>Revisa la configuración:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="sunat-panel mb-4">
        <div class="sunat-monitor-head">
            <div>
                <div class="sunat-eyebrow">ARQUITECTURA MULTIRRÉGIMEN</div>
                <h5 class="fw-bold mb-1">Perfil tributario y modalidad de emisión</h5>
                <p class="text-muted mb-0">Se aplicará solo a ventas nuevas; los comprobantes anteriores conservan su configuración histórica.</p>
            </div>
            @if($taxProfile)<span class="sunat-badge ready"><i class="fas fa-check me-1"></i>{{ $taxProfile->name }}</span>@endif
        </div>
        <form method="POST" action="{{ route('sunat.tax-profile.activate') }}" class="sunat-grid mt-3" id="tax-profile-form">
            @csrf
            <div class="sunat-field full">
                <label for="profile_name">Nombre del perfil</label>
                <input class="form-control" id="profile_name" name="name" maxlength="100" required value="{{ old('name', $taxProfile?->name ?? 'Perfil principal') }}" placeholder="Ej. Tienda principal NRUS">
            </div>
            <div class="sunat-field">
                <label for="tax_regime">Régimen tributario</label>
                <select class="form-select" id="tax_regime" name="tax_regime" required>
                    @foreach($taxRegimes as $key=>$label)<option value="{{ $key }}" @selected(old('tax_regime',$taxProfile?->tax_regime)===$key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="sunat-field">
                <label for="emission_system">Modalidad de emisión</label>
                <select class="form-select" id="emission_system" name="emission_system" required>
                    @foreach($emissionSystems as $key=>$label)<option value="{{ $key }}" @selected(old('emission_system',$taxProfile?->emission_system)===$key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="sunat-field">
                <label for="default_tax_treatment">Tratamiento tributario predeterminado</label>
                <select class="form-select" id="default_tax_treatment" name="default_tax_treatment" required>
                    <option value="gravada" @selected(old('default_tax_treatment',$taxProfile?->default_tax_treatment)==='gravada')>Gravada con IGV</option>
                    <option value="exonerada" @selected(old('default_tax_treatment',$taxProfile?->default_tax_treatment)==='exonerada')>Exonerada de IGV</option>
                    <option value="inafecta" @selected(old('default_tax_treatment',$taxProfile?->default_tax_treatment)==='inafecta')>Inafecta al IGV</option>
                    <option value="nrus_no_desglosado" @selected(old('default_tax_treatment',$taxProfile?->default_tax_treatment)==='nrus_no_desglosado')>Nuevo RUS &middot; IGV no desglosado</option>
                </select>
                <small>“Sin IGV” debe identificarse correctamente como exonerada o inafecta.</small>
            </div>
            <div class="sunat-field">
                <label for="profile_igv_rate">Tasa de IGV</label>
                <div class="input-group"><input class="form-control" type="number" min="0" max="100" step="0.01" id="profile_igv_rate" name="igv_rate" value="{{ old('igv_rate',$taxProfile?->igv_rate ?? ($config->igv ?? 18)) }}"><span class="input-group-text">%</span></div>
                <small>Se fuerza a 0 cuando el tratamiento es exonerado o inafecto.</small>
            </div>
            <div class="sunat-field full">
                <div class="sunat-warning mb-0" id="tax-profile-help"><i class="fas fa-info-circle mt-1"></i><div><strong id="tax-profile-title">Configuración controlada</strong><br><span id="tax-profile-description">El sistema verificará documentos y requisitos antes de activarla.</span></div></div>
            </div>
            <div class="sunat-actions full mt-0"><button class="btn btn-primary" type="submit"><i class="fas fa-shield-alt me-2"></i>Activar nuevo perfil</button></div>
        </form>
    </div>

    @if($taxProfile?->emission_system === 'see_sol')
    <div class="sunat-panel mb-4">
        <div class="sunat-checklist-head rounded-3 mb-3"><div><strong><i class="fas fa-link text-primary me-2"></i>SEE-SOL · vincular boleta oficial</strong><small>SUNAT genera la serie y el número. DIZANY no los inventa ni requiere certificado digital en esta modalidad.</small></div></div>
        <div class="sunat-table-wrap"><table class="sunat-table"><thead><tr><th>Venta interna</th><th>Cliente</th><th>Total</th><th>Datos de SUNAT SOL</th></tr></thead><tbody>
        @forelse($sales->filter(fn($sale)=>$sale->emission_system === 'see_sol' && !$sale->manualTaxDocument) as $sale)
            <tr><td>{{ $sale->serie }}-{{ str_pad($sale->correlativo,6,'0',STR_PAD_LEFT) }}</td><td>{{ $sale->cliente?->nombre }}</td><td>S/ {{ number_format($sale->total,2) }}</td><td>
                <form method="POST" action="{{ route('sunat.sol.link',$sale) }}" class="sunat-inline">@csrf
                    <input class="form-control form-control-sm" name="series" maxlength="4" required placeholder="Serie SUNAT">
                    <input class="form-control form-control-sm" type="number" name="number" min="1" required placeholder="Número">
                    <input class="form-control form-control-sm" type="datetime-local" name="issued_at" required value="{{ optional($sale->fecha)->format('Y-m-d\TH:i') }}">
                    <input type="hidden" name="total" value="{{ $sale->total }}"><button class="btn btn-sm btn-outline-primary">Vincular</button>
                </form>
            </td></tr>
        @empty<tr><td colspan="4" class="text-center text-muted py-4">No hay ventas SEE-SOL pendientes de vincular.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    @endif

    @if(!$taxProfile)
    <div class="sunat-panel text-center py-5">
        <i class="fas fa-sliders-h fa-2x text-primary mb-3"></i>
        <h5 class="fw-bold">Configura primero el perfil tributario</h5>
        <p class="text-muted mb-0">Cuando lo actives, DIZANY mostrar&aacute; &uacute;nicamente los datos y herramientas que correspondan a esa modalidad.</p>
    </div>
    @elseif($taxProfile->emission_system !== 'see_contribuyente')
    @php
        $visibleReadiness = collect($readiness)->take(3);
        $visibleReadyCount = $visibleReadiness->where('ready', true)->count();
    @endphp
    <div class="sunat-panel">
        <div class="sunat-checklist-head rounded-3 mb-4">
            <div>
                <strong><i class="fas fa-building text-primary me-2"></i>Datos requeridos para {{ $emissionSystems[$taxProfile->emission_system] }}</strong>
                <small>Solo se muestran la identidad tributaria y el establecimiento emisor. Certificado, XML, CDR y env&iacute;os directos no corresponden a este perfil.</small>
            </div>
            <span class="sunat-badge ready">Perfil activo</span>
        </div>

        <section class="sunat-checklist" aria-labelledby="preparation-title">
            <div class="sunat-checklist-head">
                <div><strong id="preparation-title">Preparaci&oacute;n del emisor</strong><small>{{ $visibleReadyCount }} de {{ $visibleReadiness->count() }} verificaciones completadas</small></div>
                <div class="sunat-progress"><span style="width:{{ $visibleReadiness->count() ? round(($visibleReadyCount / $visibleReadiness->count()) * 100) : 0 }}%"></span></div>
            </div>
            <div class="sunat-checklist-grid">
                @foreach($visibleReadiness as $item)
                    <div class="sunat-check {{ $item['ready'] ? 'ready' : 'pending' }}">
                        <i class="fas {{ $item['ready'] ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                        <div><b>{{ $item['label'] }}</b><span>{{ $item['value'] }}</span></div>
                    </div>
                @endforeach
            </div>
        </section>

        <form method="POST" action="{{ route('sunat.settings.update') }}" autocomplete="off">
            @csrf @method('PUT')
            <input type="hidden" name="environment" value="beta">
            <div class="sunat-grid">
                <div class="sunat-field full"><h5 class="fw-bold mb-1"><i class="fas fa-id-card me-2 text-primary"></i>Identidad tributaria</h5></div>
                <div class="sunat-field">
                    <label for="fiscal_ruc">RUC del emisor</label>
                    <div class="input-group"><input class="form-control" id="fiscal_ruc" name="fiscal_ruc" inputmode="numeric" maxlength="11" required value="{{ old('fiscal_ruc',$setting->fiscal_ruc) }}"><button class="btn btn-outline-primary" type="button" id="query-fiscal-ruc"><i class="fas fa-search me-1"></i>Consultar</button></div>
                    <small id="fiscal-ruc-status" hidden></small>
                </div>
                <div class="sunat-field"><label for="legal_name">Raz&oacute;n social</label><input class="form-control" id="legal_name" name="legal_name" maxlength="255" required value="{{ old('legal_name',$setting->legal_name) }}"></div>
                <div class="sunat-field full"><label for="trade_name">Nombre comercial <span class="text-muted fw-normal">(opcional)</span></label><input class="form-control" id="trade_name" name="trade_name" maxlength="255" value="{{ old('trade_name',$setting->trade_name) }}"></div>

                <div class="sunat-field full mt-2"><h5 class="fw-bold mb-1"><i class="fas fa-store me-2 text-primary"></i>Establecimiento emisor</h5></div>
                <div class="sunat-field"><label for="establishment_code">C&oacute;digo</label><input class="form-control" id="establishment_code" name="establishment_code" inputmode="numeric" maxlength="4" required value="{{ old('establishment_code',$establishment->code ?: '0000') }}"></div>
                <div class="sunat-field"><label for="establishment_name">Nombre del local <span class="text-muted fw-normal">(opcional)</span></label><input class="form-control" id="establishment_name" name="establishment_name" maxlength="255" value="{{ old('establishment_name',$establishment->name) }}"></div>
                <div class="sunat-field"><label for="ubigeo">Ubigeo</label><input class="form-control" id="ubigeo" name="ubigeo" inputmode="numeric" maxlength="6" required value="{{ old('ubigeo',$establishment->ubigeo) }}"></div>
                <div class="sunat-field"><label for="department">Departamento</label><input class="form-control" id="department" name="department" maxlength="100" required value="{{ old('department',$establishment->department) }}"></div>
                <div class="sunat-field"><label for="province">Provincia</label><input class="form-control" id="province" name="province" maxlength="100" required value="{{ old('province',$establishment->province) }}"></div>
                <div class="sunat-field"><label for="district">Distrito</label><input class="form-control" id="district" name="district" maxlength="100" required value="{{ old('district',$establishment->district) }}"></div>
                <div class="sunat-field full"><label for="fiscal_address">Direcci&oacute;n del establecimiento</label><input class="form-control" id="fiscal_address" name="fiscal_address" maxlength="255" required value="{{ old('fiscal_address',$establishment->address) }}"></div>
            </div>
            <div class="sunat-actions"><button class="btn-soft btn-soft-primary px-4" type="submit"><i class="fas fa-save me-2"></i>Guardar datos del emisor</button></div>
        </form>

        @if(in_array($taxProfile->emission_system, ['pse_ose','see_cf']))
        <div class="sunat-warning mt-4 mb-0"><i class="fas fa-info-circle mt-1"></i><div><strong>Integraci&oacute;n mediante proveedor autorizado</strong><br><span>Las credenciales y opciones t&eacute;cnicas aparecer&aacute;n aqu&iacute; cuando se configure el proveedor correspondiente; no se mostrar&aacute;n controles de conexi&oacute;n directa.</span></div></div>
        @endif
    </div>
    @else
    <div class="sunat-panel">
        <div class="sunat-warning">
            <i class="fas fa-vial mt-1"></i>
            <div class="flex-grow-1">
                <strong>¿Todavía no tienes RUC ni certificado?</strong><br>
                <span>Puedes descargar una boleta XML firmada con datos y certificado temporales. Estará marcada como demostración, no modifica tus ventas y no puede enviarse a SUNAT.</span>
                <form class="mt-3" method="POST" action="{{ route('sunat.demo.download') }}">
                    @csrf
                    <button class="btn btn-outline-primary" type="submit"><i class="fas fa-download me-2"></i>Probar con XML de demostración</button>
                </form>
                <form class="mt-2" method="POST" action="{{ route('sunat.demo.zip') }}">
                    @csrf
                    <button class="btn btn-outline-primary" type="submit"><i class="fas fa-file-archive me-2"></i>Descargar ZIP para SUNAT</button>
                </form>
            </div>
        </div>
        <div class="sunat-warning">
            <i class="fas fa-shield-alt mt-1"></i>
            <div><strong>Emisión desactivada por seguridad.</strong><br><span>Guardar estos datos no enviará ventas a SUNAT. Primero probaremos XML, firma digital y recepción del CDR.</span></div>
        </div>

        <section class="sunat-checklist" aria-labelledby="preparation-title">
            <div class="sunat-checklist-head">
                <div>
                    <strong id="preparation-title">1. Preparar empresa y credenciales</strong>
                    <small>{{ $readyCount }} de {{ count($readiness) }} verificaciones completadas</small>
                </div>
                <div class="sunat-progress" title="{{ $readyCount }} de {{ count($readiness) }}"><span style="width:{{ count($readiness) ? round(($readyCount / count($readiness)) * 100) : 0 }}%"></span></div>
            </div>
            <div class="sunat-checklist-grid">
                @foreach($readiness as $item)
                    <div class="sunat-check {{ $item['ready'] ? 'ready' : 'pending' }}">
                        <i class="fas {{ $item['ready'] ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                        <div><b>{{ $item['label'] }}</b><span>{{ $item['value'] }}</span></div>
                    </div>
                @endforeach
            </div>
        </section>

        <form method="POST" action="{{ route('sunat.settings.update') }}" enctype="multipart/form-data" autocomplete="off">
            @csrf @method('PUT')
            <div class="sunat-grid">
                <div class="sunat-field full">
                    <h5 class="fw-bold mb-1"><i class="fas fa-building me-2 text-primary"></i>Identidad tributaria del emisor</h5>
                    <small>Estos datos se usarán en el XML y son independientes del nombre o dirección mostrados en el catálogo.</small>
                </div>
                <div class="sunat-field">
                    <label for="fiscal_ruc">RUC del emisor</label>
                    <div class="input-group">
                        <input class="form-control" id="fiscal_ruc" name="fiscal_ruc" inputmode="numeric" maxlength="11" value="{{ old('fiscal_ruc', $setting->fiscal_ruc) }}" placeholder="11 dígitos">
                        <button class="btn btn-outline-primary" type="button" id="query-fiscal-ruc"><i class="fas fa-search me-1"></i>Consultar</button>
                    </div>
                    <small id="fiscal-ruc-status" hidden></small>
                </div>
                <div class="sunat-field">
                    <label for="legal_name">Razón social</label>
                    <input class="form-control" id="legal_name" name="legal_name" maxlength="255" value="{{ old('legal_name', $setting->legal_name) }}" placeholder="Según ficha RUC">
                </div>
                <div class="sunat-field full">
                    <label for="trade_name">Nombre comercial <span class="text-muted fw-normal">(opcional)</span></label>
                    <input class="form-control" id="trade_name" name="trade_name" maxlength="255" value="{{ old('trade_name', $setting->trade_name) }}" placeholder="Nombre visible del negocio">
                </div>

                <div class="sunat-field full mt-2">
                    <h5 class="fw-bold mb-1"><i class="fas fa-store me-2 text-primary"></i>Establecimiento emisor principal</h5>
                    <small>La dirección corresponde al local que emite el comprobante. Luego podrás registrar otros establecimientos.</small>
                </div>
                <div class="sunat-field">
                    <label for="establishment_code">Código de establecimiento</label>
                    <input class="form-control" id="establishment_code" name="establishment_code" inputmode="numeric" maxlength="4" value="{{ old('establishment_code', $establishment->code ?: '0000') }}">
                    <small>Usualmente 0000 para el domicilio fiscal principal; debe coincidir con SUNAT.</small>
                </div>
                <div class="sunat-field">
                    <label for="establishment_name">Nombre del local <span class="text-muted fw-normal">(opcional)</span></label>
                    <input class="form-control" id="establishment_name" name="establishment_name" maxlength="255" value="{{ old('establishment_name', $establishment->name) }}" placeholder="Principal, Sucursal Centro, etc.">
                </div>
                <div class="sunat-field">
                    <label for="ubigeo">Ubigeo</label>
                    <input class="form-control" id="ubigeo" name="ubigeo" inputmode="numeric" maxlength="6" value="{{ old('ubigeo', $establishment->ubigeo) }}" placeholder="6 dígitos">
                </div>
                <div class="sunat-field">
                    <label for="department">Departamento</label>
                    <input class="form-control" id="department" name="department" maxlength="100" value="{{ old('department', $establishment->department) }}">
                </div>
                <div class="sunat-field">
                    <label for="province">Provincia</label>
                    <input class="form-control" id="province" name="province" maxlength="100" value="{{ old('province', $establishment->province) }}">
                </div>
                <div class="sunat-field">
                    <label for="district">Distrito</label>
                    <input class="form-control" id="district" name="district" maxlength="100" value="{{ old('district', $establishment->district) }}">
                </div>
                <div class="sunat-field full">
                    <label for="fiscal_address">Dirección del establecimiento</label>
                    <input class="form-control" id="fiscal_address" name="fiscal_address" maxlength="255" value="{{ old('fiscal_address', $establishment->address) }}" placeholder="Dirección declarada para este local">
                </div>

                <div class="sunat-field full mt-2">
                    <h5 class="fw-bold mb-1"><i class="fas fa-plug me-2 text-primary"></i>Conexión y firma digital</h5>
                </div>
                <div class="sunat-field full">
                    <label for="environment">Ambiente</label>
                    <select class="form-select" id="environment" name="environment">
                        @foreach($environments as $key => $environment)
                            <option value="{{ $key }}" @selected(old('environment', $setting->environment) === $key) @disabled($key === 'production')>{{ $environment['label'] }}{{ $key === 'production' ? ' · bloqueado hasta aprobar Beta' : '' }}</option>
                        @endforeach
                    </select>
                    <small>Usaremos únicamente Beta durante la implementación inicial.</small>
                </div>
                <div class="sunat-field">
                    <label for="sol_user">Usuario secundario SOL</label>
                    <input class="form-control" id="sol_user" name="sol_user" value="{{ old('sol_user', $setting->sol_user) }}" maxlength="100" autocomplete="off">
                    <small>En Beta se utilizará automáticamente MODDATOS. Este campo se reserva para la futura activación de producción.</small>
                </div>
                <div class="sunat-field">
                    <label for="sol_password">Clave del usuario SOL</label>
                    <input class="form-control" type="password" id="sol_password" name="sol_password" autocomplete="new-password" data-lpignore="true" placeholder="{{ $setting->sol_password ? 'Guardada · escribe solo para cambiarla' : 'Pendiente' }}">
                    <div class="sunat-secret"><i class="fas fa-lock"></i>Se cifra antes de guardarse.</div>
                </div>
                <div class="sunat-field">
                    <label for="certificate">Certificado digital (.P12 o .PFX)</label>
                    <input class="form-control" type="file" id="certificate" name="certificate" accept=".p12,.pfx">
                    <small>
                        @if($setting->certificate_path)
                            Certificado privado almacenado{{ $setting->certificate_expires_at ? ' · vence '.$setting->certificate_expires_at->format('d/m/Y') : '' }}. Selecciona otro solo para reemplazarlo.
                        @else
                            Todavía no se cargó un certificado.
                        @endif
                    </small>
                </div>
                <div class="sunat-field">
                    <label for="certificate_password">Clave del certificado</label>
                    <input class="form-control" type="password" id="certificate_password" name="certificate_password" autocomplete="new-password" data-lpignore="true" placeholder="{{ $setting->certificate_password ? 'Guardada · escribe solo para cambiarla' : 'Pendiente' }}">
                    <div class="sunat-secret"><i class="fas fa-lock"></i>El certificado queda fuera de la carpeta pública.</div>
                </div>
                <div class="sunat-field full">
                    <div class="form-check border rounded-3 p-3 ps-5">
                        <input class="form-check-input" type="checkbox" value="1" id="enabled" name="enabled" @checked(old('enabled', $setting->enabled))>
                        <label class="form-check-label" for="enabled">Activar procesamiento automático exclusivamente en SUNAT Beta</label>
                        <small>Solo procesa facturas pagadas, en soles y gravadas con IGV. Producción está bloqueada en el servidor.</small>
                    </div>
                </div>
            </div>

            <div class="sunat-steps">
                <div class="sunat-step"><b>1. Preparar</b><span>Configuración, estructura y validaciones locales.</span></div>
                <div class="sunat-step"><b>2. Probar en Beta</b><span>XML UBL, firma, envío y lectura del CDR.</span></div>
                <div class="sunat-step"><b>3. Activar</b><span>Producción solo después de aprobar la lista de control.</span></div>
            </div>

            <div class="sunat-actions">
                <button class="btn-soft btn-soft-primary px-4" type="submit"><i class="fas fa-save me-2"></i>Guardar preparación</button>
            </div>
        </form>

        <section class="sunat-test" aria-labelledby="local-test-title">
            <div class="sunat-checklist-head rounded-3 mb-2">
                <div>
                    <strong id="local-test-title">2. Generar y firmar XML local</strong>
                    <small>Prueba una venta real sin enviarla todavía a SUNAT.</small>
                </div>
                <span class="sunat-badge"><i class="fas fa-lock me-1"></i>Envío bloqueado</span>
            </div>
            <div class="sunat-table-wrap">
                <table class="sunat-table">
                    <thead><tr><th>Comprobante</th><th>Fecha</th><th>Cliente</th><th>Total</th><th>XML</th><th>Acción</th></tr></thead>
                    <tbody>
                    @forelse($sales as $sale)
                        <tr>
                            <td>{{ strtoupper($sale->tipo_comprobante) }} {{ $sale->serie }}-{{ str_pad($sale->correlativo, 8, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ optional($sale->fecha)->format('d/m/Y H:i') }}</td>
                            <td>{{ optional($sale->cliente)->nombre ?: 'Sin cliente' }}</td>
                            <td>S/ {{ number_format($sale->total, 2) }}</td>
                            <td><span class="sunat-badge {{ optional($sale->electronicDocument)->status === 'ready' ? 'ready' : '' }}">{{ optional($sale->electronicDocument)->status === 'ready' ? 'Firmado' : 'Pendiente' }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('sunat.documents.prepare', $sale) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-file-signature me-1"></i>Generar XML</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Todavía no hay facturas o boletas para probar.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @php
            $statusLabel = fn($status) => match($status) {
                'accepted' => 'Aceptado', 'observed' => 'Aceptado con observaciones',
                'rejected' => 'Rechazado', 'ticket', 'summary_ticket' => 'Procesando',
                'sending' => 'Enviando', 'ready' => 'Firmado', 'error' => 'Requiere atención',
                'pending_summary' => 'Esperando resumen', default => 'Pendiente'
            };
            $statusClass = fn($status) => in_array($status, ['accepted','observed']) ? 'accepted' : ($status === 'rejected' ? 'rejected' : 'pending');
        @endphp

        <section class="sunat-monitor" aria-labelledby="sunat-monitor-title">
            <div class="sunat-monitor-head">
                <div><h5 id="sunat-monitor-title" class="mb-1"><i class="fas fa-satellite-dish text-primary me-2"></i>Centro de control SUNAT</h5><small class="text-muted">Seguimiento de XML, CDR, tickets y errores de transmisión.</small></div>
                <form class="sunat-inline" method="POST" action="{{ route('sunat.summaries.create') }}">@csrf
                    <input class="form-control form-control-sm" type="date" name="reference_date" max="{{ now()->format('Y-m-d') }}" value="{{ now()->subDay()->format('Y-m-d') }}" required>
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-layer-group me-1"></i>Procesar boletas</button>
                </form>
            </div>
            <div class="sunat-kpis">
                <div class="sunat-kpi"><span>Comprobantes aceptados</span><strong class="text-success">{{ $sunatStats['accepted'] }}</strong></div>
                <div class="sunat-kpi"><span>Pendientes o con error</span><strong class="text-warning">{{ $sunatStats['pending'] }}</strong></div>
                <div class="sunat-kpi"><span>Rechazados</span><strong class="text-danger">{{ $sunatStats['rejected'] }}</strong></div>
                <div class="sunat-kpi"><span>Resúmenes pendientes</span><strong class="text-primary">{{ $sunatStats['summaries_pending'] }}</strong></div>
            </div>

            <div class="sunat-checklist-head rounded-3"><div><strong>Comprobantes electrónicos</strong><small>Facturas individuales y boletas incluidas en Resumen Diario.</small></div></div>
            <div class="sunat-table-wrap mb-4"><table class="sunat-table"><thead><tr><th>Documento</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th>Respuesta SUNAT</th><th>Archivos</th><th>Acción</th></tr></thead><tbody>
                @forelse($electronicDocuments as $document)
                    <tr><td>{{ $document->document_type === '01' ? 'Factura' : 'Boleta' }} {{ $document->series }}-{{ str_pad($document->number,8,'0',STR_PAD_LEFT) }}</td><td>{{ optional($document->venta?->fecha)->format('d/m/Y H:i') }}</td><td>{{ $document->venta?->cliente?->nombre ?: 'Consumidor final' }}</td><td><span class="sunat-badge {{ $statusClass($document->status) }}">{{ $statusLabel($document->status) }}</span></td><td title="{{ $document->sunat_description }}">{{ \Illuminate\Support\Str::limit($document->sunat_description ?: '—', 60) }}</td><td><div class="sunat-inline">@if($document->xml_path)<a class="sunat-icon-btn" title="Descargar XML" href="{{ route('sunat.documents.download',[$document,'xml']) }}"><i class="fas fa-code"></i></a>@endif @if($document->cdr_path)<a class="sunat-icon-btn" title="Descargar CDR" href="{{ route('sunat.documents.download',[$document,'cdr']) }}"><i class="fas fa-file-archive"></i></a>@endif</div></td><td>@if($document->document_type === '01' && !in_array($document->status,['accepted','observed']))<form method="POST" action="{{ route('sunat.documents.retry',$document) }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="fas fa-redo me-1"></i>Reintentar</button></form>@else—@endif</td></tr>
                @empty<tr><td colspan="7" class="text-center text-muted py-4">Aún no hay comprobantes electrónicos preparados.</td></tr>@endforelse
            </tbody></table></div>

            <div class="sunat-checklist-head rounded-3"><div><strong>Resúmenes Diarios de boletas</strong><small>El ticket se consulta automáticamente hasta recibir la CDR.</small></div></div>
            <div class="sunat-table-wrap"><table class="sunat-table"><thead><tr><th>Resumen</th><th>Boletas del día</th><th>Cantidad</th><th>Ticket</th><th>Estado</th><th>Respuesta</th><th>Archivos</th><th>Acción</th></tr></thead><tbody>
                @forelse($dailySummaries as $summary)
                    <tr><td>{{ $summary->identifier }}</td><td>{{ $summary->reference_date->format('d/m/Y') }}</td><td>{{ $summary->items_count }}</td><td>{{ $summary->ticket ?: '—' }}</td><td><span class="sunat-badge {{ $statusClass($summary->status) }}">{{ $statusLabel($summary->status) }}</span></td><td title="{{ $summary->sunat_description }}">{{ \Illuminate\Support\Str::limit($summary->sunat_description ?: '—', 60) }}</td><td><div class="sunat-inline">@if($summary->xml_path)<a class="sunat-icon-btn" title="Descargar XML" href="{{ route('sunat.summaries.download',[$summary,'xml']) }}"><i class="fas fa-code"></i></a>@endif @if($summary->cdr_path)<a class="sunat-icon-btn" title="Descargar CDR" href="{{ route('sunat.summaries.download',[$summary,'cdr']) }}"><i class="fas fa-file-archive"></i></a>@endif</div></td><td>@if(!in_array($summary->status,['accepted','observed']))<form method="POST" action="{{ route('sunat.summaries.retry',$summary) }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="fas fa-sync-alt me-1"></i>{{ $summary->ticket ? 'Consultar' : 'Enviar' }}</button></form>@else—@endif</td></tr>
                @empty<tr><td colspan="8" class="text-center text-muted py-4">Aún no se generaron Resúmenes Diarios.</td></tr>@endforelse
            </tbody></table></div>
            <div class="sunat-checklist-head rounded-3 mt-4"><div><strong>Anular boleta aceptada</strong><small>La baja se comunica mediante un nuevo Resumen Diario y queda pendiente hasta recibir su CDR.</small></div></div>
            <form class="sunat-inline mb-4" method="POST" action="" id="cancel-boleta-form">@csrf
                <select class="form-select form-select-sm" id="cancel-boleta-sale" required><option value="">Selecciona una boleta aceptada</option>
                    @foreach($electronicDocuments->where('document_type','03')->whereIn('status',['accepted','observed']) as $document)
                        @if(!$document->venta?->dailySummaryItems()->where('condition_code','3')->exists())<option value="{{ route('sunat.boletas.cancel',$document->venta) }}">{{ $document->series }}-{{ str_pad($document->number,8,'0',STR_PAD_LEFT) }} · {{ $document->venta?->cliente?->nombre ?: 'Consumidor final' }}</option>@endif
                    @endforeach
                </select><button class="btn btn-sm btn-outline-danger">Comunicar baja</button>
            </form>
            <div class="sunat-checklist-head rounded-3"><div><strong>Emitir nota de crédito</strong><small>Para facturas aceptadas. La venta se anula recién cuando SUNAT acepta la nota.</small></div></div>
            <form class="sunat-inline mb-4" method="POST" action="" id="credit-note-form">@csrf
                <select class="form-select form-select-sm" id="credit-note-sale" required><option value="">Selecciona una factura aceptada</option>
                    @foreach($electronicDocuments->where('document_type','01')->whereIn('status',['accepted','observed']) as $document)
                        @if(!$document->venta?->electronicCreditNotes()->whereIn('status',['draft','ready','sending','accepted','observed'])->exists())<option value="{{ route('sunat.credit-notes.store',$document->venta) }}">{{ $document->series }}-{{ str_pad($document->number,8,'0',STR_PAD_LEFT) }} · {{ $document->venta?->cliente?->nombre }}</option>@endif
                    @endforeach
                </select>
                <select class="form-select form-select-sm" name="reason_code" required><option value="01">Anulación de la operación</option><option value="02">Anulación por error en RUC</option><option value="03">Corrección por error en descripción</option><option value="06">Devolución total</option></select>
                <input class="form-control form-control-sm" name="reason" minlength="5" maxlength="250" required placeholder="Motivo detallado">
                <button class="btn btn-sm btn-outline-danger">Emitir nota</button>
            </form>
            <div class="sunat-checklist-head rounded-3"><div><strong>Notas de crédito</strong><small>Historial tributario, XML y CDR.</small></div></div>
            <div class="sunat-table-wrap"><table class="sunat-table"><thead><tr><th>Nota</th><th>Factura</th><th>Motivo</th><th>Estado</th><th>Respuesta</th><th>Archivos</th><th>Acción</th></tr></thead><tbody>
                @forelse($creditNotes as $note)<tr><td>{{ $note->series }}-{{ str_pad($note->number,8,'0',STR_PAD_LEFT) }}</td><td>{{ $note->originalDocument?->series }}-{{ str_pad((string)$note->originalDocument?->number,8,'0',STR_PAD_LEFT) }}</td><td>{{ $note->reason_code }} · {{ $note->reason }}</td><td><span class="sunat-badge {{ $statusClass($note->status) }}">{{ $statusLabel($note->status) }}</span></td><td>{{ \Illuminate\Support\Str::limit($note->sunat_description ?: '—',60) }}</td><td><div class="sunat-inline">@if($note->xml_path)<a class="sunat-icon-btn" href="{{ route('sunat.credit-notes.download',[$note,'xml']) }}"><i class="fas fa-code"></i></a>@endif @if($note->cdr_path)<a class="sunat-icon-btn" href="{{ route('sunat.credit-notes.download',[$note,'cdr']) }}"><i class="fas fa-file-archive"></i></a>@endif</div></td><td>@if(!in_array($note->status,['accepted','observed']))<form method="POST" action="{{ route('sunat.credit-notes.retry',$note) }}">@csrf<button class="btn btn-sm btn-outline-primary">Reintentar</button></form>@else—@endif</td></tr>
                @empty<tr><td colspan="7" class="text-center text-muted py-4">Todavía no se emitieron notas de crédito.</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/sunat_settings.js') }}?v={{ filemtime(public_path('js/sunat_settings.js')) }}"></script>
<script>document.getElementById('credit-note-sale')?.addEventListener('change',function(){document.getElementById('credit-note-form').action=this.value});</script>
<script>document.getElementById('cancel-boleta-sale')?.addEventListener('change',function(){document.getElementById('cancel-boleta-form').action=this.value});</script>
@endpush
