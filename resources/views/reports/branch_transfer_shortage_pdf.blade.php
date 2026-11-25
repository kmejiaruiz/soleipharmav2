<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Faltantes #TR-{{ str_pad($transfer->id, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 12mm 15mm 15mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5pt;
            color: #000000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .top-header {
            width: 100%;
            margin-bottom: 12px;
        }
        .brand-name {
            font-size: 14pt;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 13.5pt;
            font-weight: bold;
            text-align: center;
            margin: 0;
            text-transform: uppercase;
            color: #c00000;
        }
        .doc-subtitle {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            margin-top: 3px;
            color: #000000;
        }
        .print-date {
            font-size: 8.5pt;
            text-align: right;
            color: #555555;
        }

        .alert-box {
            background-color: #fff2f2;
            border: 1.5px solid #c00000;
            padding: 8px 12px;
            margin-bottom: 14px;
            font-size: 9pt;
            color: #8a0000;
            border-radius: 4px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9pt;
        }
        .meta-table td {
            padding: 3.5px 4px;
            vertical-align: top;
        }
        .lbl {
            font-weight: bold;
            color: #222;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 8.5pt;
        }
        .items-table th {
            background-color: #f0f0f0;
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            padding: 6px 5px;
            font-weight: bold;
            font-size: 8.5pt;
        }
        .items-table td {
            padding: 5px 5px;
            vertical-align: middle;
            border-bottom: 1px solid #e0e0e0;
        }
        .table-footer-line td {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            font-weight: bold;
            background-color: #fafafa;
        }

        .summary-box {
            width: 100%;
            border: 1px solid #000000;
            background-color: #fcfcfc;
            padding: 10px 14px;
            margin-top: 6px;
            margin-bottom: 16px;
            font-size: 9pt;
            box-sizing: border-box;
        }

        .disclaimer {
            font-size: 8pt;
            color: #444;
            font-style: italic;
            line-height: 1.4;
            border-left: 3px solid #888;
            padding-left: 8px;
            margin-bottom: 24px;
        }

        .signatures-table {
            width: 100%;
            margin-top: 36px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 30px;
            font-size: 8.5pt;
        }
        .signature-line {
            border-top: 1px solid #000000;
            margin-bottom: 4px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .diff-neg { color: #c00000; font-weight: bold; }
    </style>
</head>
<body>

    <!-- Encabezado Oficial -->
    <table class="top-header">
        <tr>
            <td style="width: 30%; vertical-align: top;">
                <div class="brand-name">FARMACIA SOLEI</div>
                <div style="font-size: 8.5pt; color: #555;">Auditoría y Control de Inventarios</div>
            </td>
            <td style="width: 40%; text-align: center; vertical-align: top;">
                <div class="doc-title">ACTA DE FALTANTES EN TRASLADO</div>
                <div class="doc-subtitle">
                    COMPROBANTE N° AF-{{ str_pad($transfer->id, 4, '0', STR_PAD_LEFT) }}
                </div>
            </td>
            <td style="width: 30%; vertical-align: top;" class="print-date">
                <strong>Fecha de Emisión:</strong><br>
                {{ now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') }}
            </td>
        </tr>
    </table>

    <!-- Alerta Oficial de Discrepancia -->
    <div class="alert-box">
        <strong>AVISO OFICIAL DE DISCREPANCIA:</strong> Este documento certifica formalmente los artículos y unidades físicas que no fueron entregados o hicieron falta al momento de recibir el traslado de mercadería entre sucursales.
    </div>

    <!-- Metadatos del Traslado Original -->
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <span class="lbl">Sucursal Remitente (Origen):</span> {{ $transfer->from_branch }}
            </td>
            <td style="width: 50%;">
                <span class="lbl">Sucursal Receptora (Destino):</span> {{ $transfer->to_branch }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">N° Boleta de Traslado:</span> #BT-{{ str_pad($transfer->id, 4, '0', STR_PAD_LEFT) }}
            </td>
            <td>
                <span class="lbl">Fecha de Recepción:</span> {{ $transfer->received_at ? \Carbon\Carbon::parse($transfer->received_at)->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') : now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Despachado por (Origen):</span> {{ $transfer->creator->name ?? ($transfer->creator->username ?? '—') }}
            </td>
            <td>
                <span class="lbl">Recibido y Verificado por:</span> {{ $transfer->receiver->name ?? ($transfer->receiver->username ?? '—') }}
            </td>
        </tr>
        @if($transfer->notes)
        <tr>
            <td colspan="2">
                <span class="lbl">Notas / Justificación de la Discrepancia:</span> {{ $transfer->notes }}
            </td>
        </tr>
        @endif
    </table>

    <!-- Tabla Exclusiva de Artículos con Faltantes -->
    @php
        $totalMissingQty = 0;
        $totalMissingVal = 0;
        $totalSentOfMissing = 0;
        $totalRecvOfMissing = 0;
    @endphp

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 15%; text-align: left;">SKU / Código</th>
                <th style="width: 32%; text-align: left;">Descripción del Producto</th>
                <th style="width: 11%; text-align: right;">Costo Unit.</th>
                <th style="width: 9%; text-align: center;">Uds. Env.</th>
                <th style="width: 9%; text-align: center;">Uds. Rec.</th>
                <th style="width: 9%; text-align: center; color: #c00000;">Faltante</th>
                <th style="width: 10%; text-align: right; color: #c00000;">Pérdida (C$)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shortageItems as $idx => $item)
                @php
                    $cost = (float)($item->product->cost ?? 0);
                    $sent = (int)$item->quantity_sent;
                    $recv = (int)($item->quantity_received ?? 0);
                    $diff = $sent - $recv; // Unidades que faltaron
                    $loss = $diff * $cost;

                    $totalMissingQty += $diff;
                    $totalMissingVal += $loss;
                    $totalSentOfMissing += $sent;
                    $totalRecvOfMissing += $recv;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left font-bold">{{ $item->product->sku ?? ($item->product->barcode ?? '—') }}</td>
                    <td class="text-left font-bold">{{ $item->product->name ?? 'Producto #' . $item->product_id }}</td>
                    <td class="text-right">{{ $cost > 0 ? 'C$ ' . number_format($cost, 2) : '—' }}</td>
                    <td class="text-center">{{ number_format($sent) }}</td>
                    <td class="text-center">{{ number_format($recv) }}</td>
                    <td class="text-center diff-neg">-{{ number_format($diff) }}</td>
                    <td class="text-right diff-neg">-C$ {{ number_format($loss, 2) }}</td>
                </tr>
            @endforeach
            <tr class="table-footer-line">
                <td colspan="4" class="text-right"><strong>TOTALES DE FALTANTES:</strong></td>
                <td class="text-center"><strong>{{ number_format($totalSentOfMissing) }}</strong></td>
                <td class="text-center"><strong>{{ number_format($totalRecvOfMissing) }}</strong></td>
                <td class="text-center diff-neg"><strong>-{{ number_format($totalMissingQty) }}</strong></td>
                <td class="text-right diff-neg"><strong>-C$ {{ number_format($totalMissingVal, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Resumen Financiero del Faltante -->
    <div class="summary-box">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 60%; vertical-align: middle;">
                    <strong>Artículos con Discrepancia:</strong> &nbsp;<u>{{ count($shortageItems) }} producto(s)</u><br>
                    <strong>Total Unidades Faltantes:</strong> &nbsp;<span class="diff-neg" style="font-size: 11pt;">{{ number_format($totalMissingQty) }} unidades</span>
                </td>
                <td style="width: 40%; text-align: right; vertical-align: middle;">
                    <div style="font-size: 9pt; color: #444;">VALOR TOTAL DEL FALTANTE A AJUSTAR:</div>
                    <div class="diff-neg" style="font-size: 13pt; margin-top: 2px;">
                        -C$ {{ number_format($totalMissingVal, 2) }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Declaración Legal / Auditoría -->
    <div class="disclaimer">
        Hacemos constar que al momento de la apertura y verificación física del paquete correspondiente al traslado <strong>#BT-{{ str_pad($transfer->id, 4, '0', STR_PAD_LEFT) }}</strong>, se constató la ausencia de los artículos y unidades arriba especificados. Estas unidades <strong>NO ingresaron al inventario de la sucursal receptora</strong> y quedan asentadas bajo este comprobante oficial para los fines de investigación y ajuste contable correspondientes.
    </div>

    <!-- Firma Formal -->
    <table class="signatures-table" align="left" style="width: 28%; margin: 65px 0 0 0;">
        <tr>
            <td style="text-align: center; width: 100%; padding: 0;">
                <div class="signature-line"></div>
                <strong style="font-size: 9pt;">Usuario Recibe</strong><br>
                <span style="font-size: 8.5pt;">{{ $transfer->receiver->name ?? ($transfer->receiver->username ?? '—') }}</span>
            </td>
        </tr>
    </table>

</body>
</html>
