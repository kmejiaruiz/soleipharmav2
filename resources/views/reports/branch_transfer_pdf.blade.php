<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Traslado #TR-{{ str_pad($transfer->id, 4, '0', STR_PAD_LEFT) }}</title>
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
        .items-table tbody tr:nth-child(even) td {
            background-color: #f7f7f7;
        }
        .table-footer-line {
            border-top: 1px solid #000000;
            font-weight: bold;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 9pt;
        }
        .summary-table td {
            padding: 3px 0;
            vertical-align: top;
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
            padding: 0 40px;
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
        .diff-ok { color: #007500; font-weight: bold; }
    </style>
</head>
<body>

    <!-- Encabezado -->
    <table class="top-header">
        <tr>
            <td style="width: 28%; vertical-align: top;">
                <div class="brand-name">FARMACIA SOLEI</div>
                <div style="font-size: 8.5pt; color: #555;">Sistema de Inventarios — Traslado entre Sucursales</div>
            </td>
            <td style="width: 44%; text-align: center; vertical-align: top;">
                <div class="doc-title">BOLETA DE TRASLADO DE INVENTARIO</div>
                <div class="doc-subtitle">
                    N° BT-{{ str_pad($transfer->id, 4, '0', STR_PAD_LEFT) }} &nbsp;·&nbsp; Estado: {{ strtoupper($transfer->status) }}
                </div>
            </td>
            <td style="width: 28%; vertical-align: top;" class="print-date">
                <strong>Hora de Impresión:</strong><br>
                <span style="color:#c00000; font-weight:bold;">
                    {{ now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Metadatos -->
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <span class="lbl">Sucursal Origen:</span> {{ $transfer->from_branch }}
            </td>
            <td style="width: 50%; text-align: right;">
                <span class="lbl" style="width:auto;">Sucursal Destino:</span> {{ $transfer->to_branch }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Fecha Emisión:</span> {{ $transfer->created_at ? \Carbon\Carbon::parse($transfer->created_at)->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') : now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y') }}
            </td>
            <td style="text-align: right;">
                <span class="lbl" style="width:auto;">Emitido por:</span> {{ $transfer->creator->name ?? ($transfer->creator->username ?? '—') }}
            </td>
        </tr>
        @if($transfer->status === 'recibido')
        <tr>
            <td>
                <span class="lbl">Fecha Recepción:</span> {{ $transfer->received_at ? \Carbon\Carbon::parse($transfer->received_at)->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') : '—' }}
            </td>
            <td style="text-align: right;">
                <span class="lbl" style="width:auto;">Recibido por:</span> {{ $transfer->receiver->name ?? ($transfer->receiver->username ?? '—') }}
            </td>
        </tr>
        @endif
        @if($transfer->notes)
        <tr>
            <td colspan="2">
                <span class="lbl">Observaciones:</span> {{ $transfer->notes }}
            </td>
        </tr>
        @endif
    </table>

    <!-- Tabla de Artículos con Costos y Diferencias -->
    @php
        $isReceived = ($transfer->status === 'recibido');
        $totalSentQty = 0;
        $totalRecvQty = 0;
        $totalSentVal = 0;
        $totalRecvVal = 0;
    @endphp

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">#</th>
                <th style="width: 14%; text-align: left;">SKU / Código</th>
                <th style="width: 30%; text-align: left;">Descripción del Producto</th>
                <th style="width: 10%; text-align: right;">Costo Unit.</th>
                <th style="width: 9%; text-align: center;">Uds. Env.</th>
                <th style="width: 11%; text-align: right;">Val. Enviado</th>
                @if($isReceived)
                <th style="width: 9%; text-align: center;">Uds. Rec.</th>
                <th style="width: 11%; text-align: right;">Val. Recibido</th>
                <th style="width: 10%; text-align: right;">Dif. Costo</th>
                <th style="width: 6%; text-align: center;">Dif.</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($transfer->items as $idx => $item)
                @php
                    $cost = (float)($item->product->cost ?? 0);
                    $sentQty = (int)$item->quantity_sent;
                    $recvQty = $isReceived ? (int)($item->quantity_received ?? $sentQty) : null;
                    $sentVal = $cost * $sentQty;
                    $recvVal = $isReceived ? ($cost * $recvQty) : 0;
                    $diffQty = $isReceived ? ($recvQty - $sentQty) : 0;
                    $diffVal = $isReceived ? ($diffQty * $cost) : 0;

                    $totalSentQty += $sentQty;
                    $totalSentVal += $sentVal;
                    if ($isReceived) {
                        $totalRecvQty += $recvQty;
                        $totalRecvVal += $recvVal;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left">{{ $item->product->sku ?? ($item->product->barcode ?? '—') }}</td>
                    <td class="text-left font-bold">{{ $item->product->name ?? 'Producto #' . $item->product_id }}</td>
                    <td class="text-right">{{ $cost > 0 ? number_format($cost, 2) : '—' }}</td>
                    <td class="text-center font-bold">{{ number_format($sentQty) }}</td>
                    <td class="text-right">{{ $cost > 0 ? number_format($sentVal, 2) : '—' }}</td>
                    @if($isReceived)
                    <td class="text-center font-bold">{{ number_format($recvQty) }}</td>
                    <td class="text-right">{{ $cost > 0 ? number_format($recvVal, 2) : '—' }}</td>
                    <td class="text-right {{ $diffVal < 0 ? 'diff-neg' : ($diffVal > 0 ? 'text-emerald-700' : '') }}">
                        {{ $cost > 0 ? ($diffVal < 0 ? '-C$ ' . number_format(abs($diffVal), 2) : ($diffVal > 0 ? '+C$ ' . number_format($diffVal, 2) : 'C$ 0.00')) : '—' }}
                    </td>
                    <td class="text-center {{ $diffQty < 0 ? 'diff-neg' : 'diff-ok' }}">
                        {{ $diffQty === 0 ? '✓' : ($diffQty > 0 ? "+{$diffQty}" : $diffQty) }}
                    </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isReceived ? 10 : 6 }}" class="text-center" style="padding: 10px;">No hay productos registrados en este traslado.</td>
                </tr>
            @endforelse
            <tr class="table-footer-line">
                <td colspan="3" class="text-right"><strong>TOTALES:</strong></td>
                <td class="text-right"></td>
                <td class="text-center"><strong>{{ number_format($totalSentQty) }}</strong></td>
                <td class="text-right"><strong>C$ {{ number_format($totalSentVal, 2) }}</strong></td>
                @if($isReceived)
                <td class="text-center"><strong>{{ number_format($totalRecvQty) }}</strong></td>
                <td class="text-right"><strong>C$ {{ number_format($totalRecvVal, 2) }}</strong></td>
                <td class="text-right {{ ($totalRecvVal - $totalSentVal) < 0 ? 'diff-neg' : '' }}">
                    <strong>{{ ($totalRecvVal - $totalSentVal) < 0 ? '-C$ ' . number_format(abs($totalRecvVal - $totalSentVal), 2) : 'C$ ' . number_format(max(0, $totalRecvVal - $totalSentVal), 2) }}</strong>
                </td>
                <td class="text-center {{ ($totalRecvQty - $totalSentQty) < 0 ? 'diff-neg' : 'diff-ok' }}">
                    <strong>{{ ($totalRecvQty - $totalSentQty) === 0 ? '✓' : ($totalRecvQty - $totalSentQty) }}</strong>
                </td>
                @endif
            </tr>
        </tbody>
    </table>

    <!-- Resumen -->
    <table class="summary-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <strong>Total de Líneas:</strong> &nbsp;&nbsp;&nbsp; <u>{{ count($transfer->items) }}</u><br>
                <strong>Total Unidades Enviadas:</strong> &nbsp;&nbsp;&nbsp; <u>{{ number_format($totalSentQty) }}</u>
                @if($isReceived)
                <br><strong>Total Unidades Recibidas:</strong> &nbsp;&nbsp;&nbsp; <u>{{ number_format($totalRecvQty) }}</u>
                @if($totalSentQty - $totalRecvQty > 0)
                <br><strong style="color:#c00000;">Unidades Faltantes:</strong> &nbsp;&nbsp;&nbsp; <u style="color:#c00000; font-weight:bold;">{{ number_format($totalSentQty - $totalRecvQty) }}</u>
                @endif
                @endif
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <strong>Valor Total Enviado:</strong> &nbsp;&nbsp;&nbsp; C$ {{ number_format($totalSentVal, 2) }}<br>
                @if($isReceived)
                <strong>Valor Total Recibido:</strong> &nbsp;&nbsp;&nbsp; C$ {{ number_format($totalRecvVal, 2) }}<br>
                @if(abs($totalSentVal - $totalRecvVal) > 0.001)
                <strong style="color:#c00000;">Diferencia de Costo (Faltante):</strong> &nbsp;&nbsp;&nbsp; <span style="color:#c00000; font-weight:bold;">-C$ {{ number_format(abs($totalSentVal - $totalRecvVal), 2) }}</span>
                @else
                <strong style="color:#007500;">Entrega Completa (Sin Faltantes):</strong> &nbsp;&nbsp;&nbsp; <span style="color:#007500; font-weight:bold;">C$ 0.00</span>
                @endif
                @endif
            </td>
        </tr>
    </table>

    <!-- Firmas -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <strong>Entregado Por (Origen)</strong><br>
                {{ $transfer->creator->name ?? ($transfer->creator->username ?? '—') }}<br>
                <span style="font-size:7.5pt; color:#555;">{{ $transfer->from_branch }}</span>
            </td>
            <td>
                <div class="signature-line"></div>
                <strong>Recibido Por (Destino)</strong><br>
                {{ $transfer->receiver->name ?? ($transfer->receiver->username ?? 'Firma y Sello de Recepción') }}<br>
                <span style="font-size:7.5pt; color:#555;">{{ $transfer->to_branch }}</span>
            </td>
        </tr>
    </table>

</body>
</html>
