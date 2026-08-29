<?php
namespace App\Http\Controllers;


use App\Exports\VentasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use App\Models\Venta;
use App\Models\User; 
use Carbon\Carbon;
use App\Models\DetalleVenta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Factura;
use App\Models\Configuracion;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Movimiento;
use App\Models\PagoVenta;
use App\Models\Lote;
use App\Models\Caja;
use App\Services\SaleLineCalculator;
use App\Services\DocumentNumberService;
use App\Jobs\SendElectronicInvoice;
use App\Models\SunatSetting;
use App\Services\Tax\TaxProfileService;


class VentaController extends Controller
{
    // Mostrar la interfaz para registrar una nueva venta
    public function index()
    {
        // Configuración (IGV, empresa, etc.)
        $config = Configuracion::first();

        // Categorías activas y ordenadas
        $categorias = \App\Models\Categoria::orderBy('nombre', 'ASC')->get();

        // Productos visibles disponibles
        $productos = Producto::where('activo', true)
            ->where('visible_en_catalogo', true)
            ->orderBy('nombre', 'ASC')
            ->get();

        $taxProfile = app(TaxProfileService::class)->current();
        $taxCapabilities = $taxProfile?->capabilities->where('enabled', true)->pluck('capability')->all() ?? [];

        return view('ventas.index', compact('config', 'categorias', 'productos', 'taxProfile', 'taxCapabilities'));
    }


public function filtrarPorCategoria(Request $request)
{
    $productos = Producto::where('categoria_id', $request->id)
        ->where('activo', true)
        ->where('visible_en_catalogo', true)
        ->orderBy('nombre', 'ASC')
        ->get();

    return response()->json($productos);
}

public function registrarVenta(Request $request)
    {
        $request->validate([
            'tipo_comprobante' => 'required|in:boleta,factura,nota_venta',
            'cliente_id'       => 'nullable|integer',
            'cliente_modo'     => 'required|in:sin_documento,con_documento',
            'tipo_documento'   => 'nullable|required_if:cliente_modo,con_documento|in:dni,ruc',
            'documento'        => 'nullable|string|max:11',
            'informacion_adicional' => 'nullable|string|max:500',
            'fecha'            => 'required|date',
            'hora'             => 'required',
            'productos'        => 'required|array|min:1',
            'productos.*.producto_id' => 'required|integer|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.presentacion' => 'required|in:unidad,paquete,caja',

            'monto_pagado'     => 'required|numeric|min:0',
            'efectivo_recibido'=> 'nullable|numeric|min:0',
            'metodo_pago'      => 'nullable|string',
            'formato'          => 'nullable|in:a4,ticket,ticket_80,ticket_58',
            'credit_due_date'  => 'nullable|date|after_or_equal:fecha',
        ]);

        if (! Caja::where('usuario_id', auth()->id())->where('estado', 'abierta')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes abrir tu caja antes de registrar una venta.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            /* ================= CLIENTE ================= */
            $tipo = $request->tipo_comprobante;
            $sinDocumento = $request->cliente_modo === 'sin_documento';
            $documento = preg_replace('/\D+/', '', (string) $request->documento);

            if ($tipo === 'factura' && ($sinDocumento || $request->tipo_documento !== 'ruc' || strlen($documento) !== 11)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'type' => 'client_not_found',
                    'message' => 'La factura requiere seleccionar un cliente registrado con RUC de 11 dígitos.',
                ], 422);
            }

            if ($tipo === 'boleta' && ! $sinDocumento && $request->tipo_documento !== 'dni') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'type' => 'invalid_document_type',
                    'message' => 'La boleta permite identificar al cliente únicamente con DNI.',
                ], 422);
            }

            if ($sinDocumento && ! in_array($tipo, ['boleta', 'nota_venta'], true)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'type' => 'client_not_found',
                    'message' => 'Este comprobante requiere identificar al cliente.',
                ], 422);
            }

            $cliente = null;
            if (! $sinDocumento) {
                $longitudEsperada = $request->tipo_documento === 'ruc' ? 11 : 8;
                if (strlen($documento) !== $longitudEsperada) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'type' => 'client_not_found',
                        'message' => $request->tipo_documento === 'ruc'
                            ? 'Ingresa un RUC válido de 11 dígitos.'
                            : 'Ingresa un DNI válido de 8 dígitos.',
                    ], 422);
                }

                $cliente = $request->filled('cliente_id')
                    ? Cliente::find($request->integer('cliente_id'))
                    : null;

                if ($cliente && (string) $cliente->{$request->tipo_documento} !== $documento) {
                    $cliente = null;
                }

                if (! $cliente) {
                    $cliente = Cliente::where($request->tipo_documento, $documento)->first();
                }

                if (! $cliente) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'type' => 'client_not_found',
                        'message' => 'El cliente todavía no está registrado. Guárdalo antes de confirmar la venta.',
                    ], 422);
                }
            }

            /* ================= FECHA ================= */
            $hora = strlen($request->hora) === 5 ? $request->hora . ':00' : $request->hora;
            $fechaHora = Carbon::createFromFormat('Y-m-d H:i:s', "{$request->fecha} {$hora}");

            /* ================= SERIE ================= */
            $taxProfiles = app(TaxProfileService::class);
            $taxProfile = $taxProfiles->current();
            if ($tipo === 'factura' && $taxProfile && !$taxProfiles->has($taxProfile, 'issue_factura')) {
                throw new \Exception('El perfil tributario activo no permite emitir facturas.');
            }
            if ($tipo === 'boleta' && $taxProfile && !$taxProfiles->has($taxProfile, 'issue_boleta')) {
                throw new \Exception('El perfil tributario activo no permite emitir boletas.');
            }
            $numbering = app(DocumentNumberService::class);
            $serie = $numbering->seriesFor($tipo);
            $correlativo = $numbering->next($serie);

            /* ================= CONFIG ================= */
            $config = Configuracion::first();
            if (! $taxProfile) {
                throw new \Exception('No existe un perfil tributario activo. Configúralo antes de registrar nuevas ventas.');
            }
            $taxTreatment = $taxProfile->default_tax_treatment;
            $igvPercent = $taxTreatment === 'gravada' ? (float) $taxProfile->igv_rate : 0;

            /* ================= VENTA BASE ================= */
            $venta = Venta::create([
                'cliente_id'       => $cliente?->id,
                'usuario_id'       => auth()->id(),
                'tax_profile_id'   => $taxProfile?->id,
                'fecha'            => $fechaHora,
                'tipo_comprobante' => $tipo,
                'emission_system'  => $taxProfile?->emission_system,
                'tax_treatment'    => $taxTreatment,
                'igv_rate'         => $igvPercent,
                'serie'            => $serie,
                'correlativo'      => $correlativo,

                'metodo_pago'      => null,
                'estado'           => 'pendiente',

                'estado_sunat'     => 'pendiente',
                'op_gravadas'      => 0,
                'op_nrus'          => 0,
                'igv'              => 0,
                'total'            => 0,
                'saldo'            => 0,
                'activo'           => 1
            ]);

            /* ================= DETALLE + STOCK (POR LOTES) ================= */
            $operationBase = 0;
            \Log::info($request->productos);

            $calculator = app(SaleLineCalculator::class);

            foreach ($request->productos as $item) {

                $productoId = $item['producto_id'] ?? null;
                if (!$productoId) {
                    throw new \Exception("Producto inválido en el carrito.");
                }

                $producto = Producto::findOrFail($productoId);

                $cantidadPresentaciones = (int)($item['cantidad'] ?? 0);
                $presentacion = (string)($item['presentacion'] ?? 'unidad');

                if ($cantidadPresentaciones <= 0) {
                    throw new \Exception("Cantidad inválida para {$producto->nombre}");
                }

                // 🔒 Obtener lotes FEFO reales (bloqueados)
                $lotes = Lote::where('producto_id', $producto->id)
                    ->where('activo', 1)
                    ->where('stock_actual', '>', 0)
                    ->orderByRaw('fecha_vencimiento IS NULL')
                    ->orderBy('fecha_vencimiento', 'asc')
                    ->orderBy('fecha_ingreso', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $calculation = $calculator->calculate(
                    $producto,
                    $lotes,
                    $cantidadPresentaciones,
                    $presentacion
                );

                $unidadesAfectadas = $calculation['required_units'];
                $subtotal = $calculation['subtotal'];
                $operationBase += $subtotal;

                $detalle = DetalleVenta::create([
                    'venta_id'            => $venta->id,
                    'producto_id'         => $producto->id,
                    'presentacion'        => $presentacion,
                    'cantidad'            => $cantidadPresentaciones, // ✅ YA NO ES 1
                    'unidades_afectadas'  => $unidadesAfectadas,
                    'precio_presentacion' => $calculation['average_presentation_price'],
                    'precio_unitario'     => $calculation['average_unit_price'],
                    'subtotal'            => $subtotal,
                    'ganancia'            => $calculation['profit'],
                    'activo'              => 1
                ]);

                foreach ($calculation['allocations'] as $allocation) {
                    $lote = $allocation['lot'];
                    $lote->stock_actual -= $allocation['units'];
                    $lote->save();

                    DB::table('detalle_lote_ventas')->insert([
                        'detalle_venta_id' => $detalle->id,
                        'lote_id'          => $lote->id,
                        'cantidad'         => $allocation['units'],
                        'precio_lote'      => $allocation['presentation_price'],
                        'fecha_vencimiento'=> $lote->fecha_vencimiento,
                        'created_at'       => now(),
                        'updated_at'       => now(),
                    ]);
                }
            }

            /* ================= IGV + TOTAL ================= */
            $operationBase = round($operationBase, 2);
            $opGravadas = $taxTreatment === 'gravada' ? $operationBase : 0;
            $opExoneradas = $taxTreatment === 'exonerada' ? $operationBase : 0;
            $opInafectas = $taxTreatment === 'inafecta' ? $operationBase : 0;
            $opNrus = $taxTreatment === 'nrus_no_desglosado' ? $operationBase : 0;
            $igvMonto = round($opGravadas * ($igvPercent / 100), 2);
            $total    = round($operationBase + $igvMonto, 2);

            /* ================= PAGO / ESTADO ================= */
            $montoPagado = round((float) $request->monto_pagado, 2);

            if ($request->metodo_pago === 'efectivo' && $request->filled('efectivo_recibido')) {
                $montoPagado = round((float) $request->efectivo_recibido, 2);
            }

            if ($montoPagado > 0 && empty($request->metodo_pago)) {
                throw new \Exception("Debe seleccionar un método de pago.");
            }

            $esPagoEfectivo = $request->metodo_pago === 'efectivo' && $montoPagado > 0;
            $efectivoRecibido = $esPagoEfectivo
                ? round((float) ($request->efectivo_recibido ?? $montoPagado), 2)
                : null;
            $vuelto = $esPagoEfectivo
                ? max(0, round($efectivoRecibido - $total, 2))
                : null;

            if ($montoPagado > $total) {
                $montoPagado = $total;
            }

            if ($montoPagado <= 0) {
                $estado = 'pendiente';
                $saldo  = $total;
                $metodoPagoVenta = null;
            } elseif ($montoPagado < $total) {
                $estado = 'credito';
                $saldo  = round($total - $montoPagado, 2);
                $metodoPagoVenta = $request->metodo_pago;
            } else {
                $estado = 'pagado';
                $saldo  = 0;
                $metodoPagoVenta = $request->metodo_pago;
            }

            if (in_array($estado, ['credito', 'pendiente'], true) && ! $request->filled('credit_due_date')) {
                throw new \Exception('Una venta al crédito requiere la fecha de vencimiento de la cuota.');
            }

            $venta->update([
                'op_gravadas' => $opGravadas,
                'op_exoneradas' => $opExoneradas,
                'op_inafectas' => $opInafectas,
                'op_nrus' => $opNrus,
                'igv'         => $igvMonto,
                'total'       => $total,
                'saldo'       => $saldo,
                'efectivo_recibido' => $efectivoRecibido,
                'vuelto'      => $vuelto,
                'estado'      => $estado,
                'metodo_pago' => $metodoPagoVenta,
                'credit_due_date' => in_array($estado, ['credito', 'pendiente'], true) ? $request->credit_due_date : null,
                'informacion_adicional' => $request->filled('informacion_adicional')
                    ? trim((string) $request->informacion_adicional)
                    : null,
            ]);

            if ($montoPagado > 0) {
                PagoVenta::create([
                    'venta_id'    => $venta->id,
                    'usuario_id'  => auth()->id(),
                    'monto'       => $montoPagado,
                    'metodo_pago' => $request->metodo_pago,
                ]);
            }

            // ============================== GENERAR PDF ==============================
            $formato = $request->input('formato', 'a4');
            $vista = match ($formato) {
                'ticket', 'ticket_80', 'ticket_58' => "comprobantes.{$tipo}_ticket",
                default  => "comprobantes.{$tipo}_a4",
            };

            if (!view()->exists($vista)) {
                throw new \Exception("La vista [$vista] no existe.");
            }

            $venta->load(['cliente', 'usuario', 'detalleVentas.producto']);

            // LOGO
            $logoBase64 = null;
            if ($config && $config->logo && file_exists(public_path($config->logo))) {
                $path = public_path($config->logo);
                $logoBase64 = 'data:image/' . pathinfo($path, PATHINFO_EXTENSION) .
                    ';base64,' . base64_encode(file_get_contents($path));
            }
//........................
            // QR (evita usar $venta->hash si no existe)
            //$qrData = "{$config->ruc}|{$tipo}|{$serie}|{$correlativo}|{$venta->total}|{$venta->igv}|{$venta->fecha->format('d/m/Y')}";
            //$qr = base64_encode(\QrCode::format('png')->size(120)->generate($qrData));

            //$pdf = \PDF::setOptions([
                //'isRemoteEnabled'   => true,
               // 'dpi'               => 96,
               // 'defaultMediaType'  => 'screen',
           // ])->loadView($vista, [
                //'venta' => $venta,
                //'config' => $config,
                //'qr' => $qr,
                //'logoBase64' => $logoBase64,
                //'subtotal' => $venta->op_gravadas,
               // 'igv' => $venta->igv,
               // 'total' => $venta->total,
            //]);
//.........................
// QR en SVG: no depende de Imagick
$sunatSetting = SunatSetting::current();
$tipoSunat = $tipo === 'factura' ? '01' : ($tipo === 'boleta' ? '03' : '00');
$documentoCliente = $cliente?->ruc ?: ($cliente?->dni ?: '');
$tipoDocumentoCliente = $cliente?->ruc ? '6' : ($cliente?->dni ? '1' : '0');
$qrData = implode('|', [
    $sunatSetting->fiscal_ruc ?: ($config->ruc ?? ''),
    $tipoSunat,
    $serie,
    $correlativo,
    number_format($venta->igv, 2, '.', ''),
    number_format($venta->total, 2, '.', ''),
    $venta->fecha->format('Y-m-d'),
    $tipoDocumentoCliente,
    $documentoCliente,
]);

$qr = base64_encode(
    QrCode::format('svg')
        ->size(120)
        ->generate($qrData)
);

$pdf = Pdf::setOptions([
    'isRemoteEnabled'  => true,
    'dpi'              => 96,
    'defaultMediaType' => 'screen',
])->loadView($vista, [
    'venta'      => $venta,
    'config'     => $config,
    'qr'         => $qr,
    'logoBase64' => $logoBase64,
    'subtotal'   => $venta->op_gravadas + $venta->op_exoneradas + $venta->op_inafectas + $venta->op_nrus,
    'igv'        => $venta->igv,
    'total'      => $venta->total,
    'ticketWidth'=> $formato === 'ticket_58' ? 58 : 80,
]);


            if (in_array($formato, ['ticket', 'ticket_80', 'ticket_58'], true)) {
                $ticketWidth = $formato === 'ticket_58' ? 58 : 80;
                $paperWidth = $ticketWidth === 58 ? 164.41 : 226.77;
                $lineHeight = $ticketWidth === 58 ? 26 : 28;
                $baseHeight = $ticketWidth === 58 ? 377 : 426;
                $additionalLines = $venta->informacion_adicional
                    ? max(1, (int) ceil(mb_strlen($venta->informacion_adicional) / ($ticketWidth === 58 ? 34 : 48)))
                    : 0;
                $alto = $baseHeight
                    + count($venta->detalleVentas) * $lineHeight
                    + $additionalLines * ($ticketWidth === 58 ? 10 : 9)
                    + ($additionalLines > 0 ? 10 : 0)
                    + ($venta->efectivo_recibido !== null ? ($ticketWidth === 58 ? 16 : 18) : 0);
                $pdf->setPaper([0, 0, $paperWidth, $alto]);
            } else {
                $pdf->setPaper('A4');
            }

            $nombreArchivo = "{$serie}-" . str_pad($correlativo, 6, '0', STR_PAD_LEFT) . ".pdf";
            $ruta = public_path("comprobantes");
            if (!is_dir($ruta)) mkdir($ruta, 0775, true);

            $pdf->save("$ruta/$nombreArchivo");
            $pdfUrl = asset("comprobantes/$nombreArchivo");
            $venta->pdf_url = $pdfUrl;
            $venta->save();

            /* ================= MOVIMIENTOS ================= */
            if ($estado === 'pagado') {
                Movimiento::create([
                    'fecha' => $fechaHora->toDateString(),
                    'tipo'  => 'ingreso',
                    'subtipo' => 'venta',
                    'concepto' => "Venta {$tipo} {$serie}-" . str_pad($correlativo, 6, '0', STR_PAD_LEFT),
                    'monto' => $total,
                    'metodo_pago' => $metodoPagoVenta,
                    'estado' => 'pagado',
                    'referencia_id' => $venta->id,
                    'referencia_tipo' => 'venta',
                ]);
            } elseif ($estado === 'pendiente') {
                Movimiento::create([
                    'fecha' => $fechaHora->toDateString(),
                    'tipo' => 'ingreso',
                    'subtipo' => 'venta',
                    'concepto' => "Venta pendiente {$tipo} {$serie}-" . str_pad($correlativo, 6, '0', STR_PAD_LEFT),
                    'monto' => $total,
                    'metodo_pago' => 'fiado',
                    'estado' => 'pendiente',
                    'referencia_id' => $venta->id,
                    'referencia_tipo' => 'venta',
                ]);
            } elseif ($estado === 'credito') {

                if ($montoPagado > 0) {
                    Movimiento::create([
                        'fecha' => $fechaHora->toDateString(),
                        'tipo' => 'ingreso',
                        'subtipo' => 'venta',
                        'concepto' => "Adelanto venta {$tipo} {$serie}-" . str_pad($correlativo, 6, '0', STR_PAD_LEFT),
                        'monto' => $montoPagado,
                        'metodo_pago' => $metodoPagoVenta,
                        'estado' => 'pagado',
                        'referencia_id' => $venta->id,
                        'referencia_tipo' => 'venta',
                    ]);
                }

                Movimiento::create([
                    'fecha' => $fechaHora->toDateString(),
                    'tipo' => 'ingreso',
                    'subtipo' => 'venta',
                    'concepto' => "Saldo venta {$tipo} {$serie}-" . str_pad($correlativo, 6, '0', STR_PAD_LEFT),
                    'monto' => $saldo,
                    'metodo_pago' => 'credito',
                    'estado' => 'pendiente',
                    'referencia_id' => $venta->id,
                    'referencia_tipo' => 'venta',
                ]);
            }

            DB::commit();

            if (
                in_array($tipo, ['factura', 'boleta'], true)
                && in_array($estado, ['pagado', 'credito', 'pendiente'], true)
                && (!$taxProfile || $taxProfiles->has($taxProfile, 'automatic_submission'))
                && SunatSetting::current()->enabled
            ) {
                if ($tipo === 'factura') {
                    SendElectronicInvoice::dispatchAfterResponse($venta->id);
                }
            }

            return response()->json([
                'success'        => true,
                'message'        => 'Venta registrada correctamente.',
                'serie'          => $serie,
                'correlativo'    => str_pad($correlativo, 6, '0', STR_PAD_LEFT),
                'pdf_url'        => $pdfUrl,
                'nombre_archivo' => $nombreArchivo,
                'estado'         => $estado,
                'saldo'          => $saldo,
                'monto_pagado'   => $montoPagado,
                'efectivo_recibido' => $efectivoRecibido,
                'vuelto'         => $vuelto ?? 0,
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            $msg = $e->getMessage();

            // 🚨 Manejo especial para errores de stock
           if (strpos($msg, "STOCK|") === 0) {

                $partes = explode("|", $msg);

                return response()->json([
                    'success' => false,
                    'type' => 'stock',
                    'producto_id' => (int)($partes[1] ?? 0),
                    'producto_nombre' => $partes[2] ?? '',
                    'disponible' => (int)($partes[3] ?? 0),
                    'solicitado' => (int)($partes[4] ?? 0),
                    'lote' => $partes[5] ?? null,
                    'message' => "Stock insuficiente"
                ], 422);
            }


            \Log::error('Error registrarVenta', [
                'exception' => get_class($e),
                'message' => $msg,
                'usuario_id' => auth()->id(),
            ]);

            if ($e instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'type' => 'product_not_found',
                    'message' => 'Uno de los productos del carrito ya no está disponible. Actualiza el listado y vuelve a agregarlo.',
                ], 422);
            }

            $businessMessages = [
                'El perfil tributario activo no permite emitir facturas.',
                'El perfil tributario activo no permite emitir boletas.',
                'No existe un perfil tributario activo. Configúralo antes de registrar nuevas ventas.',
                'Debe seleccionar un método de pago.',
                'Una venta al crédito requiere la fecha de vencimiento de la cuota.',
            ];

            if (
                in_array($msg, $businessMessages, true)
                || str_starts_with($msg, 'Cantidad inválida para ')
                || str_starts_with($msg, 'No hay precio público de ')
                || str_starts_with($msg, 'La presentación ')
            ) {
                return response()->json([
                    'success' => false,
                    'type' => 'business_rule',
                    'message' => $msg,
                ], 422);
            }

            $publicMessage = $e instanceof QueryException
                ? 'No se pudo guardar la venta en este momento. No se realizó ningún cobro ni movimiento de stock; inténtalo nuevamente.'
                : 'Ocurrió un problema al procesar la venta. No se guardaron cambios; inténtalo nuevamente o comunícate con el administrador.';

            return response()->json([
                'success' => false,
                'type' => 'server_error',
                'message' => $publicMessage,
            ], 500);
        }
    }


// VentaController.php
public function detalle($id)
{
    $venta = Venta::with([
        'usuario',
        'cliente',
        'detalleVentas.producto',
        'manualTaxDocument',
        'taxProfile.capabilities',
    ])->findOrFail($id);

    $taxProfiles = app(TaxProfileService::class);
    $esBoletaSol = $venta->tipo_comprobante === 'boleta'
        && $venta->emission_system === 'see_sol'
        && $taxProfiles->has($venta->taxProfile, 'manual_sunat_link');
    $saldo = max(0, (float) $venta->saldo);
    $montoPagado = max(0, round((float) $venta->total - $saldo, 2));
    $esVentaCredito = in_array($venta->estado, ['pendiente', 'credito'], true);
    $vencimiento = $venta->credit_due_date;

    return response()->json([
        'id' => $venta->id,

        // === Comprobante (para que salga F001-000001) ===
        'tipo_comprobante' => $venta->tipo_comprobante,               // "factura" | "boleta"
        'tipo'             => ucfirst($venta->tipo_comprobante),      // texto superior (compat)
        'serie'            => $venta->serie,                          // "F001"
        'numero'           => str_pad($venta->correlativo, 6, '0', STR_PAD_LEFT), // "000001"

        // === Estados ===
        'estado'       => $venta->estado,        // pagado | pendiente | credito
        'estado_sunat' => $venta->estado_sunat,  // aceptado | enviado | rechazado | pendiente | etc

        // === Totales FE ===
        'subtotal' => (float) ($venta->op_gravadas + $venta->op_exoneradas + $venta->op_inafectas + $venta->op_nrus),
        'igv'      => (float) $venta->igv,
        'total'    => (float) $venta->total,
        'saldo'    => $saldo,
        'monto_pagado' => $montoPagado,
        'efectivo_recibido' => $venta->efectivo_recibido !== null ? (float) $venta->efectivo_recibido : null,
        'vuelto' => $venta->vuelto !== null ? (float) $venta->vuelto : null,
        'condicion_pago' => match ($venta->estado) {
            'pendiente' => 'Fiado sin adelanto',
            'credito' => 'Crédito con adelanto',
            default => 'Contado',
        },
        'fecha_vencimiento' => $esVentaCredito && $vencimiento
            ? $vencimiento->format('d/m/Y')
            : null,
        'credito_vencido' => $esVentaCredito && $vencimiento
            ? $vencimiento->copy()->endOfDay()->isPast()
            : false,

        // === Fecha ===
        'fecha_formato' => ucfirst(optional($venta->fecha)->locale('es')->translatedFormat('h:i A | d F Y')),

        // === Método pago ===
        'metodo_pago' => $venta->metodo_pago,

        // === Cliente (sigues enviándolo como string para tu JS actual) ===
        'cliente' => optional($venta->cliente)->razon_social
            ?? optional($venta->cliente)->nombre
            ?? optional($venta->cliente)->nombres
            ?? '—',

        // (opcional) si quieres mostrar RUC/DNI luego:
        'cliente_doc' => optional($venta->cliente)->ruc
            ?? optional($venta->cliente)->dni
            ?? null,

        'vendedor' => $venta->usuario->nombre ?? '—',

        // === Ganancia ===
        'ganancia' => (float) $venta->detalleVentas->sum('ganancia'),

        // === Archivos FE ===
        'pdf_url' => $venta->pdf_url ?? null,
        'xml_url' => $venta->xml_url ?? null,
        'cdr_url' => $venta->cdr_url ?? null,

        // === Vinculación manual con la boleta oficial emitida en SEE-SOL ===
        'sunat_sol' => [
            'aplica' => $esBoletaSol,
            'puede_vincular' => $esBoletaSol
                && !$venta->manualTaxDocument
                && auth()->user()->esAdmin(),
            'link_url' => $esBoletaSol && !$venta->manualTaxDocument
                ? route('sunat.sol.link', $venta)
                : null,
            'documento' => $venta->manualTaxDocument ? [
                'serie' => $venta->manualTaxDocument->series,
                'numero' => str_pad((string) $venta->manualTaxDocument->number, 8, '0', STR_PAD_LEFT),
                'fecha' => optional($venta->manualTaxDocument->issued_at)->format('d/m/Y H:i'),
                'total' => (float) $venta->manualTaxDocument->total,
                'estado' => $venta->manualTaxDocument->status,
            ] : null,
        ],

        // === Productos ===
        'productos' => $venta->detalleVentas->map(function ($d) {
            return [
                'nombre' => $d->producto->nombre,
                'descripcion' => $d->producto->descripcion,
                'imagen' => $d->producto->imagen
                    ? asset('uploads/productos/' . $d->producto->imagen)
                    : asset('img/sin-imagen.png'),
                'cantidad_txt' => "{$d->cantidad} {$d->presentacion}",
                'subtotal' => (float) $d->subtotal,
            ];
        })->values(),
    ]);
}
public function pagarCredito(Request $request, Venta $venta)
{
    $request->validate([
        'monto_pagado' => 'required|numeric|min:0.01',
        'metodo_pago'  => 'required|string',
    ]);

    if (! Caja::where('usuario_id', auth()->id())->where('estado', 'abierta')->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Debes abrir tu caja antes de registrar el cobro.',
        ], 422);
    }

    if ($venta->estado !== 'credito') {
        return response()->json([
            'success' => false,
            'message' => 'La venta no está en crédito'
        ], 400);
    }

    $monto = round($request->monto_pagado, 2);

    // 🔥 Si paga menos → NO permitido
    if ($monto < $venta->saldo) {
        return response()->json([
            'success' => false,
            'message' => 'Debe pagar al menos el saldo pendiente'
        ], 400);
    }

    // 🔥 Si paga más → se ajusta (vuelto solo visual)
    if ($monto > $venta->saldo) {
        $monto = $venta->saldo;
    }

    DB::beginTransaction();

    try {
        // Registrar pago
        PagoVenta::create([
            'venta_id'    => $venta->id,
            'usuario_id'  => auth()->id(),
            'monto'       => $monto,
            'metodo_pago' => $request->metodo_pago,
        ]);

        // Actualizar saldo
        $nuevoSaldo = round($venta->saldo - $monto, 2);

        $venta->update([
            'saldo'  => $nuevoSaldo,
            'estado' => $nuevoSaldo <= 0 ? 'pagado' : 'credito',
        ]);

        // Movimiento de ingreso
        Movimiento::create([
            'fecha' => now()->toDateString(),
            'tipo'  => 'ingreso',
            'subtipo' => 'cobro_credito',
            'concepto' => "Cobro crédito venta {$venta->serie}-" . str_pad($venta->correlativo, 6, '0', STR_PAD_LEFT),
            'monto' => $monto,
            'metodo_pago' => $request->metodo_pago,
            'estado' => 'pagado',
            'referencia_id' => $venta->id,
            'referencia_tipo' => 'venta',
        ]);
        Movimiento::where('referencia_id', $venta->id)
        ->where('subtipo', 'venta')
        ->where('estado', 'pendiente')
        ->where('metodo_pago', 'credito')
        ->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado correctamente',
            'saldo'   => $nuevoSaldo,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function cerrarPendiente(Request $request, Venta $venta)
{
    $request->validate([
        'monto_pagado' => 'required|numeric|min:0.01',
        'metodo_pago'  => 'required|string',
    ]);

    if (! Caja::where('usuario_id', auth()->id())->where('estado', 'abierta')->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Debes abrir tu caja antes de cobrar esta venta fiada.',
        ], 422);
    }

    // 🔴 USAR ESTADO REAL
    if ($venta->estado !== 'pendiente') {
        return response()->json([
            'success' => false,
            'message' => 'La venta no está pendiente'
        ], 400);
    }

    $total = (float) $venta->total;
    $monto = (float) $request->monto_pagado;

    if ($monto < $total) {
        return response()->json([
            'success' => false,
            'message' => 'El monto recibido no puede ser menor al total'
        ], 400);
    }

    DB::transaction(function () use ($venta, $request, $total) {

        // 1️⃣ Registrar pago
        PagoVenta::create([
            'venta_id'    => $venta->id,
            'usuario_id'  => auth()->id(),
            'monto'       => $total,
            'metodo_pago' => $request->metodo_pago,
        ]);

        // 2️⃣ Cerrar venta (CAMPO CORRECTO)
        $venta->update([
            'estado'      => 'pagado',
            'saldo'       => 0,
            'metodo_pago' => $request->metodo_pago,
        ]);

        // El pendiente pertenece al momento en que se originó la deuda.
        // Se anula y el pago genera un ingreso nuevo en la caja actualmente abierta.
        Movimiento::where('referencia_tipo', 'venta')
            ->where('referencia_id', $venta->id)
            ->where('estado', 'pendiente')
            ->update([
                'estado' => 'anulado',
            ]);

        Movimiento::create([
            'fecha' => now()->toDateString(),
            'hora' => now()->toTimeString(),
            'tipo' => 'ingreso',
            'subtipo' => 'cobro_fiado',
            'concepto' => "Cobro fiado venta {$venta->serie}-" . str_pad($venta->correlativo, 6, '0', STR_PAD_LEFT),
            'monto' => $total,
            'metodo_pago' => $request->metodo_pago,
            'estado' => 'pagado',
            'referencia_id' => $venta->id,
            'referencia_tipo' => 'venta',
        ]);
    });

    return response()->json([
        'success' => true,
        'vuelto'  => round($monto - $total, 2),
    ]);
}



public function obtenerSerieCorrelativo(Request $request)
{
    $tipo = $request->query('tipo');

    $serie = match ($tipo) {
        'boleta' => 'B001',
        'factura' => 'F001',
        'nota_venta' => 'NV01',
        default => 'ND00',
    };

    $ultimoCorrelativo = DB::table('ventas')
        ->where('tipo_comprobante', $tipo)
        ->where('serie', $serie)
        ->max('correlativo');

    $nuevoCorrelativo = $ultimoCorrelativo ? $ultimoCorrelativo + 1 : 1;
    $correlativoFormateado = str_pad($nuevoCorrelativo, 6, '0', STR_PAD_LEFT);

    return response()->json([
        'serie' => $serie,
        'correlativo' => $correlativoFormateado,
    ]);
}

public function show($id)
{
    $venta = Venta::with(['cliente', 'detalleVentas.producto'])->findOrFail($id);

    // ================= SALDO SEGURO =================
    $saldo = $venta->estado === 'credito'
        ? (float) ($venta->saldo ?? 0)
        : 0;

    return response()->json([
        'id'            => $venta->id,
        'cliente'       => $venta->cliente->nombre ?? '—',
        'tipo'          => $venta->tipo_comprobante,
        'serie'         => $venta->serie,
        'correlativo'   => $venta->correlativo,
        'estado'        => $venta->estado,
        'total'         => (float) $venta->total,
        'saldo'         => $saldo, // 🔥 CLAVE
        'metodo_pago'   => $venta->metodo_pago
                                ? ucfirst($venta->metodo_pago)
                                : null,
        'fecha_formato' => $venta->fecha
                                ? Carbon::parse($venta->fecha)->format('h:i A | d F Y')
                                : '—',
        'ganancia'      => (float) $venta->detalleVentas->sum('ganancia'),

        'productos' => $venta->detalleVentas->map(function ($item) {

            $cantidadTxt = match ($item->presentacion) {
                'caja'    => $item->cantidad . ' caja x' . $item->unidades_afectadas,
                'paquete' => $item->cantidad . ' paquete x' . $item->unidades_afectadas,
                default   => $item->cantidad . ' unidad'
            };

            return [
                'nombre'        => $item->producto->nombre,
                'descripcion'   => $item->producto->descripcion ?? '',
                'imagen'        => $item->producto->imagen
                    ? asset('uploads/productos/' . basename($item->producto->imagen))
                    : asset('images/producto-default.png'),
                'cantidad_txt'  => $cantidadTxt,
                'subtotal'      => (float) $item->subtotal,
            ];
        }),
    ]);
}



public function stockFifo($productoId)
{
        $producto = Producto::findOrFail($productoId);
        $lotes = Lote::where('producto_id', $productoId)
        ->where('activo', 1)
        ->where('stock_actual', '>', 0)
        ->orderByRaw('fecha_vencimiento IS NULL') // null al final
        ->orderBy('fecha_vencimiento', 'asc')     // FEFO real
        ->orderBy('fecha_ingreso', 'asc')
        ->orderBy('id', 'asc')
        ->get();

    return response()->json(
        $lotes->map(fn($l) => [
            'id' => $l->id,
            'numero' => $l->numero_lote,
            'numero_lote' => $l->numero_lote,
            'stock' => (int) $l->stock_actual,     // 👈 OJO: stock en UNIDADES
            'precio_unidad' => (float) $producto->precio_venta,
            'precio_paquete' => (float) $producto->precio_paquete,
            'precio_caja' => (float) $producto->precio_caja,
            'fecha_vencimiento' => $l->fecha_vencimiento,
            'fecha_ingreso' => $l->fecha_ingreso,
        ])
    );
}
public function autorizar(Request $request)
{
    $usuario = $request->input('usuario');
    $clave = $request->input('clave');

    $user = User::where('usuario', $usuario)
                ->with('rol') // Cargar relación
                ->first();

    if ($user) {
        \Log::info('Usuario encontrado: ' . $user->nombre);
        \Log::info('Rol del usuario: ' . ($user->rol->nombre ?? 'No definido'));
        \Log::info('Clave hash en BD: ' . $user->clave);

        // ⚠️ Verificar si el rol no es ADMINISTRADOR (por rol_id)
        if ($user->rol_id != 1) {
            \Log::warning('⛔ Usuario no autorizado. No es administrador.');
            return response()->json([
                'success' => false,
                'message' => 'USUARIO NO AUTORIZADO (NO TIENES PERMISO DE ADMINISTRADOR PARA EDITAR ESTA VENTA)'
            ], 401);
        }

        // Verificar contraseña
        if (Hash::check($clave, $user->clave)) {
            \Log::info('✅ Clave correcta');
            return response()->json(['success' => true]);
        } else {
            \Log::warning('❌ Clave incorrecta');
        }
    } else {
        \Log::warning('❌ Usuario no encontrado');
    }

    return response()->json(['success' => false], 401);
}

public function descargarComprobante($filename)
{
    $path = public_path("comprobantes/{$filename}");

    if (!file_exists($path)) {
        abort(404, 'Archivo no encontrado');
    }

    return response()->download($path, $filename, [
        'Content-Type' => 'application/pdf'
    ]);
}


public function imprimirFactura($id)
{
    $venta = Venta::with('cliente', 'detalleVentas.producto')->findOrFail($id);
    $config = Configuracion::first();

    // Texto para el QR (puede incluir RUC, serie, correlativo, total, etc.)
    $textoQR = "{$config->ruc}|{$venta->serie}-{$venta->correlativo}|{$venta->total}|{$venta->fecha->format('d/m/Y')}";

    // Generamos QR como imagen en Base64
    $qr = base64_encode(QrCode::format('png')->size(120)->generate($textoQR));

    return view('factura', compact('venta', 'config', 'qr'));
}

}
