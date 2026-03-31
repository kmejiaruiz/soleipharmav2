<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Pedido #<?= htmlspecialchars($order['id']) ?></title>
    <style>
        /* General Reset & Typography */
        @page { size: A4 portrait; margin: 15mm 15mm 20mm; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            margin: 0 auto;
        }

        /* Header Section */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #6f42c1;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .header-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }

        .company-name {
            font-size: 26px;
            font-weight: bold;
            color: #6f42c1;
            margin: 0 0 5px 0;
            letter-spacing: -0.5px;
        }
        
        .document-title {
            font-size: 22px;
            font-weight: bold;
            color: #495057;
            text-align: right;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .meta-info {
            font-size: 13px;
            color: #6c757d;
            line-height: 1.5;
        }

        .meta-info-right {
            text-align: right;
        }
        
        .highlight-text {
            color: #6f42c1;
            font-weight: bold;
        }

        /* Information Panels */
        .info-panel {
            width: 100%;
            margin-bottom: 25px;
        }
        
        .info-panel td {
            border: none;
            padding: 0;
        }
        
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #6f42c1;
            padding: 12px 15px;
            border-radius: 4px;
        }

        .info-box h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            color: #adb5bd;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #212529;
            font-weight: normal; 
            text-transform: capitalize;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #343a40;
            color: #ffffff;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            text-align: left;
            border: 1px solid #343a40;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            color: #495057;
            vertical-align: middle;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .sku-badge {
            background-color: #e9ecef;
            padding: 3px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 11px;
            color: #495057;
        }

        /* Totals Section */
        .totals-container {
            width: 100%;
        }
        
        .totals-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
        }
        
        .totals-table td {
            padding: 8px 12px;
            border: none;
            border-bottom: 1px solid #f1f1f1;
            font-size: 14px;
        }

        .totals-table .total-label {
            text-align: right;
            color: #6c757d;
        }

        .totals-table .total-value {
            text-align: right;
            font-weight: bold;
            color: #212529;
        }
        
        .totals-table .grand-total td {
            border-bottom: none;
            border-top: 2px solid #343a40;
            font-size: 18px;
            padding-top: 12px;
            color: #6f42c1;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 30px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: #adb5bd;
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
        }

        .clear { clear: both; }
    </style>
</head>
<body>

<div class="container">

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <h1 class="company-name">FARMACIA SOLEI</h1>
                <div class="meta-info">
                    Módulo de Pedidos<br>
                    <?= htmlspecialchars(BRANCH ?? 'Sucursal Principal') ?>
                </div>
            </td>
            <td style="width: 50%;" class="meta-info-right">
                <h2 class="document-title">Boleta de Pedido</h2>
                <div class="meta-info">
                    Nº de Pedido: <span class="highlight-text">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span><br>
                    Fecha Creación: <?= htmlspecialchars($orderDate) ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Info Panels -->
    <table class="info-panel">
        <tr>
            <td style="width: 48%; padding-right: 2%;">
                <div class="info-box">
                    <h4>Aplicado por</h4>
                    <p style="font-size: 15px; color: #495057; font-weight: normal;">
                        <?= htmlspecialchars(ucwords(strtolower(trim($order['admin_name'] ?? '—')))) ?>
                    </p>
                </div>
            </td>
            <td style="width: 48%; padding-left: 2%;">
                <div class="info-box">
                    <h4>Fecha de Aplicación (Aprobado)</h4>
                    <p style="font-size: 15px; color: #495057; font-weight: normal; text-transform:none;"><?= htmlspecialchars($appliedDate) ?></p>
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%;">SKU</th>
                <th style="width: 35%;">Descripción del Producto</th>
                <th style="width: 15%;" class="text-right">Costo Unit.</th>
                <th style="width: 15%;" class="text-center">Cant. Ord.</th>
                <th style="width: 20%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $it): ?>
                <tr>
                    <td><span class="sku-badge"><?= htmlspecialchars($it['sku'] ?? 'N/A') ?></span></td>
                    <td><strong><?= htmlspecialchars($it['name'] ?? '') ?></strong></td>
                    <td class="text-right">C$<?= number_format($it['cost'], 2) ?></td>
                    <td class="text-center"><b><?= intval($it['quantity']) ?></b></td>
                    <td class="text-right">C$<?= number_format($it['cost'] * $it['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals Section -->
    <div class="totals-container">
        <table class="totals-table">
            <tr>
                <td class="total-label">Subtotal Pedido:</td>
                <td class="total-value">C$<?= number_format($systemSubtotal, 2) ?></td>
            </tr>
            <tr>
                <td class="total-label">IVA (15%):</td>
                <td class="total-value">C$<?= number_format($systemTax, 2) ?></td>
            </tr>
            <tr class="grand-total">
                <td class="total-label">Total Pedido:</td>
                <td class="total-value">C$<?= number_format($systemTotal, 2) ?></td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>

</div>

<!-- Footer -->
<div class="footer">
    Documento generado automáticamente por el Sistema Farmacia Solei &bull; Válido como comprobante interno de proyección de pedido.<br>
    Página 1 de 1
</div>

</body>
</html>
