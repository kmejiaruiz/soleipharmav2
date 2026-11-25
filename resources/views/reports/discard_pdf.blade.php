<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Salida de Inventario</title>
    <style>
        @page {
            size: letter portrait;
            margin: 14mm 14mm 16mm 14mm;
        }
        * {
            box-sizing: border-box;
            font-family: 'Helvetica', 'Arial', sans-serif;
        }
        body {
            font-size: 10.5pt;
            color: #111827;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }

        /* Encabezado Principal Crystal Reports */
        .top-header {
            width: 100%;
            border-bottom: 2px solid #000000;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .brand-name {
            font-size: 15pt;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 9pt;
            color: #4b5563;
            margin-top: 3px;
        }
        .doc-title {
            font-size: 14.5pt;
            font-weight: bold;
            text-align: center;
            color: #000000;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .doc-status {
            font-size: 10pt;
            font-weight: bold;
            color: #1f2937;
            margin-top: 4px;
        }
        .print-date {
            text-align: right;
            font-size: 9pt;
            color: #374151;
            line-height: 1.35;
        }

        /* Sección de Metadatos */
        .meta-container {
            width: 100%;
            margin-bottom: 18px;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 12px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            font-size: 10pt;
            padding: 5px 6px;
            vertical-align: top;
        }
        .lbl {
            font-weight: bold;
            color: #000000;
        }
        .val {
            color: #1f2937;
        }

        /* Tabla de Productos / Salida */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 18px;
        }
        .items-table thead th {
            font-size: 9.5pt;
            font-weight: bold;
            color: #000000;
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            padding: 9px 6px;
            background-color: #f3f4f6;
            text-transform: uppercase;
        }
        .items-table tbody td {
            font-size: 10pt;
            padding: 9px 6px;
            vertical-align: top;
            border-bottom: 1px dotted #9ca3af;
        }
        
        .subtotal-row td {
            border-top: 1.5px solid #000000;
            font-size: 10pt;
            padding: 7px 6px;
            background-color: #f9fafb;
        }
        .tax-row td {
            font-size: 10pt;
            padding: 6px 6px;
            background-color: #f9fafb;
            border-bottom: 1px solid #d1d5db;
        }
        .final-total-row td {
            border-top: 1.5px solid #000000;
            border-bottom: 2.5px solid #000000;
            font-size: 11pt;
            font-weight: bold;
            padding: 9px 6px;
            background-color: #f3f4f6;
        }

        /* Motivo y Observaciones */
        .details-box {
            width: 100%;
            margin-top: 12px;
            margin-bottom: 25px;
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 12px 16px;
        }
        .details-row {
            font-size: 10pt;
            margin-bottom: 8px;
            line-height: 1.45;
        }
        .details-row:last-child {
            margin-bottom: 0;
        }

        /* Firmas */
        .signatures-container {
            width: 100%;
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures-table td {
            vertical-align: top;
            width: 50%;
        }
        .signature-line {
            border-top: 1.5px solid #000000;
            width: 250px;
            margin-bottom: 6px;
        }
        .sig-name {
            font-weight: bold;
            font-size: 10.5pt;
            color: #000000;
        }
        .sig-title {
            font-size: 9pt;
            color: #4b5563;
            margin-top: 3px;
        }

        /* Utilidades */
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .negative-val {
            color: #000000;
            font-weight: bold;
        }
        .product-sub-tax {
            font-size: 8.5pt;
            color: #374151;
            margin-top: 4px;
            font-weight: normal;
        }
    </style>
</head>
<body>

    <!-- Encabezado Principal Crystal Reports -->
    <table class="top-header" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 28%; vertical-align: middle;">
                <div class="brand-name">FARMACIA SOLEI</div>
                <div class="brand-sub">Sistema de Gestión Farmacéutica</div>
            </td>
            <td style="width: 46%; text-align: center; vertical-align: middle;">
                <div class="doc-title">REPORTE DE SALIDA DE INVENTARIO</div>
                <div class="doc-status">
                    ESTADO: {{ strtoupper($discard->status === 'approved' ? 'APROBADA' : ($discard->status === 'rejected' ? 'RECHAZADA' : 'PENDIENTE')) }}
                </div>
            </td>
            <td style="width: 26%; vertical-align: middle;" class="print-date">
                <strong>Fecha impresión:</strong><br>
                {{ now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') }}
            </td>
        </tr>
    </table>

    @php
        // Nombre completo del autorizador
        $authorizer = $discard->decider;
        $authorizerName = '—';
        if ($authorizer) {
            $authorizerFullName = trim(($authorizer->first_name ?? '') . ' ' . ($authorizer->last_name ?? ''));
            $authorizerName = !empty($authorizerFullName) ? $authorizerFullName : ($authorizer->name ?? $authorizer->username);
        }

        // Nombre completo del solicitante
        $requester = $discard->requester;
        $requesterName = '—';
        if ($requester) {
            $reqFullName = trim(($requester->first_name ?? '') . ' ' . ($requester->last_name ?? ''));
            $requesterName = !empty($reqFullName) ? $reqFullName : ($requester->name ?? $requester->username);
        }

        // Cálculos de costo con IVA desglosado
        $baseCost = (float)($discard->product->cost ?? 0);
        $taxPercent = (float)($discard->product->purchase_tax_percent ?? $discard->product->tax_percent ?? 0);
        $costIvaUnit = $baseCost * ($taxPercent / 100);
        $costWithTax = $baseCost + $costIvaUnit;
        $qty = (float)$discard->quantity;
        
        $totalBaseCost = $qty * $baseCost;
        $totalIva = $qty * $costIvaUnit;
        $totalExitCost = $qty * $costWithTax;
    @endphp

    <!-- Datos Generales del Documento -->
    <div class="meta-container">
        <table class="meta-table">
            <tr>
                <td style="width: 50%;">
                    <span class="lbl">N° de Boleta:</span> <span class="val font-bold">#BAJA-{{ str_pad($discard->id, 4, '0', STR_PAD_LEFT) }}</span>
                </td>
                <td style="width: 50%; text-align: right;">
                    <span class="lbl">Fecha Registro:</span> <span class="val">{{ $discard->created_at ? $discard->created_at->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') : '—' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="lbl">Sucursal de Salida:</span> <span class="val">{{ $discard->branch ?? ($discard->requester->branch ?? 'Sucursal General') }}</span>
                </td>
                <td style="text-align: right;">
                    <span class="lbl">Fecha Autorización:</span> <span class="val">{{ $discard->decision_at ? $discard->decision_at->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') : '—' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="lbl">Solicitado por:</span> <span class="val">{{ $requesterName }} ({{ $discard->requester->username ?? '—' }})</span>
                </td>
                <td style="text-align: right;">
                    <span class="lbl">Autorizado por:</span> <span class="val font-bold">{{ $authorizerName }}</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla Detallada de Salida de Inventario (Cantidades, IVA y Salida en Negativo) -->
    <table class="items-table" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 18%; text-align: left;">Código / Barra</th>
                <th style="width: 38%; text-align: left;">Descripción del Producto</th>
                <th style="width: 14%; text-align: right;">Cant. Salida</th>
                <th style="width: 14%; text-align: right;">Costo Base</th>
                <th style="width: 16%; text-align: right;">Total Salida</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">{{ $discard->product->barcode ?? ($discard->product->sku ?? 'N/A') }}</td>
                <td class="text-left">
                    <div class="font-bold">{{ $discard->product->name ?? 'Producto #' . $discard->product_id }}</div>
                    @if($taxPercent > 0)
                    <div class="product-sub-tax">
                        <strong>IVA ({{ number_format($taxPercent, 0) }}%):</strong> -C$ {{ number_format($totalIva, 2) }}
                    </div>
                    @endif
                </td>
                <td class="text-right font-bold negative-val">-{{ number_format($qty, 3) }}</td>
                <td class="text-right">C$ {{ number_format($baseCost, 2) }}</td>
                <td class="text-right font-bold negative-val">-C$ {{ number_format($totalExitCost, 2) }}</td>
            </tr>
            <tr class="subtotal-row">
                <td colspan="2" class="text-left font-bold">TOTAL CANTIDAD SALIDA:</td>
                <td class="text-right font-bold negative-val">-{{ number_format($qty, 3) }}</td>
                <td class="text-right font-bold">SUBTOTAL COSTO:</td>
                <td class="text-right font-bold negative-val">-C$ {{ number_format($totalBaseCost, 2) }}</td>
            </tr>
            <tr class="tax-row">
                <td colspan="4" class="text-right font-bold">TOTAL IVA ({{ number_format($taxPercent, 0) }}%):</td>
                <td class="text-right font-bold negative-val">-C$ {{ number_format($totalIva, 2) }}</td>
            </tr>
            <tr class="final-total-row">
                <td colspan="4" class="text-right font-bold">TOTAL SALIDA:</td>
                <td class="text-right font-bold negative-val">-C$ {{ number_format($totalExitCost, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Motivo y Justificación -->
    <div class="details-box">
        <div class="details-row">
            <span class="lbl">Motivo / Causa de la Baja:</span>
            <span class="val">{{ $discard->reason }}</span>
        </div>
        @if($discard->decision_reason)
        <div class="details-row">
            <span class="lbl">Observaciones de Autorización:</span>
            <span class="val">{{ $discard->decision_reason }}</span>
        </div>
        @endif
    </div>

    <!-- Bloque de Firmas -->
    <div class="signatures-container">
        <table class="signatures-table">
            <tr>
                <!-- Usuario Autoriza alineado a la izquierda -->
                <td style="text-align: left;">
                    <div class="signature-line"></div>
                    <div class="sig-name">{{ $authorizerName }}</div>
                    <div class="sig-title">Usuario Autoriza (Administración)</div>
                </td>

                <!-- Solicitado por a la derecha -->
                <td style="text-align: right;">
                    <div style="display: inline-block; text-align: left;">
                        <div class="signature-line"></div>
                        <div class="sig-name">{{ $requesterName }}</div>
                        <div class="sig-title">Solicitado Por (Bodega / Caja)</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
