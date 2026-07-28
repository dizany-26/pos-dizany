<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Venta Ticket</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            width: 80mm;
            margin: 0;
            padding: 5px;
        }
        .header { text-align: center; margin-bottom: 5px; }
        .logo img { max-height: 50px; margin-bottom: 3px; }
        .empresa { font-size: 12px; font-weight: bold; }
        .datos-empresa { font-size: 9px; line-height: 1.2; }
        .comprobante { font-size: 11px; font-weight: bold; margin-top: 3px; }
        .line { border-top: 1px dashed #000; margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; }
        .datos-cliente td { font-size: 9px; }
        .tabla-productos th, .tabla-productos td {
            border-bottom: 1px dashed #000;
            padding: 2px;
            text-align: left;
            font-size: 9px;
        }
        .totales td { text-align: right; font-size: 9px; }
        .qr { text-align: center; margin-top: 5px; }
        .footer { text-align: center; margin-top: 5px; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        @if($logoBase64)
            <div class="logo">
                <img src="{{ $logoBase64 }}" alt="Logo">
            </div>
        @endif
        <div class="empresa">{{ $config->nombre_empresa }}</div>
        <div class="datos-empresa">
            <div>RUC: {{ $config->ruc }}</div>
            <div>{{ $config->direccion }}</div>
            <div>Tel: {{ $config->telefono }}</div>
        </div>
        <div class="comprobante">NOTA DE VENTA</div>
        <div>{{ $venta->serie }}-{{ str_pad($venta->correlativo, 6, '0', STR_PAD_LEFT) }}</div>
        <div class="line"></div>
    </div>

    <table class="datos-cliente">
        <tr>
            <td><strong>Cliente:</strong> {{ $venta->cliente->nombre }}</td>
        </tr>
        <tr>
            <td><strong>DNI/RUC:</strong> {{ $venta->cliente->dni ?? $venta->cliente->ruc }}</td>
        </tr>
        <tr>
            <td><strong>Fecha:</strong> {{ $venta->fecha->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Pago:</strong> {{ ucfirst($venta->metodo_pago) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <table class="tabla-productos">
        <thead>
            <tr>
                <th>Prod.</th>
                <th>Cant.</th>
                <th>P/U</th>
                <th>SubT.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detalleVentas as $detalle)
                <tr>
                    <td>{{ Str::limit($detalle->producto->nombre, 10) }}</td>
                    <td>{{ $detalle->cantidad }} {{ ucfirst($detalle->presentacion) }}</td>
                    <td>{{ number_format($detalle->precio_presentacion, 2) }}</td>
                    <td>{{ number_format($detalle->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <table class="totales">
        <tr>
            <td><strong>Total:</strong> {{ number_format($venta->total, 2) }}</td>
        </tr>
    </table>

    <div class="qr">
        <img src="data:image/png;base64,{{ $qr }}" alt="QR">
    </div>

    <div class="footer">
        ¡Gracias por su compra!<br>
        <small>{{ $config->nombre_empresa }}</small>
    </div>
</body>
</html>
