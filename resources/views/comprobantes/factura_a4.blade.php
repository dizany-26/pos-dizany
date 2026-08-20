<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Electrónica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; padding-bottom: 10px; border-bottom: 2px solid #000; }
        .logo img { max-height: 80px; margin-bottom: 5px; }
        .empresa { font-size: 18px; font-weight: bold; margin-top: 5px; }
        .datos-empresa { font-size: 12px; margin-top: 3px; }
        .comprobante { font-size: 16px; font-weight: bold; margin-top: 8px; color: #007BFF; }
        .datos-cliente, .tabla-productos { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .tabla-productos th, .tabla-productos td { border: 1px solid #ddd; padding: 6px; text-align: center; font-size: 12px; }
        .tabla-productos th { background-color: #f8f8f8; }
        .totales { width: 100%; margin-top: 10px; font-size: 13px; }
        .totales td { text-align: right; padding: 4px; }
        .footer { text-align: center; margin-top: 15px; font-size: 11px; color: #555; }
        .icon { margin-right: 5px; }
    </style>
</head>
<body>
    <div class="header">
        @if($logoBase64)
            <div class="logo">
                <img src="{{ $logoBase64 }}" alt="Logo">
            </div>
        @endif
        <div class="empresa">{{ $config->nombre_empresa ?? 'Mi Empresa' }}</div>
        <div class="datos-empresa">
            <div><i class="fas fa-id-card icon"></i>RUC: {{ $config->ruc ?? '00000000000' }}</div>
            <div><i class="fas fa-map-marker-alt icon"></i>{{ $config->direccion ?? 'Dirección no registrada' }}</div>
            <div><i class="fas fa-phone icon"></i>{{ $config->telefono ?? '-' }} | <i class="fas fa-envelope icon"></i>{{ $config->correo ?? 'correo@empresa.com' }}</div>
        </div>
        <div class="comprobante">FACTURA ELECTRÓNICA</div>
        <div>{{ $venta->serie }}-{{ str_pad($venta->correlativo, 6, '0', STR_PAD_LEFT) }}</div>
    </div>

    <table class="datos-cliente">
        <tr>
            <td><strong>Cliente:</strong> {{ $venta->cliente->nombre ?? 'Consumidor Final' }}</td>
            <td><strong>Documento:</strong> {{ $venta->cliente->dni ?? $venta->cliente->ruc }}</td>
        </tr>
        <tr>
            <td><strong>Fecha:</strong> {{ $venta->fecha->format('d/m/Y') }}</td>
            <td><strong>Método de Pago:</strong> {{ ucfirst($venta->metodo_pago) }}</td>
        </tr>
    </table>

    <table class="tabla-productos">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>P. Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detalleVentas as $i => $detalle)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $detalle->producto->nombre }}</td>
                    <td>{{ $detalle->cantidad }} {{ ucfirst($detalle->presentacion) }}</td>
                    <td>{{ $config->moneda }} {{ number_format($detalle->precio_presentacion, 2) }}</td>
                    <td>{{ $config->moneda }} {{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales" style="margin-top:10px; width:100%;">
        <tr>
            <td style="text-align:right;"><strong>Op. Gravadas:</strong></td>
            <td style="text-align:right;">S/ {{ number_format($subtotal, 2) }}</td>
        </tr>
        <tr>
            <td style="text-align:right;"><strong>IGV ({{ number_format($venta->igv_rate, 2) }}%):</strong></td>
            <td style="text-align:right;">S/ {{ number_format($igv, 2) }}</td>
        </tr>
        <tr>
            <td style="text-align:right;"><strong>Total:</strong></td>
            <td style="text-align:right;">S/ {{ number_format($total, 2) }}</td>
        </tr>
    </table>


    <div class="qr">
        <img src="data:image/png;base64,{{ $qr }}" alt="Código QR">
    </div>

    <div class="footer">
        <i class="fas fa-check-circle"></i> ¡Gracias por su compra!<br>
        <small>{{ $config->nombre_empresa }}</small>
    </div>
</body>
</html>

