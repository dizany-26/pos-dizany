<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Venta</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        .empresa { font-size: 16px; font-weight: bold; }
        .logo img { max-height: 70px; margin-bottom: 5px; }
        .comprobante { font-size: 14px; margin-top: 5px; font-weight: bold; }
        .datos-empresa { font-size: 12px; margin-bottom: 8px; }
        .datos-cliente, .tabla-productos {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }
        .tabla-productos th, .tabla-productos td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .totales {
            margin-top: 15px;
            width: 100%;
            font-size: 13px;
        }
        .totales td {
            text-align: right;
            padding: 3px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
        }
        .qr {
            text-align: center;
            margin-top: 15px;
        }
        .qr img {
            width: 100px;
            height: 100px;
        }
    </style>
</head>
<body>
    <div class="header">
        <!-- LOGO -->
        @if($logoBase64)
            <div class="logo">
                <img src="{{ $logoBase64 }}" alt="Logo">
            </div>
        @endif

        <!-- NOMBRE EMPRESA -->
        <div class="empresa">{{ $config->nombre_empresa ?? 'Nombre de la Empresa' }}</div>

        <!-- DATOS DE EMPRESA -->
        <div class="datos-empresa">
            <div>RUC: {{ $config->ruc ?? '00000000000' }}</div>
            <div>{{ $config->direccion ?? 'Dirección no registrada' }}</div>
            <div>Tel: {{ $config->telefono ?? '-' }} | Correo: {{ $config->correo ?? 'correo@empresa.com' }}</div>
        </div>

        <div class="comprobante">BOLETA DE VENTA</div>
        <div>{{ $venta->serie }}-{{ str_pad($venta->correlativo, 6, '0', STR_PAD_LEFT) }}</div>
    </div>

    <!-- DATOS DEL CLIENTE -->
    <table class="datos-cliente">
        <tr>
            <td><strong>Cliente:</strong> {{ $venta->cliente->nombre ?? 'Consumidor Final' }}</td>
            <td><strong>DNI:</strong> {{ $venta->cliente->dni ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Fecha:</strong> {{ $venta->fecha->format('d/m/Y') }}</td>
            <td><strong>Método Pago:</strong> {{ ucfirst($venta->metodo_pago) }}</td>
        </tr>
    </table>

    <!-- DETALLE DE PRODUCTOS -->
    <table class="tabla-productos">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
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

    <!-- TOTALES -->
    @php
        $opGravadas = $venta->total / (1 + ($config->igv / 100));
        $igv = $venta->total - $opGravadas;
    @endphp
    <table class="totales">
        <tr>
            <td><strong>Op. Gravadas:</strong> {{ $config->moneda }} {{ number_format($opGravadas, 2) }}</td>
        </tr>
        <tr>
            <td><strong>IGV ({{ $config->igv }}%):</strong> {{ $config->moneda }} {{ number_format($igv, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total:</strong> {{ $config->moneda }} {{ number_format($venta->total, 2) }}</td>
        </tr>
    </table>

    <!-- QR -->
    <div class="qr">
        <img src="data:image/png;base64,{{ $qr }}" alt="Código QR">
    </div>

    <div class="footer">
        ¡Gracias por su compra!
    </div>
</body>
</html>
