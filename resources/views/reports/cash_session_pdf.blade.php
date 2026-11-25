<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Arqueo de Caja #CS-{{ str_pad($session->id, 4, '0', STR_PAD_LEFT) }}</title>
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

        .section-header {
            font-weight: bold;
            font-size: 9.5pt;
            text-transform: uppercase;
            border-bottom: 1px solid #000000;
            padding-bottom: 3px;
            margin-top: 10px;
            margin-bottom: 6px;
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
            padding: 4px 4px;
            vertical-align: middle;
        }
        .table-footer-line {
            border-bottom: 1px solid #000000;
        }

        .two-cols {
            width: 100%;
            border-collapse: collapse;
        }
        .two-cols td {
            vertical-align: top;
        }

        .signatures-table {
            width: 100%;
            margin-top: 35px;
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
                <div class="doc-title">REPORTE DE ARQUEO Y CIERRE DE CAJA</div>
                <div class="doc-subtitle">
                    Estado: {{ $session->closed_at ? 'SESIÓN CERRADA' : 'SESIÓN ABIERTA' }}
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
                <span class="lbl">Sucursal:</span> {{ $session->branch ?? 'Sucursal Leon' }}
            </td>
            <td style="width: 50%; text-align: right;">
                <span class="lbl" style="width:auto;">N° Sesión:</span> #CS-{{ str_pad($session->id, 4, '0', STR_PAD_LEFT) }}
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Cajero Apertura:</span> {{ $session->opener->username ?? '—' }} &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="lbl" style="width:auto;">Apertura:</span> {{ $session->opened_at ? $session->opened_at->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i A') : '—' }}
            </td>
            <td style="text-align: right;">
                <span class="lbl" style="width:auto;">Cierre:</span> {{ $session->closed_at ? $session->closed_at->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i A') : 'EN CURSO' }}
            </td>
        </tr>
        @if($session->closer)
        <tr>
            <td>
                <span class="lbl">Cajero Cierre:</span> {{ $session->closer->username }}
            </td>
            <td></td>
        </tr>
        @endif
    </table>

    <!-- Dos Columnas: Resumen Financiero y Desglose de Billetaje -->
    <table class="two-cols">
        <tr>
            <td style="width: 48%; padding-right: 2%;">
                <div class="section-header">Resumen de Movimientos</div>
                <table class="items-table">
                    <tr>
                        <td style="font-weight: bold;">Fondo Inicial de Apertura:</td>
                        <td class="text-right">C$ {{ number_format((float)$session->opening_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Retiros de Caja en el Turno:</td>
                        <td class="text-right">- C$ {{ number_format((float)$session->withdrawals->sum('total_amount'), 2) }}</td>
                    </tr>
                    <tr style="border-top: 1px solid #000000; background-color: #f8fafc;">
                        <td style="font-weight: bold;">Efectivo Esperado en Gaveta:</td>
                        <td class="text-right font-bold">C$ {{ number_format((float)($session->closingCount->expected_amount ?? 0), 2) }}</td>
                    </tr>
                    <tr style="background-color: #f8fafc;">
                        <td style="font-weight: bold;">Efectivo Contado Físicamente:</td>
                        <td class="text-right font-bold">C$ {{ number_format((float)($session->closingCount->counted_amount ?? 0), 2) }}</td>
                    </tr>
                    @php
                        $diff = (float)($session->closingCount->difference ?? 0);
                    @endphp
                    <tr style="border-top: 2px solid #000000; font-weight: bold;">
                        <td>DIFERENCIA DE CIERRE:</td>
                        <td class="text-right">
                            C$ {{ number_format($diff, 2) }}
                            ({{ $diff == 0 ? 'EXACTO' : ($diff < 0 ? 'FALTANTE' : 'SOBRANTE') }})
                        </td>
                    </tr>
                </table>

                @if($session->withdrawals->count() > 0)
                <div class="section-header" style="margin-top: 15px;">Detalle de Retiros</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Motivo / Justificación</th>
                            <th style="text-align: right;">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($session->withdrawals as $w)
                        <tr>
                            <td>{{ $w->reason ?? 'Retiro de caja' }}</td>
                            <td class="text-right font-bold">- C$ {{ number_format((float)$w->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </td>

            <td style="width: 48%; padding-left: 2%;">
                <div class="section-header">Desglose de Billetaje (Arqueo Físico)</div>
                @php
                    $denoms = is_string($session->closingCount->denominations ?? null)
                        ? json_decode($session->closingCount->denominations, true)
                        : ($session->closingCount->denominations ?? []);
                    $bills = [1000, 500, 200, 100, 50, 20, 10, 5, 1];
                @endphp
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="text-align: left;">Denominación</th>
                            <th style="text-align: center;">Cantidad</th>
                            <th style="text-align: right;">Subtotal (C$)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bills as $bill)
                            @php $qty = (int)($denoms[$bill] ?? 0); @endphp
                            <tr>
                                <td>Billetes / Monedas de C$ {{ number_format($bill, 0) }}</td>
                                <td class="text-center">{{ $qty }}</td>
                                <td class="text-right font-bold">C$ {{ number_format($bill * $qty, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-footer-line"><td colspan="3"></td></tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Firmas -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <strong>Cajero Entregante</strong><br>
                {{ $session->opener->username ?? '—' }}
            </td>
            <td>
                <div class="signature-line"></div>
                <strong>Administrador Receptor</strong><br>
                {{ $session->closer->username ?? 'Firma y Sello' }}
            </td>
        </tr>
    </table>

</body>
</html>
