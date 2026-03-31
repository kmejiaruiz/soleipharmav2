<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Débito #<?= htmlspecialchars($entryId ?? '') ?></title>
    <style>
        @page { size: A4 portrait; margin: 15mm 15mm 20mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; color: #000; margin: 0; padding: 20px; }
        
        .header-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .header-table td { vertical-align: top; padding: 2px 5px; }
        
        .company-name { font-size: 18px; font-weight: bold; text-transform: uppercase; }
        
        .doc-title { text-align: center; }
        .doc-title h2 { font-size: 18px; margin: 0; font-weight: bold; }
        
        .print-date { text-align: right; font-size: 13px; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td { padding: 4px 5px; vertical-align: top; font-size: 13px; }
        .info-table .label { font-weight: bold; width: 220px; }
        
        .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px; }
        .items-table th { border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 7px; text-align: left; font-weight: bold; font-size: 13px; }
        .items-table td { padding: 7px; vertical-align: middle; border: none; font-size: 13px; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .total-row td { border-top: 1px solid #000; border-bottom: 1px solid #000; font-weight: bold; padding-top: 9px; padding-bottom: 9px; }
        
        .signatures { width: 100%; margin-top: 60px; border-collapse: collapse; }
        .signatures td { width: 50%; text-align: center; vertical-align: bottom; height: 60px; }
        .sign-line { display: inline-block; width: 80%; border-top: 1px solid #000; padding-top: 5px; font-weight: bold; font-size: 13px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 33%;"><div class="company-name"><?= htmlspecialchars($company ?? 'FARMACIA SOLEI') ?></div></td>
            <td style="width: 34%;" class="doc-title"><h2>Nota de Débito</h2></td>
            <td style="width: 33%;" class="print-date">Fecha impresión: <?= date('d/m/Y h:i:s A') ?></td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td class="label">Autorización Fiscal de Sistema de Facturación:</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td class="label">Razón Social:</td>
            <td colspan="3"><?= htmlspecialchars($company ?? 'FARMACIA SOLEI') ?></td>
        </tr>
        <tr>
            <td class="label">RUC:</td>
            <td colspan="3">N/A</td>
        </tr>
        <tr>
            <td class="label">Número:</td>
            <td style="width: 250px;">ND-<?= str_pad($entryId, 6, '0', STR_PAD_LEFT) ?></td>
            <td class="label" style="width: 100px;">Código Contable:</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td class="label">Sucursal:</td>
            <td colspan="3"><?= htmlspecialchars($branch ?? '') ?></td>
        </tr>
        <tr>
            <td class="label">Proveedor:</td>
            <td colspan="3"><?= htmlspecialchars($entry['supplier_name'] ?? 'Sin proveedor') ?></td>
        </tr>
        <tr>
            <td class="label">Fecha de Nota de Débito:</td>
            <td colspan="3"><?= htmlspecialchars($dateTime ?? '') ?></td>
        </tr>
        <tr>
            <td class="label">Número de Pedido Orig:</td>
            <td colspan="3">#<?= htmlspecialchars($entry['order_id'] ?? '') ?></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">Descripción</th>
                <th class="text-right" style="width: 20%;">Monto Total Factura</th>
                <th class="text-right" style="width: 20%;">Monto Total Sistema</th>
                <th class="text-right" style="width: 20%;">Monto Nota Débito</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($hasCostSection) && isset($costDiff)): ?>
            <tr>
                <td>Diferencia en Costos</td>
                <td class="text-right"><?= number_format($invoiceTotal ?? 0, 2) ?></td>
                <td class="text-right"><?= number_format($systemTotal ?? 0, 2) ?></td>
                <td class="text-right"><?= number_format($costDiff, 2) ?></td>
            </tr>
            <?php endif; ?>
            
            <?php if (!empty($hasQtySection) && !empty($excessItems)): ?>
            <tr>
                <td colspan="4" style="font-weight: bold; border-top: 1px dashed #ccc; padding-top: 8px; padding-bottom: 5px;">Diferencia por Cantidades (Excesos recibidos)</td>
            </tr>
                <?php foreach ($excessItems as $it): ?>
                <tr>
                    <td style="padding-left: 10px;">- <?= htmlspecialchars($it['name']) ?> (Exc: <?= $it['excess'] ?>)</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right"><?= number_format($it['excess_total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <tr class="total-row">
                <td colspan="3" class="text-right" style="padding-right: 20px;">Total:</td>
                <td class="text-right"><?= number_format($grandTotal ?? 0, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <span class="sign-line">Firma y sello del responsable de la tienda</span>
            </td>
            <td>
                <span class="sign-line">Firma y sello del proveedor</span>
            </td>
        </tr>
    </table>

</body>
</html>
