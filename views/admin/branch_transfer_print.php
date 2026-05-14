<?php
// views/admin/branch_transfer_print.php
// Renderizado vía ob_start() → DomPDF → PDF inline
// Variables: $transfer, $items, $creator, $receiver
$transferNum  = str_pad($transfer['id'], 4, '0', STR_PAD_LEFT);
$isReceived   = $transfer['status'] === 'recibido';

$totalSentQty   = 0; $totalRecvQty   = 0;
$totalSentValue = 0.0; $totalRecvValue = 0.0;
foreach ($items as $item) {
    $cost            = floatval($item['unit_cost'] ?? 0);
    $totalSentQty   += intval($item['quantity_sent']);
    $totalSentValue += intval($item['quantity_sent']) * $cost;
    if ($isReceived) {
        $r = intval($item['quantity_received'] ?? 0);
        $totalRecvQty   += $r;
        $totalRecvValue += $r * $cost;
    }
}
$statusLabel = ['pendiente' => 'PENDIENTE', 'recibido' => 'RECIBIDO', 'cancelado' => 'CANCELADO'][$transfer['status']] ?? strtoupper($transfer['status']);
$company     = defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) : 'FARMACIA SOLEI';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Boleta BT-<?= $transferNum ?></title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10pt;
        color: #000;
        background: #fff;
        padding: 22pt 26pt 22pt 26pt;
    }

    /* ── Encabezado ── */
    .company-name {
        font-size: 15pt;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 2pt;
    }
    .company-sub {
        font-size: 8.5pt;
        color: #555;
        margin-bottom: 11pt;
    }

    /* ── Título ── */
    .title-box {
        text-align: center;
        border-top: 1.5pt solid #000;
        border-bottom: 1pt solid #000;
        padding: 6pt 0;
        margin-bottom: 11pt;
    }
    .title-box h2 {
        font-size: 14pt;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 2pt;
    }
    .title-box p { font-size: 9.5pt; font-weight: bold; }

    /* ── Metadatos ── */
    .meta-tbl { width: 100%; border-collapse: collapse; margin-bottom: 11pt; }
    .meta-tbl td { padding: 2.5pt 4pt; font-size: 9.5pt; vertical-align: top; }
    .meta-tbl td.lbl { font-weight: bold; width: 90pt; white-space: nowrap; }
    .meta-tbl td.sep { width: 12pt; }
    .meta-tbl td.tr  { text-align: right; }

    /* ── Tabla ítems ── */
    .items-tbl { width: 100%; border-collapse: collapse; margin-bottom: 11pt; }
    .items-tbl thead th {
        border-top: 1pt solid #000;
        border-bottom: 1pt solid #000;
        padding: 4pt 5pt;
        font-size: 9pt;
        font-weight: bold;
        text-align: left;
    }
    .items-tbl tbody td {
        padding: 4pt 5pt;
        font-size: 9pt;
        vertical-align: middle;
    }
    .items-tbl tbody tr.even td { background-color: #f2f2f2; }
    .items-tbl tfoot td {
        border-top: 1pt solid #000;
        padding: 4pt 5pt;
        font-size: 9pt;
        font-weight: bold;
    }

    /* ── Resumen ── */
    .sum-wrap  { width: 100%; margin-top: 4pt; padding-top: 6pt; border-top: 1pt solid #000; }
    .sum-left  { width: 52%; float: left; font-size: 9pt; line-height: 1.7; }
    .sum-right { width: 48%; float: right; text-align: right; }
    .sum-tbl   { width: 100%; border-collapse: collapse; }
    .sum-tbl td { padding: 2.5pt 4pt; font-size: 9pt; text-align: right; }
    .sum-tbl td.lbl { font-weight: bold; }
    .clearfix  { clear: both; }

    /* ── Firmas ── */
    .sigs-table  { width: 100%; border-collapse: collapse; margin-top: 24pt; }
    .sigs-table td { width: 50%; text-align: center; padding: 0 12pt;
                     vertical-align: bottom; min-height: 36pt; height: 36pt; }
    .sig-line    { border-top: 1pt solid #555; padding-top: 3pt; }
    .sig-label   { font-size: 8pt; color: #333; margin-top: 2pt; }
    .sig-name    { font-weight: bold; }
    .sig-role    { font-size: 7.5pt; color: #555; margin-top: 1pt; }

    /* ── Pie ── */
    .footer-bar {
        margin-top: 16pt;
        border-top: 0.8pt solid #ccc;
        padding-top: 5pt;
        text-align: center;
        font-size: 7.5pt;
        color: #999;
    }

    /* ── Utilidades ── */
    .tr   { text-align: right; }
    .tc   { text-align: center; }
    .bold { font-weight: bold; }
    .ok   { color: #155724; }
    .neg  { color: #b00; font-weight: bold; }
    .warn { color: #856404; font-weight: bold; }
</style>
</head>
<body>

<div class="company-name"><?= $company ?></div>
<div class="company-sub">Sistema de Inventarios &mdash; Traslado entre Sucursales</div>

<div class="title-box">
    <h2>Boleta de Traslado de Inventario</h2>
    <p>N&deg; BT-<?= $transferNum ?> &nbsp;&middot;&nbsp; Estado: <?= $statusLabel ?></p>
</div>

<!-- Metadatos -->
<table class="meta-tbl">
    <tr>
        <td class="lbl">Sucursal Origen:</td>
        <td><?= htmlspecialchars($transfer['from_branch']) ?></td>
        <td class="sep"></td>
        <td class="lbl tr">Fecha Impresi&oacute;n:</td>
        <td class="tr"><?= date('d/m/Y h:i A') ?></td>
    </tr>
    <tr>
        <td class="lbl">Sucursal Destino:</td>
        <td><?= htmlspecialchars($transfer['to_branch']) ?></td>
        <td class="sep"></td>
        <td class="lbl tr">Fecha Emisi&oacute;n:</td>
        <td class="tr"><?= date('d/m/Y', strtotime($transfer['created_at'])) ?></td>
    </tr>
    <tr>
        <td class="lbl">Emitido por:</td>
        <td><?= htmlspecialchars(ucwords(strtolower($creator))) ?></td>
        <td class="sep"></td>
        <?php if ($isReceived): ?>
        <td class="lbl tr">Fecha Recepci&oacute;n:</td>
        <td class="tr"><?= date('d/m/Y H:i', strtotime($transfer['received_at'])) ?></td>
        <?php else: ?>
        <td colspan="2"></td>
        <?php endif; ?>
    </tr>
    <?php if ($isReceived && $receiver): ?>
    <tr>
        <td class="lbl">Recibido por:</td>
        <td><?= htmlspecialchars(ucwords(strtolower($receiver))) ?></td>
        <td colspan="3"></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($transfer['notes'])): ?>
    <tr>
        <td class="lbl">Observaciones:</td>
        <td colspan="4"><?= htmlspecialchars($transfer['notes']) ?></td>
    </tr>
    <?php endif; ?>
</table>

<!-- Tabla de productos -->
<table class="items-tbl">
    <thead>
        <tr>
            <th style="width:11%">SKU</th>
            <th style="width:<?= $isReceived ? '25%' : '35%' ?>">Producto</th>
            <th class="tr" style="width:11%">Costo Unit.</th>
            <th class="tc" style="width:9%">Uds. Env.</th>
            <th class="tr" style="width:12%">Val. Enviado</th>
            <?php if ($isReceived): ?>
            <th class="tc" style="width:9%">Uds. Rec.</th>
            <th class="tr" style="width:12%">Val. Recibido</th>
            <th class="tc" style="width:7%">Dif.</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $item):
            $cost    = floatval($item['unit_cost'] ?? 0);
            $sentVal = intval($item['quantity_sent']) * $cost;
            $recvQty = $isReceived ? intval($item['quantity_received'] ?? 0) : null;
            $recvVal = $isReceived ? $recvQty * $cost : null;
            $diff    = $isReceived ? ($recvQty - intval($item['quantity_sent'])) : null;
            $rowClass = ($i % 2 === 1) ? 'even' : '';
        ?>
        <tr class="<?= $rowClass ?>">
            <td><?= htmlspecialchars($item['sku'] ?? '&mdash;') ?></td>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td class="tr"><?= $cost > 0 ? number_format($cost, 2) : '&mdash;' ?></td>
            <td class="tc bold"><?= number_format($item['quantity_sent']) ?></td>
            <td class="tr"><?= $cost > 0 ? number_format($sentVal, 2) : '&mdash;' ?></td>
            <?php if ($isReceived): ?>
            <td class="tc bold"><?= number_format($recvQty) ?></td>
            <td class="tr"><?= $cost > 0 ? number_format($recvVal, 2) : '&mdash;' ?></td>
            <td class="tc <?= $diff === 0 ? 'ok' : ($diff < 0 ? 'neg' : 'warn') ?>">
                <?= $diff === 0 ? '&#10003;' : ($diff > 0 ? "+{$diff}" : $diff) ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" class="tr">TOTALES:</td>
            <td class="tc"><?= number_format($totalSentQty) ?></td>
            <td class="tr"><?= $totalSentValue > 0 ? number_format($totalSentValue, 2) : '&mdash;' ?></td>
            <?php if ($isReceived): ?>
            <td class="tc"><?= number_format($totalRecvQty) ?></td>
            <td class="tr"><?= $totalRecvValue > 0 ? number_format($totalRecvValue, 2) : '&mdash;' ?></td>
            <td></td>
            <?php endif; ?>
        </tr>
    </tfoot>
</table>

<!-- Resumen inferior -->
<div class="sum-wrap">
    <div class="sum-left">
        Total de l&iacute;neas: <u class="bold"><?= count($items) ?></u><br>
        Uds. enviadas: <u class="bold"><?= number_format($totalSentQty) ?></u>
        <?php if ($isReceived): ?>
        <br>Uds. recibidas: <u class="bold"><?= number_format($totalRecvQty) ?></u>
        <?php endif; ?>
    </div>
    <?php if ($totalSentValue > 0): ?>
    <div class="sum-right">
        <table class="sum-tbl">
            <tr>
                <td class="lbl">Valor Total Enviado</td>
                <td style="width:80pt;">C$ <?= number_format($totalSentValue, 2) ?></td>
            </tr>
            <?php if ($isReceived): ?>
            <tr>
                <td class="lbl">Valor Total Recibido</td>
                <td>C$ <?= number_format($totalRecvValue, 2) ?></td>
            </tr>
            <?php if (abs($totalSentValue - $totalRecvValue) > 0.001): ?>
            <tr>
                <td class="lbl neg">Diferencia de Valor</td>
                <td class="neg">C$ <?= number_format($totalRecvValue - $totalSentValue, 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>
    <div class="clearfix"></div>
</div>

<!-- Firmas -->
<table class="sigs-table">
    <tr>
        <td>
            <div class="sig-line"></div>
            <div class="sig-label">Usuario Aplica: <span class="sig-name"><?= htmlspecialchars(ucwords(strtolower($creator))) ?></span></div>
            <div class="sig-role">Emitido por &mdash; <?= htmlspecialchars($transfer['from_branch']) ?></div>
        </td>
        <td>
            <div class="sig-line"></div>
            <div class="sig-label">Usuario Aplica: <span class="sig-name">
                <?= $receiver
                    ? htmlspecialchars(ucwords(strtolower($receiver)))
                    : '&nbsp;' ?>
            </span></div>
            <div class="sig-role">Recibido por &mdash; <?= htmlspecialchars($transfer['to_branch']) ?></div>
        </td>
    </tr>
</table>

<!-- Pie -->
<div class="footer-bar">
    <?= $company ?> &nbsp;&middot;&nbsp; BT-<?= $transferNum ?> &nbsp;&middot;&nbsp; Generado el <?= date('d/m/Y H:i:s') ?>
</div>

</body>
</html>
