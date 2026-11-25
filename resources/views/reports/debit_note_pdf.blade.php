<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota_Debito_#ND-{{ str_pad($debitNote->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 12mm 15mm 15mm 15mm;
            size: letter portrait;  /* Vertical como en Imagen 1 */
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        /* ── Encabezado Imagen 1 ───────────────────────────────────────────── */
        .top-header {
            width: 100%;
            margin-bottom: 20px;
        }
        .brand-name {
            font-size: 14pt;
            font-weight: bold;
            color: #000000;
        }
        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .print-date {
            font-size: 8.5pt;
            text-align: right;
            color: #000000;
        }

        /* ── Grid Metadatos Imagen 1 ──────────────────────────────────────── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }
        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .lbl-bold {
            font-weight: bold;
            display: inline-block;
        }

        /* ── Tabla de Detalles Imagen 1 ───────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }
        .items-table th {
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            padding: 6px 4px;
            font-weight: bold;
            font-size: 9pt;
        }
        .items-table td {
            padding: 5px 4px;
            vertical-align: middle;
        }
        .group-header {
            font-weight: bold;
            padding-top: 8px;
            padding-bottom: 4px;
        }

        /* ── Total Imagen 1 ────────────────────────────────────────────────── */
        .total-row-top {
            border-top: 1px solid #000000;
        }
        .total-row-bottom {
            border-bottom: 2px double #000000;
        }

        /* ── Firmas Imagen 1 ──────────────────────────────────────────────── */
        .signatures-table {
            width: 100%;
            margin-top: 60px;
            border-collapse: collapse;
        }
        .signature-cell {
            width: 45%;
            text-align: center;
            vertical-align: top;
        }
        .signature-line {
            border-top: 1px solid #000000;
            width: 80%;
            margin: 0 auto 5px auto;
        }
    </style>
</head>
<body>

    <!-- Encabezado exacto a Imagen 1 -->
    <table class="top-header">
        <tr>
            <td style="width: 30%; vertical-align: top;">
                <div class="brand-name">FARMACIA SOLEI</div>
            </td>
            <td style="width: 40%; text-align: center; vertical-align: top;">
                <div class="doc-title">Nota de Débito</div>
            </td>
            <td style="width: 30%; vertical-align: top;" class="print-date">
                Fecha impresión: {{ now()->format('d/m/Y h:i:s A') }}
            </td>
        </tr>
    </table>

    <!-- Metadatos exactos a Imagen 1 -->
    <table class="meta-table">
        <tr>
            <td style="width: 200px;" class="lbl-bold">Autorización Fiscal de Sistema de Facturación:</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td class="lbl-bold">Razón Social:</td>
            <td>Farmacia Solei</td>
        </tr>
        <tr>
            <td class="lbl-bold">RUC:</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td class="lbl-bold">Número:</td>
            <td>
                ND-{{ str_pad($debitNote->id, 6, '0', STR_PAD_LEFT) }}
                <span style="float: right; margin-right: 50px;">
                    <span class="lbl-bold">Código Contable:</span> &nbsp;&nbsp;&nbsp;&nbsp; N/A
                </span>
            </td>
        </tr>
        <tr>
            <td class="lbl-bold">Sucursal:</td>
            <td>{{ $debitNote->goodsEntry->branch ?? 'Sucursal Leon' }}</td>
        </tr>
        <tr>
            <td class="lbl-bold">Proveedor:</td>
            <td>{{ $debitNote->supplier->name ?? 'INFARSA S.A.' }}</td>
        </tr>
        <tr>
            <td class="lbl-bold">Fecha de Nota de Débito:</td>
            <td>{{ $debitNote->created_at ? $debitNote->created_at->format('d/m/Y h:i:s A') : now()->format('d/m/Y h:i:s A') }}</td>
        </tr>
        <tr>
            <td class="lbl-bold">Número de Pedido Orig:</td>
            <td>#{{ $debitNote->order_id }}</td>
        </tr>
    </table>

    <!-- Tabla de Detalles exactos a Imagen 1 -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%; text-align: left;">Descripción</th>
                <th style="width: 18%; text-align: right;">Monto Total Factura</th>
                <th style="width: 18%; text-align: right;">Monto Total Sistema</th>
                <th style="width: 19%; text-align: right;">Monto Nota Débito</th>
            </tr>
        </thead>
        <tbody>
            @if($debitNote->cost_difference > 0)
                <tr>
                    <td colspan="4" class="group-header">Diferencia por Costos (Factura superior a sistema)</td>
                </tr>
                <tr>
                    <td style="padding-left: 15px;">- Ajuste de costo factura física vs sistema</td>
                    <td style="text-align: right;">{{ number_format((float)$debitNote->goodsEntry->invoice_subtotal + $debitNote->goodsEntry->invoice_tax, 2) }}</td>
                    <td style="text-align: right;">{{ number_format((float)$debitNote->goodsEntry->system_total, 2) }}</td>
                    <td style="text-align: right;">{{ number_format((float)$debitNote->cost_difference, 2) }}</td>
                </tr>
            @endif

            @if($debitNote->details && is_array($debitNote->details) && count($debitNote->details))
                <tr>
                    <td colspan="4" class="group-header">Diferencia por Cantidades (Excesos recibidos)</td>
                </tr>
                @foreach($debitNote->details as $d)
                    <tr>
                        <td style="padding-left: 15px;">- {{ $d['product_name'] ?? 'Producto' }} (Exc: {{ $d['excess_qty'] ?? 1 }})</td>
                        <td style="text-align: right;">-</td>
                        <td style="text-align: right;">-</td>
                        <td style="text-align: right;">{{ number_format((float)($d['extra_cost_iva'] ?? $d['extra_cost'] ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            @elseif($debitNote->qty_extra_cost > 0 && !$debitNote->cost_difference)
                <tr>
                    <td colspan="4" class="group-header">Diferencia por Cantidades (Excesos recibidos)</td>
                </tr>
                <tr>
                    <td style="padding-left: 15px;">- Exceso de unidades recibidas sobre el pedido</td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right;">-</td>
                    <td style="text-align: right;">{{ number_format((float)$debitNote->qty_extra_cost, 2) }}</td>
                </tr>
            @endif

            <!-- Línea de Total exacto a Imagen 1 -->
            <tr class="total-row-top total-row-bottom">
                <td colspan="3" style="text-align: right; font-weight: bold; padding: 6px 15px 6px 0;">Total:</td>
                <td style="text-align: right; font-weight: bold; padding: 6px 4px;">{{ number_format((float)$debitNote->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Firmas exactas a Imagen 1 -->
    <table class="signatures-table">
        <tr>
            <td class="signature-cell">
                <div class="signature-line"></div>
                <strong>Firma y sello del responsable de la tienda</strong>
            </td>
            <td style="width: 10%;"></td>
            <td class="signature-cell">
                <div class="signature-line"></div>
                <strong>Firma y sello del proveedor</strong>
            </td>
        </tr>
    </table>

</body>
</html>
