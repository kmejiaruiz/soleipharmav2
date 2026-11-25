<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Recepción #{{ str_pad($entry->order_id ?? $entry->id, 3, '0', STR_PAD_LEFT) }}</title>
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
        $authorizerName = $entry->creator->full_name ?? ($entry->creator->name ?? ($entry->creator->username ?? 'Administración'));
    @endphp

    <!-- Encabezado Crystal Reports -->
    <table class="top-header">
        <tr>
            <td style="width: 25%; vertical-align: top;">
                <div class="brand-name">FARMACIA SOLEI</div>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <div class="doc-title">BOLETA DE RECEPCIÓN</div>
                <div class="doc-subtitle">Estado: RECIBIDO</div>
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
                <span class="lbl">Sucursal:</span> {{ $entry->branch ?? 'Sucursal Leon' }}
            </td>
            <td style="width: 50%; text-align: right;">
                <span class="lbl" style="width:auto;">Proveedor:</span> {{ $entry->supplier->name ?? 'INFARSA S.A.' }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Boleta:</span> {{ str_pad($entry->order_id ?? $entry->id, 3, '0', STR_PAD_LEFT) }} &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="lbl" style="width:auto;">Fecha Boleta:</span> {{ $entry->created_at ? \Carbon\Carbon::parse($entry->created_at)->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') : now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') }}
            </td>
            <td style="text-align: right;">
                <span class="lbl" style="width:auto;">Usuario Autoriza:</span> {{ $authorizerName }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Pedido Orig:</span> #{{ str_pad($entry->order_id, 3, '0', STR_PAD_LEFT) }}
            </td>
            <td style="text-align: right;">
                @if($entry->invoice_reference)
                    <span class="lbl" style="width:auto;">Factura Ref:</span> {{ $entry->invoice_reference }}
                @endif
            </td>
        </tr>
    </table>

    <!-- Tabla de Artículos -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 12%; text-align: left;">Código</th>
                <th style="width: 38%; text-align: left;">Nombre del Producto</th>
                <th style="width: 10%; text-align: right;">C. Fact</th>
                <th style="width: 10%; text-align: right;">C. Scan</th>
                <th style="width: 10%; text-align: right;">C. Ent</th>
                <th style="width: 10%; text-align: right;">Costo Unit.</th>
                <th style="width: 10%; text-align: right;">Total Sistema</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entry->items as $it)
                <tr>
                    <td class="text-left">{{ $it->product->barcode ?? ($it->product->sku ?? 'N/A') }}</td>
                    <td class="text-left">{{ $it->product->name ?? 'Producto' }}</td>
                    <td class="text-right">{{ number_format((float)($it->quantity_factured ?? $it->quantity_received), 3) }}</td>
                    <td class="text-right">{{ number_format((float)$it->quantity_received, 3) }}</td>
                    <td class="text-right">{{ number_format((float)$it->quantity_received, 3) }}</td>
                    <td class="text-right">{{ number_format((float)($it->unit_cost ?? $it->cost ?? 0), 2) }}</td>
                    <td class="text-right">{{ number_format((float)($it->quantity_received * ($it->unit_cost ?? $it->cost ?? 0)), 2) }}</td>
                </tr>
            @endforeach
            <tr class="table-footer-line"><td colspan="7"></td></tr>
        </tbody>
    </table>

    <!-- Resumen Inferior -->
    <table class="summary-table">
        <tr>
            <td style="width: 40%; vertical-align: top;">
                <strong>Total de Líneas:</strong> &nbsp;&nbsp;&nbsp; <u>{{ count($entry->items) }}</u>
            </td>
            <td style="width: 60%; text-align: right; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: right; font-weight: bold; padding-right: 15px;">Total Factura Física</td>
                        <td style="text-align: right; width: 110px;">{{ number_format((float)($entry->invoice_subtotal + $entry->invoice_tax), 2) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-weight: bold; padding-right: 15px;">Total Entrada (Sistema)</td>
                        <td style="text-align: right; width: 110px;">{{ number_format((float)$entry->system_total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Firma Autorización Alineada a la Izquierda con Nombre de la Persona -->
    <div class="signature-box-left">
        <div class="signature-line-left"></div>
        <strong style="font-size: 9pt;">Usuario Autoriza</strong><br>
        <span style="font-size: 9pt;">{{ $authorizerName }}</span>
    </div>

</body>
</html>
