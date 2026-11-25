<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas — Farmacia Solei</title>
    <style>
        @page {
            size: letter landscape;
            margin: 10mm 15mm 12mm 15mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
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
            margin-bottom: 10px;
            font-size: 9pt;
        }
        .meta-table td {
            padding: 2px 0;
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
            padding: 4px 4px;
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
                <div class="doc-title">REPORTE GENERAL DE VENTAS</div>
                <div class="doc-subtitle">Módulo de Punto de Venta &bull; Consolidado</div>
            </td>
            <td style="width: 25%; vertical-align: top;" class="print-date">
                Fecha impresión: {{ now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') }}
            </td>
        </tr>
    </table>

    <!-- Tabla de Ventas -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 12%; text-align: left;">N° Ticket</th>
                <th style="width: 18%; text-align: left;">Fecha y Hora</th>
                <th style="width: 18%; text-align: left;">Sucursal</th>
                <th style="width: 24%; text-align: left;">Cliente</th>
                <th style="width: 14%; text-align: center;">Método de Pago</th>
                <th style="width: 14%; text-align: right;">Total Venta</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @forelse($orders as $o)
                @php $total += (float)$o->total; @endphp
                <tr>
                    <td class="text-left">#FAC-{{ str_pad($o->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td class="text-left">{{ $o->created_at ? (new \DateTime($o->created_at))->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i A') : '—' }}</td>
                    <td class="text-left">{{ $o->branch ?? 'Sucursal Leon' }}</td>
                    <td class="text-left">{{ $o->client_name ?? 'Consumidor Final' }}</td>
                    <td class="text-center">{{ strtoupper($o->payment_method ?? $o->pay_method ?? 'EFECTIVO') }}</td>
                    <td class="text-right font-bold">C$ {{ number_format((float)$o->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 10px;">No hay ventas registradas en el período seleccionado.</td>
                </tr>
            @endforelse
            <tr class="table-footer-line"><td colspan="6"></td></tr>
        </tbody>
    </table>

    <!-- Resumen -->
    <table class="summary-table">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <strong>Total de Transacciones:</strong> &nbsp;&nbsp;&nbsp; <u>{{ count($orders) }}</u>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: right; font-weight: bold; padding-right: 15px;">TOTAL GENERAL DE VENTAS:</td>
                        <td style="text-align: right; width: 130px; font-weight: bold; font-size: 11pt;">C$ {{ number_format($total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
