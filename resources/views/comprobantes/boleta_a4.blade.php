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
        $operationAmount = (float) ($venta->op_gravadas + $venta->op_exoneradas + $venta->op_inafectas + $venta->op_nrus);
        $operationLabel = match($venta->tax_treatment) {'exonerada'=>'Op. Exoneradas', 'inafecta'=>'Op. Inafectas', 'nrus_no_desglosado'=>'Valor de venta', default=>'Op. Gravadas'};
    @endphp
    <table class="totales">
        <tr>
            <td><strong>{{ $operationLabel }}:</strong> {{ $config->moneda }} {{ number_format($operationAmount, 2) }}</td>
        </tr>
        @if($venta->tax_treatment === 'gravada')<tr><td><strong>IGV ({{ number_format($venta->igv_rate, 2) }}%):</strong> {{ $config->moneda }} {{ number_format($venta->igv, 2) }}</td></tr>@endif
        <tr>
            <td><strong>Total:</strong> {{ $config->moneda }} {{ number_format($venta->total, 2) }}</td>
        </tr>
    </table>

    <!-- QR -->
    <div class="qr">
        <img src="data:image/svg+xml;base64,{{ $qr }}" alt="Código QR">
    </div>

    <div class="footer">
        ¡Gracias por su compra!
    </div>
</body>
</html>
