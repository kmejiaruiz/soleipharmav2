<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Toma Física de Inventario — Farmacia Solei</title>
    <style>
        @page {
            size: letter landscape;
            margin: 10mm 12mm 12mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #000000;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .top-header {
            width: 100%;
            margin-bottom: 10px;
        }
        .brand-name {
            font-size: 15pt;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .brand-sub {
            font-size: 8.5pt;
            color: #555555;
        }
        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            text-align: center;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .doc-subtitle {
            font-size: 9.5pt;
            font-weight: bold;
            text-align: center;
            margin-top: 2px;
            color: #444444;
        }
        .print-date {
            font-size: 8.5pt;
            text-align: right;
            color: #444444;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 9pt;
            background-color: #fafafa;
            border: 1px solid #c0c0c0;
        }
        .meta-table td {
            padding: 6px 12px;
            vertical-align: middle;
        }
        .lbl {
            font-weight: bold;
            color: #333333;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 9pt;
        }
        .items-table th {
            background-color: #f2f2f2;
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            padding: 8px 6px;
            font-weight: bold;
            font-size: 8.5pt;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 8px 6px;
            vertical-align: middle;
            border-bottom: 1px solid #dcdcdc;
        }
        .blank-box {
            border: 1.5px dashed #444444 !important;
            background-color: #ffffff;
            height: 38px;
        }
        .table-footer-line td {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            font-weight: bold;
            background-color: #f9f9f9;
            padding: 9px 6px;
        }

        .signatures-table {
            width: 100%;
            margin-top: 45px;
            border-collapse: collapse;
        }
        .signatures-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 25px;
            font-size: 8.5pt;
        }
        .signature-line {
            border-top: 1px solid #000000;
            margin-bottom: 5px;
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
                <div class="brand-sub">Control de Inventarios & Bodega</div>
            </td>
            <td style="width: 44%; text-align: center; vertical-align: top;">
                <div class="doc-title">HOJA DE TOMA FÍSICA DE INVENTARIO</div>
                <div class="doc-subtitle">CONTEO CÍCLICO Y VERIFICACIÓN EN BODEGA</div>
            </td>
            <td style="width: 28%; vertical-align: top;" class="print-date">
                <strong>Fecha Emisión:</strong><br>
                {{ now()->setTimezone(new \DateTimeZone('America/Managua'))->format('d/m/Y h:i:s A') }}
            </td>
        </tr>
    </table>

    <!-- Metadatos de la Toma Física -->
    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <span class="lbl">Sucursal / Bodega:</span>
                <strong>{{ $branch && $branch !== 'all' ? $branch : 'Todas las Sucursales' }}</strong>
            </td>
            <td style="width: 50%;">
                <span class="lbl">Categoría:</span>
                <strong>{{ $category ? $category->name : 'Todas las Categorías' }}</strong>
            </td>
        </tr>
        <tr>
            <td>
                <span class="lbl">Proveedor:</span>
                <span>{{ $supplier ? $supplier->name : 'Todos los Proveedores' }}</span>
            </td>
            <td>
                <span class="lbl">Generado por:</span>
                <span>{{ auth()->user()->username ?? 'Administración' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="lbl">Filtro de Toma Física:</span>
                <span>{{ ($eligibility ?? 'eligible') === 'eligible' ? 'Solo artículos habilitados para conteo (≥ 10 ventas tras último conteo aplicado)' : (($eligibility ?? '') === 'locked' ? 'Artículos en espera de ventas (< 10 ventas)' : 'Todos los artículos') }}</span>
            </td>
        </tr>
    </table>

    <!-- Tabla de Artículos para Conteo Físico -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">#</th>
                <th style="width: 14%; text-align: left;">Código / SKU</th>
                <th style="width: 28%; text-align: left;">Descripción del Producto</th>
                <th style="width: 22%; text-align: left;">Categoría</th>
                <th style="width: 10%; text-align: center;">Exist. Sistema</th>
                <th style="width: 22%; text-align: center;">Conteo Físico</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $totalSistema = 0;
            @endphp
            @forelse($products as $idx => $p)
                @php 
                    $stock = (int)$p->stock;
                    $totalSistema += $stock;
                @endphp
                <tr>
                    <td class="text-center" style="color: #666;">{{ $idx + 1 }}</td>
                    <td class="text-left font-mono" style="font-weight: 500;">{{ $p->barcode ?? ($p->sku ?? '—') }}</td>
                    <td class="text-left font-bold" style="font-size: 9.5pt; color: #111;">{{ $p->name }}</td>
                    <td class="text-left font-bold" style="font-size: 9pt; color: #333;">
                        {{ $p->category ? $p->category->name : 'General' }}
                    </td>
                    <td class="text-center font-bold font-mono" style="font-size: 10pt;">
                        {{ number_format($stock) }}
                    </td>
                    @if(isset($p->can_count) && !$p->can_count)
                        <td class="text-center" style="background-color: #f5f5f5; color: #888; font-size: 8pt; font-weight: bold; border: 1px solid #ddd;">
                            BLOQUEADO ({{ $p->sales_since_last_count ?? 0 }}/10 ventas)
                        </td>
                    @else
                        <td class="blank-box">&nbsp;</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 24px; color: #777;">
                        No se encontraron productos registrados para los parámetros seleccionados.
                    </td>
                </tr>
            @endforelse
            <tr class="table-footer-line">
                <td colspan="4" class="text-right"><strong>TOTAL UNIDADES SISTEMA:</strong></td>
                <td class="text-center font-mono" style="font-size: 10.5pt;"><strong>{{ number_format($totalSistema) }}</strong></td>
                <td class="text-center" style="font-size: 8.5pt; color: #555;">(Total a verificar)</td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 8.5pt; color: #555; margin-top: 4px; text-align: right;">
        Total de productos listados: <strong>{{ count($products) }}</strong>
    </div>

    <!-- Bloque de Firmas -->
    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-line"></div>
                <strong>Responsable de Conteo</strong><br>
                <span>Firma / Nombre Bodeguero</span>
            </td>
            <td>
                <div class="signature-line"></div>
                <strong>Verificado por</strong><br>
                <span>Firma Auditor / Supervisor</span>
            </td>
            <td>
                <div class="signature-line"></div>
                <strong>Autorizado por</strong><br>
                <span>Firma Administración</span>
            </td>
        </tr>
    </table>

</body>
</html>
