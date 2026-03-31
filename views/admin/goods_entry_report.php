<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Boleta de Recepción #<?= htmlspecialchars($entry['entry_id']) ?></title>
    <style>
        @page { size: A4 landscape; margin: 12mm 15mm 15mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000; margin: 0; padding: 20px; }
        .company-name { font-size: 18px; font-weight: bold; margin-bottom: 20px; text-transform: uppercase; }
        .title-center { text-align: center; }
        .title-center h2 { font-size: 22px; margin: 0 0 5px 0; text-transform: uppercase; font-weight: bold; }
        .title-center p  { margin: 0; font-size: 15px; font-weight: bold; }
        
        .header-info-table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px; }
        .header-info-table td { padding: 5px 6px; vertical-align: top; font-size: 14px; }
        .header-info-table td.label { font-weight: bold; width: 110px; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
        .items-table th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 7px; text-align: left; font-weight: bold; font-size: 13px; }
        .items-table td { padding: 7px; vertical-align: middle; border: none; font-size: 13px; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .summary-wrapper { width: 100%; margin-top: 10px; border-top: 1px solid #000; padding-top: 5px; display: table; }
        .summary-left  { display: table-cell; width: 60%; vertical-align: top; font-size: 13px; }
        .summary-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
        
        .summary-table { width: 100%; float: right; border-collapse: collapse; }
        .summary-table td { padding: 5px 6px; text-align: right; font-size: 13px; }
        .summary-table td.label { font-weight: bold; }
    </style>
</head>
<body>

    <div class="company-name"><?= defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) : 'FARMACIA SOLEI' ?></div>
    
    <div class="title-center">
        <h2>BOLETA DE RECEPCIÓN</h2>
        <p>Estado: <?= htmlspecialchars($orderStatus ?? 'APLICADO') ?></p>
    </div>

    <table class="header-info-table">
        <tr>
            <td class="label">Sucursal:</td>
            <td style="width: 250px;"><?= htmlspecialchars(defined('BRANCH') ? BRANCH : 'Sucursal Principal') ?></td>
            <td class="label" style="text-align: right;">Fecha impresión:</td>
            <td style="text-align: right;"><?= date('d/m/Y h:i:s A') ?></td>
        </tr>
        <tr>
            <td class="label">Boleta:</td>
            <td><?= str_pad($entry['entry_id'], 3, '0', STR_PAD_LEFT) ?> &nbsp;&nbsp;&nbsp; <b>Fecha Boleta:</b> <?= htmlspecialchars($receptionDate) ?></td>
            <td class="label" style="text-align: right;">Proveedor:</td>
            <td style="text-align: right;"><?= htmlspecialchars($entry['supplier_name'] ?? 'Sin proveedor') ?></td>
        </tr>
        <tr>
            <td class="label">Usuario Aplica:</td>
            <td style="text-transform: capitalize;"><?= htmlspecialchars(strtolower($appliedUser)) ?></td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td class="label">Pedido Orig:</td>
            <td>#<?= str_pad($entry['order_id'], 3, '0', STR_PAD_LEFT) ?></td>
            <td colspan="2"></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%;">Código</th>
                <th style="width: 35%;">Nombre</th>
                <th class="text-center" style="width: 10%;">C. Fact</th>
                <th class="text-center" style="width: 10%;">C. Scan</th>
                <th class="text-center" style="width: 10%;">C. Ent</th>
                <th class="text-right" style="width: 10%;">Costo Unit.</th>
                <th class="text-right" style="width: 10%;">Total Sistema</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $countLines = count($orderItems);
                foreach ($orderItems as $it): 
                    $ordered = intval($it['ordered_qty']);
                    $received = intval($it['received_qty']);
                    $lineTotal = floatval($it['cost_unit']) * $received;
            ?>
            <tr>
                <td><?= htmlspecialchars($it['sku'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($it['name'] ?? '') ?></td>
                <td class="text-center"><?= number_format($ordered, 3) ?></td>
                <td class="text-center"><?= number_format($received, 3) ?></td>
                <td class="text-center"><?= number_format($received, 3) ?></td>
                <td class="text-right"><?= number_format($it['cost_unit'], 2) ?></td>
                <td class="text-right"><?= number_format($lineTotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary-wrapper">
        <div class="summary-left">
            Total de Líneas: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <u style="font-weight:bold;"><?= $countLines ?></u><br>
        </div>
        <div class="summary-right">
            <table class="summary-table">
                <tr>
                    <td class="label">Total Factura Física</td>
                    <td style="width: 80px;"><?= number_format($invoiceTotal, 2) ?></td>
                </tr>
                <tr>
                    <td class="label">Total Entrada (Sistema)</td>
                    <td><?= number_format($systemTotal, 2) ?></td>
                </tr>
            </table>
        </div>
    </div>
    
</body>
</html>