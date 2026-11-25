<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Venta #FAC-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 10mm 15mm 12mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            color: #000000;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .top-header {
            width: 100%;
            margin-bottom: 12px;
        }
        .brand-name {
            font-size: 13pt;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 15pt;
            font-weight: bold;
            text-align: center;
            margin: 0;
            text-transform: uppercase;
        }
        .doc-subtitle {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            margin-top: 2px;
            color: #000000;
        }
        .print-date {
            font-size: 8.5pt;
            text-align: right;
            color: #000000;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9pt;
        }
        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .lbl {
            font-weight: bold;
            width: 120px;
            display: inline-block;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        .items-table th {
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            padding: 6px 4px;
            font-weight: bold;
            font-size: 8.5pt;
        }
        .items-table td {
            padding: 5px 4px;
            vertical-align: middle;
        }
        .table-footer-line {
            border-bottom: 1px solid #000000;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 9pt;
        }
        .summary-table td {
            padding: 2px 0;
            vertical-align: top;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
    </style>
</head>
<body>

    <!-- Encabezado Crystal Reports -->
    <table class="top-header">
        <tr>
            <td style="width: 25%; vertical-align: top;">
                <div class="brand-name">FARMACIA SOLEI</div>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div class="doc-title">COMPROBANTE DE VENTA / FACTURA</div>
                <div class="doc-subtitle">Punto de Venta &bull; Atención Farmacéutica</div>
            </td>
            <td style="width: 25%; vertical-align: top;" class="print-date">
                Fecha impresión: {{ now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') }}
            </td>
        </tr>
    </table>

    <!-- Metadatos Crystal Reports -->
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <span class="lbl">Sucursal:</span> {{ $order->branch ?? 'Sucursal Leon' }}
            </td>
            <td style="width: 50%; text-align: right;">
                <span class="lbl" style="width:auto;">N° Factura:</span> #FAC-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Cliente:</span> {{ $order->client_name ?? 'Consumidor Final' }}
            </td>
            <td style="text-align: right;">
                <span class="lbl" style="width:auto;">Fecha Venta:</span> {{ $order->created_at ? (new \DateTime($order->created_at))->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i A') : now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i A') }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Método de Pago:</span> {{ strtoupper($order->payment_method ?? $order->pay_method ?? 'EFECTIVO') }}
            </td>
            <td style="text-align: right;">
                <span class="lbl" style="width:auto;">Estado:</span> PAGADA / EMITIDA
            </td>
        </tr>
    </table>

    <!-- Tabla de Artículos -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 16%; text-align: left;">Código / Barcode</th>
                <th style="width: 48%; text-align: left;">Descripción del Producto</th>
                <th style="width: 10%; text-align: center;">Cant.</th>
                <th style="width: 10%; text-align: right;">Precio Unit.</th>
                <th style="width: 10%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left">{{ $item->product->barcode ?? ($item->product->sku ?? 'N/A') }}</td>
                    <td class="text-left">{{ $item->product->name ?? 'Producto #' . $item->product_id }}</td>
                    <td class="text-center font-bold">{{ $item->quantity }}</td>
                    <td class="text-right">C$ {{ number_format((float)$item->price, 2) }}</td>
                    <td class="text-right font-bold">C$ {{ number_format((float)($item->price * $item->quantity), 2) }}</td>
                </tr>
            @endforeach
            <tr class="table-footer-line"><td colspan="6"></td></tr>
        </tbody>
    </table>

    <!-- Resumen y Totales -->
    <table class="summary-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <strong>Total de Líneas:</strong> &nbsp;&nbsp;&nbsp; <u>{{ count($order->items) }}</u><br>
                <span style="font-size: 8pt; color: #444;">Gracias por su preferencia. Conserve este comprobante.</span>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    @if(($order->discount ?? 0) > 0)
                    <tr>
                        <td style="text-align: right; padding-right: 15px;">Subtotal:</td>
                        <td style="text-align: right; width: 110px;">C$ {{ number_format((float)(($order->total ?? 0) + ($order->discount ?? 0)), 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-right: 15px;">Descuento:</td>
                        <td style="text-align: right; width: 110px;">- C$ {{ number_format((float)$order->discount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="text-align: right; font-weight: bold; padding-right: 15px;">TOTAL PAGADO:</td>
                        <td style="text-align: right; width: 110px; font-weight: bold; font-size: 11pt;">C$ {{ number_format((float)$order->total, 2) }}</td>
                    </tr>
                    @if(($order->amount_paid ?? 0) > 0)
                    <tr>
                        <td style="text-align: right; padding-right: 15px;">Monto Recibido:</td>
                        <td style="text-align: right; width: 110px;">C$ {{ number_format((float)$order->amount_paid, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-right: 15px;">Cambio Entregado:</td>
                        <td style="text-align: right; width: 110px;">C$ {{ number_format((float)($order->change_given ?? max(0, $order->amount_paid - $order->total)), 2) }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
