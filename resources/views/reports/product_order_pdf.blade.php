<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Pedido #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</title>
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
            width: 130px;
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

        .signature-box-left {
            margin-top: 45px;
            text-align: left;
            width: 280px;
        }
        .signature-line-left {
            border-top: 1px solid #000000;
            margin-bottom: 5px;
            width: 100%;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
    </style>
</head>
<body>

    @php
        $authorizerName = $order->admin->full_name ?? ($order->admin_name ?? 'Administración');
        $receivedByName = $order->goodsEntry->creator->full_name 
            ?? ($order->appliedBy->full_name ?? ($order->goodsEntry->creator->username ?? ($order->appliedBy->username ?? null)));
    @endphp

    <!-- Encabezado Crystal Reports -->
    <table class="top-header">
        <tr>
            <td style="width: 25%; vertical-align: top;">
                <div class="brand-name">FARMACIA SOLEI</div>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div class="doc-title">BOLETA DE PEDIDO</div>
                <div class="doc-subtitle">
                    Estado: {{ $order->status === 'pending' ? 'PENDIENTE' : ($order->status === 'applied' ? 'APLICADO' : strtoupper($order->status)) }}
                </div>
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
                <span class="lbl" style="width:auto;">Proveedor:</span> {{ $order->supplier->name ?? 'Proveedor General' }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Boleta Pedido:</span> #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }} &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="lbl" style="width:auto;">Fecha Boleta:</span> {{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') : now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') }}
            </td>
            <td style="text-align: right;">
                <span class="lbl" style="width:auto;">Usuario Autoriza Pedido:</span> {{ $authorizerName }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Tipo Documento:</span> Orden de Resurtido
            </td>
            <td style="text-align: right;">
                @if(in_array($order->status, ['applied', 'received']) && $receivedByName)
                    <span class="lbl" style="width:auto;">Usuario Autoriza Entrada:</span> {{ $receivedByName }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Tabla de Productos -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">#</th>
                <th style="width: 14%; text-align: left;">Código / Barcode</th>
                <th style="width: 44%; text-align: left;">Descripción del Producto</th>
                <th style="width: 12%; text-align: right;">Cant. Solicitada</th>
                <th style="width: 12%; text-align: right;">Costo Unit.</th>
                <th style="width: 12%; text-align: right;">Total Estimado</th>
            </tr>
        </thead>
        <tbody>
            @php
                $calcSubtotal = 0;
            @endphp
            @forelse($order->items as $idx => $it)
                @php
                    $unitPrice = (float)($it->price > 0 ? $it->price : ($it->product->cost ?? 0));
                    $lineTotal = (int)$it->quantity * $unitPrice;
                    $calcSubtotal += $lineTotal;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left">{{ $it->product->barcode ?? ($it->product->sku ?? 'N/A') }}</td>
                    <td class="text-left">{{ $it->product->name ?? 'Producto #' . $it->product_id }}</td>
                    <td class="text-right">{{ number_format((float)$it->quantity, 3) }}</td>
                    <td class="text-right">{{ number_format($unitPrice, 2) }}</td>
                    <td class="text-right">{{ number_format($lineTotal, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 10px;">No hay productos en este pedido.</td>
                </tr>
            @endforelse
            <tr class="table-footer-line"><td colspan="6"></td></tr>
        </tbody>
    </table>

    <!-- Resumen Crystal Reports -->
    @php
        $finalSubtotal = $order->total > 0 ? (float)$order->total : $calcSubtotal;
        $iva = round($finalSubtotal * 0.15, 2);
        $totalConIva = round($finalSubtotal + $iva, 2);
    @endphp
    <table class="summary-table">
        <tr>
            <td style="width: 40%; vertical-align: top;">
                <strong>Total de Líneas:</strong> &nbsp;&nbsp;&nbsp; <u>{{ count($order->items) }}</u>
            </td>
            <td style="width: 60%; text-align: right; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: right; font-weight: bold; padding-right: 15px;">Subtotal Pedido (C$)</td>
                        <td style="text-align: right; width: 110px;">{{ number_format($finalSubtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-weight: bold; padding-right: 15px;">IVA Estimado (15%)</td>
                        <td style="text-align: right; width: 110px;">{{ number_format($iva, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-weight: bold; padding-right: 15px;">TOTAL ESTIMADO (C$)</td>
                        <td style="text-align: right; width: 110px; font-weight: bold;">{{ number_format($totalConIva, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Firmas de Autorización -->
    <table style="width: 100%; margin-top: 35px; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <div class="signature-box-left" style="margin-top: 0;">
                    <div class="signature-line-left"></div>
                    <strong style="font-size: 9pt;">Usuario Autoriza Pedido</strong><br>
                    <span style="font-size: 9pt;">{{ $authorizerName }}</span>
                </div>
            </td>
            @if(in_array($order->status, ['applied', 'received']) && $receivedByName)
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <div class="signature-box-left" style="margin-top: 0; margin-left: auto; text-align: center;">
                    <div class="signature-line-left"></div>
                    <strong style="font-size: 9pt;">Usuario Autoriza Entrada</strong><br>
                    <span style="font-size: 9pt;">{{ $receivedByName }}</span>
                </div>
            </td>
            @endif
        </tr>
    </table>

</body>
</html>
