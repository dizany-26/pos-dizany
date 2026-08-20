<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Consultar comprobante | DIZANY</title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:Inter,Arial,sans-serif;background:radial-gradient(circle at 90% 5%,#194a7a 0 12%,transparent 13%),linear-gradient(135deg,#071a36,#0b3158);color:#12213a;padding:32px 16px}.card{max-width:900px;margin:auto;background:#fff;border-radius:22px;padding:30px;box-shadow:0 24px 65px #0005;border-top:4px solid #1d7cf2}h1{margin:0 0 8px}.sub{color:#64748b;margin-bottom:24px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.field label{display:block;font-weight:700;font-size:13px;margin-bottom:7px}.field input,.field select{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:10px;font-size:15px}button,.btn{background:#1677ed;color:white;border:0;border-radius:10px;padding:13px 18px;font-weight:700;text-decoration:none;display:inline-flex;gap:8px;margin-top:18px;cursor:pointer}.result{margin-top:22px;padding:18px;border-radius:14px;background:#ecfdf5;border:1px solid #86efac}.error{background:#fff1f2;border-color:#fda4af}.actions{display:flex;gap:10px;flex-wrap:wrap}.errors{color:#b91c1c;margin:10px 0}@media(max-width:700px){.grid{grid-template-columns:1fr}.card{padding:22px 18px}}
</style></head><body><main class="card"><h1>Consulta tu comprobante</h1><p class="sub">Valida los datos exactos del documento para acceder de forma segura a su representación y XML.</p>
@if($errors->any())<div class="errors">Revisa los datos ingresados.</div>@endif
<form method="post" action="{{ route('sunat.public.search') }}">@csrf<div class="grid">
<div class="field"><label>Tipo</label><select name="document_type"><option value="03">Boleta</option><option value="01">Factura</option></select></div>
<div class="field"><label>Serie</label><input name="series" required value="{{ old('series') }}" placeholder="B001"></div>
<div class="field"><label>Número</label><input name="number" type="number" min="1" required value="{{ old('number') }}"></div>
<div class="field"><label>Fecha de emisión</label><input name="issue_date" type="date" required value="{{ old('issue_date') }}"></div>
<div class="field"><label>Total</label><input name="total" type="number" step="0.01" min="0" required value="{{ old('total') }}"></div>
<div class="field"><label>Documento del cliente</label><input name="customer_document" required value="{{ old('customer_document') }}"></div>
</div><button type="submit">Consultar comprobante</button></form>
@isset($searched)@if($document)<section class="result"><strong>Comprobante válido</strong><p>{{ $document->series }}-{{ str_pad($document->number,8,'0',STR_PAD_LEFT) }} · Estado: {{ $document->status }}</p><div class="actions">
<a class="btn" href="{{ URL::temporarySignedRoute('sunat.public.download',now()->addMinutes(10),['document'=>$document,'kind'=>'pdf']) }}">Descargar PDF</a>
<a class="btn" href="{{ URL::temporarySignedRoute('sunat.public.download',now()->addMinutes(10),['document'=>$document,'kind'=>'xml']) }}">Descargar XML</a></div></section>
@else<section class="result error"><strong>No se encontró una coincidencia.</strong><p>Comprueba serie, número, fecha, importe y documento del cliente.</p></section>@endif @endisset
</main></body></html>
