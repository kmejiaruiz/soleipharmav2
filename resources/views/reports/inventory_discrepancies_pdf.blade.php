<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Discrepancias de Inventario — {{ $count->count_number }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 10mm 12mm 12mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #111111;
            line-height: 1.3;
            margin: 0;
            padding: 0;
        }
        .top-header {
            width: 100%;
            margin-bottom: 8px;
        }
        .brand-name {
            font-size: 14pt;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
        }
        .brand-sub {
            font-size: 8pt;
            color: #555555;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            margin: 0;
            color: #991b1b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-subtitle {
            font-size: 9pt;
            font-weight: bold;
            text-align: center;
            margin-top: 2px;
            color: #333333;
        }
        .count-badge {
            display: inline-block;
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 9pt;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8.5pt;
            background-color: #fafafa;
            border: 1px solid #d0d0d0;
        }
        .meta-table td {
            padding: 4px 8px;
            vertical-align: middle;
        }
        .lbl {
            font-weight: bold;
            color: #444444;
        }

        /* KPIs de discrepancia */
        .kpi-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .kpi-box {
            border: 1px solid #d1d5db;
            padding: 6px 10px;
            text-align: center;
            border-radius: 4px;
        }
        .kpi-faltante {
            background-color: #fef2f2;
            border-color: #fca5a5;
            color: #991b1b;
        }
        .kpi-sobrante {
            background-color: #f0fdf4;
            border-color: #86efac;
            color: #166534;
        }
        .kpi-neto {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        .items-table th {
            background-color: #f3f4f6;
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            padding: 5px 4px;
            font-weight: bold;
            font-size: 8pt;
        }
        .items-table td {
            padding: 5px 4px;
            vertical-align: middle;
            border-bottom: 1px solid #e5e5e5;
        }
        .diff-negative {
            color: #b91c1c;
            font-weight: bold;
        }
        .diff-positive {
            color: #15803d;
            font-weight: bold;
        }
        .table-footer-line td {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            font-weight: bold;
            background-color: #f9fafb;
        }

        .signatures-table {
            width: 100%;
            margin-top: 35px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
            font-size: 8pt;
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

    <!-- Encabezado Oficial -->
    <table class="top-header">
        <tr>
            <td style="width: 28%; vertical-align: top;">
                <div class="brand-name">FARMACIA SOLEI</div>
                <div class="brand-sub">Control y Auditoría de Inventarios</div>
            </td>
            <td style="width: 44%; text-align: center; vertical-align: top;">
                <div class="doc-title">ACTA DE DISCREPANCIAS Y AJUSTE DE INVENTARIO</div>
                <div class="doc-subtitle">REPORTE VALORIZADO DE DIFERENCIAS FÍSICAS VS SISTEMA</div>
            </td>
            <td style="width: 28%; text-align: right; vertical-align: top;">
                <span class="count-badge">No. {{ $count->count_number }}</span><br>
                <span style="font-size: 8pt; color: #444;">
                    <strong>Fecha:</strong> {{ now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i A') }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Metadatos de la Auditoría -->
    <table class="meta-table">
        <tr>
            <td style="width: 33%;">
                <span class="lbl">Sucursal / Bodega:</span>
                <strong>{{ $count->branch }}</strong>
            </td>
            <td style="width: 33%;">
                <span class="lbl">Estado de Conteo:</span>
                <strong style="text-transform: uppercase; color: {{ $count->status === 'aplicado' ? '#166534' : '#b45309' }}">
                    {{ $count->status === 'aplicado' ? 'APLICADO EN BODEGA' : 'PRELIMINAR / BORRADOR' }}
                </strong>
            </td>
            <td style="width: 33%;">
                <span class="lbl">Categoría:</span>
                <strong>{{ $count->category ? $count->category->name : 'Todas las Categorías' }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Conteo realizado por:</span>
                {{ $count->creator->name ?? ($count->creator->username ?? 'Responsable Bodega') }}
            </td>
            <td>
                <span class="lbl">Ajuste Aplicado por:</span>
                {{ $count->appliedBy->name ?? ($count->appliedBy->username ?? 'Pendiente de Aplicación') }}
            </td>
            <td>
                <span class="lbl">Fecha Aplicación:</span>
                {{ $count->applied_at ? $count->applied_at->format('d/m/Y h:i A') : 'Sin aplicar' }}
            </td>
        </tr>
        @if($count->notes)
        <tr>
            <td colspan="3">
                <span class="lbl">Observaciones / Motivo:</span>
                <span>{{ $count->notes }}</span>
            </td>
        </tr>
        @endif
    </table>

    <!-- Resumen Económico de Diferencias (KPIs) -->
    <table class="kpi-table">
        <tr>
            <td style="width: 33%; padding-right: 6px;">
                <div class="kpi-box kpi-faltante">
                    <span style="font-size: 7.5pt; text-transform: uppercase; font-weight: bold;">Faltantes (Pérdida / Descuadre Negativo)</span><br>
                    <strong style="font-size: 11pt;">{{ $kpis['faltantes_units'] }} uds.</strong> &nbsp;|&nbsp;
                    <strong style="font-size: 11pt;">- C$ {{ number_format(abs($kpis['faltantes_cost']), 2) }}</strong>
                </div>
            </td>
            <td style="width: 33%; padding: 0 3px;">
                <div class="kpi-box kpi-sobrante">
                    <span style="font-size: 7.5pt; text-transform: uppercase; font-weight: bold;">Sobrantes (Excedente Positivo)</span><br>
                    <strong style="font-size: 11pt;">+{{ $kpis['sobrantes_units'] }} uds.</strong> &nbsp;|&nbsp;
                    <strong style="font-size: 11pt;">+ C$ {{ number_format($kpis['sobrantes_cost'], 2) }}</strong>
                </div>
            </td>
            <td style="width: 33%; padding-left: 6px;">
                <div class="kpi-box kpi-neto">
                    <span style="font-size: 7.5pt; text-transform: uppercase; font-weight: bold;">Impacto Neto Valorizado</span><br>
                    <strong style="font-size: 11pt; color: {{ $kpis['net_cost'] < 0 ? '#b91c1c' : ($kpis['net_cost'] > 0 ? '#15803d' : '#333333') }};">
                        {{ $kpis['net_cost'] < 0 ? '- C$ ' . number_format(abs($kpis['net_cost']), 2) : ($kpis['net_cost'] > 0 ? '+ C$ ' . number_format($kpis['net_cost'], 2) : 'C$ 0.00 (Cuadrado)') }}
                    </strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- Tabla Detallada de Discrepancias -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 3%; text-align: center;">#</th>
                <th style="width: 14%; text-align: left;">Código / SKU</th>
                <th style="width: 25%; text-align: left;">Producto</th>
                <th style="width: 18%; text-align: left;">Categoría</th>
                <th style="width: 8%; text-align: center;">Exist. Sist.</th>
                <th style="width: 8%; text-align: center;">Físico Real</th>
                <th style="width: 8%; text-align: center;">Diferencia</th>
                <th style="width: 10%; text-align: right;">Costo Unit.</th>
                <th style="width: 11%; text-align: right;">Impacto Costo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($discrepancies as $idx => $item)
                @php
                    $prod = $item->product;
                    $diff = (int)$item->difference;
                    $diffCost = (float)$item->total_cost_diff;
                @endphp
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left font-mono">{{ $prod->barcode ?? ($prod->sku ?? '—') }}</td>
                    <td class="text-left font-bold">{{ $prod->name }}</td>
                    <td class="text-left font-semibold" style="color: #333;">
                        {{ $prod->category ? $prod->category->name : 'General' }}
                    </td>
                    <td class="text-center font-bold">{{ number_format($item->system_stock) }}</td>
                    <td class="text-center font-bold">{{ number_format($item->counted_stock) }}</td>
                    <td class="text-center {{ $diff < 0 ? 'diff-negative' : 'diff-positive' }}">
                        {{ $diff > 0 ? '+' . $diff : $diff }}
                    </td>
                    <td class="text-right">C$ {{ number_format($item->unit_cost, 2) }}</td>
                    <td class="text-right {{ $diffCost < 0 ? 'diff-negative' : 'diff-positive' }}">
                        {{ $diffCost < 0 ? '- C$ ' . number_format(abs($diffCost), 2) : '+ C$ ' . number_format($diffCost, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #15803d; font-weight: bold;">
                        ✓ ¡EXCELENTE! No se registraron discrepancias en el conteo. El inventario físico coincide al 100% con el sistema.
                    </td>
                </tr>
            @endforelse
            <tr class="table-footer-line">
                <td colspan="4" class="text-right"><strong>TOTAL DISCREPANCIAS:</strong></td>
                <td class="text-center"><strong>{{ number_format($kpis['total_system']) }}</strong></td>
                <td class="text-center"><strong>{{ number_format($kpis['total_counted']) }}</strong></td>
                <td class="text-center font-bold {{ ($kpis['total_counted'] - $kpis['total_system']) < 0 ? 'diff-negative' : 'diff-positive' }}">
                    {{ ($kpis['total_counted'] - $kpis['total_system']) > 0 ? '+' . ($kpis['total_counted'] - $kpis['total_system']) : ($kpis['total_counted'] - $kpis['total_system']) }}
                </td>
                <td class="text-right"></td>
                <td class="text-right font-bold {{ $kpis['net_cost'] < 0 ? 'diff-negative' : 'diff-positive' }}">
                    {{ $kpis['net_cost'] < 0 ? '- C$ ' . number_format(abs($kpis['net_cost']), 2) : '+ C$ ' . number_format($kpis['net_cost'], 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 7.5pt; color: #666; margin-top: 4px;">
        * Nota de auditoría: Las diferencias negativas representan mermas o pérdidas que requieren investigación. Las diferencias positivas representan excedentes o entradas no registradas.
    </div>

    <!-- Firmas de Responsabilidad -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <strong>{{ $count->creator->name ?? ($count->creator->username ?? 'Responsable de Conteo') }}</strong><br>
                <span>Responsable de Bodega / Conteo</span>
            </td>
            <td>
                <div class="signature-line"></div>
                <strong>Auditor de Inventarios</strong><br>
                <span>Verificación y Control Interno</span>
            </td>
            <td>
                <div class="signature-line"></div>
                <strong>Administración / Gerencia</strong><br>
                <span>Autorización y Aplicación Contable</span>
            </td>
        </tr>
    </table>

</body>
</html>
