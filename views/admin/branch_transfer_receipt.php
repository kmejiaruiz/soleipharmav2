<?php // views/admin/branch_transfer_receipt.php
$transferNum  = str_pad($transfer['id'], 4, '0', STR_PAD_LEFT);
$isReceived   = $transfer['status'] === 'recibido';
$isCancelled  = $transfer['status'] === 'cancelado';

$totalSentQty   = 0;
$totalRecvQty   = 0;
$totalSentValue = 0.0;
$totalRecvValue = 0.0;
foreach ($items as $item) {
    $cost            = floatval($item['unit_cost'] ?? 0);
    $totalSentQty   += intval($item['quantity_sent']);
    $totalSentValue += intval($item['quantity_sent']) * $cost;
    if ($isReceived) {
        $totalRecvQty   += intval($item['quantity_received'] ?? 0);
        $totalRecvValue += intval($item['quantity_received'] ?? 0) * $cost;
    }
}

$statusLabel = match($transfer['status']) {
    'pendiente' => 'PENDIENTE',
    'recibido'  => 'RECIBIDO',
    'cancelado' => 'CANCELADO',
    default     => strtoupper($transfer['status']),
};
?>

<!-- ── Botón de acción (no imprime) ── -->
<style>
    .no-print-bar {}

    @media print {
        /* ── Ocultar todo el chrome de AdminLTE ── */
        .no-print-bar,
        .content-header,
        .main-header,
        .main-sidebar,
        .main-footer,
        .breadcrumb,
        nav { display: none !important; }

        /* ── Resetear layout AdminLTE completamente ── */
        body,
        html { background: #fff !important; margin: 0 !important; padding: 0 !important; }
        .wrapper,
        .content-wrapper { margin: 0 !important; padding: 0 !important;
                           min-height: unset !important; width: 100% !important; }
        .content { padding: 0 !important; margin: 0 !important; }
        .container-fluid { padding: 0 !important; margin: 0 !important; }

        /* ── El área de la boleta ocupa toda la página ── */
        #boleta-print-area {
            border: none !important;
            box-shadow: none !important;
            max-width: 100% !important;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            font-size: 11pt !important;
            font-family: Arial, Helvetica, sans-serif !important;
            color: #000 !important;
        }

        @page {
            size: A4 portrait;
            margin: 14mm 16mm 18mm;
        }
    }
</style>

<div class="no-print-bar content-header">
    <div class="container-fluid">
        <div class="row align-items-center mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-file-invoice"></i> Boleta de Traslado #<?= $transferNum ?></h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= APP_BASE ?>/branchTransfer/index">Traslados</a>
                    </li>
                    <li class="breadcrumb-item active">Boleta #<?= $transferNum ?></li>
                </ol>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?= APP_BASE ?>/branchTransfer/printReceipt/<?= $transfer['id'] ?>"
                   target="_blank" class="btn btn-primary">
                    <i class="fas fa-print"></i> Imprimir / PDF
                </a>
                <a href="<?= APP_BASE ?>/branchTransfer/index" class="btn btn-secondary ml-2">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     BOLETA — mismo estilo que goods_entry_report (Arial, portrait A4)
══════════════════════════════════════════════════════════════════════════════ -->
<div class="content">
<div class="container-fluid">
<div id="boleta-print-area" style="max-width:780px;margin:0 auto 48px;padding:28px 36px;background:#fff;
     border:1px solid #dee2e6;border-radius:6px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#000;">

<style>
/* ── Estilos base (pantalla) ── */
#boleta-print-area { box-sizing: border-box; }

/* ── Encabezado ── */
.b-company   { font-size: 18px; font-weight: bold; text-transform: uppercase; margin-bottom: 4px; }
.b-subtitle  { font-size: 12px; color: #555; margin-bottom: 16px; }
.b-title-box { text-align: center; border-top: 2px solid #000; border-bottom: 1px solid #000;
               padding: 8px 0; margin-bottom: 16px; }
.b-title-box h2 { font-size: 19px; margin: 0 0 3px; text-transform: uppercase; font-weight: bold; }
.b-title-box p  { margin: 0; font-size: 13px; font-weight: bold; }

/* ── Tabla de metadatos ── */
.b-meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 13.5px; }
.b-meta td { padding: 4px 6px; vertical-align: top; }
.b-meta td.lbl { font-weight: bold; white-space: nowrap; width: 130px; }
.b-meta td.sep { width: 30px; }

/* ── Tabla de ítems ── */
.b-items { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 12.5px; }
.b-items thead th {
    border-top: 1px solid #000; border-bottom: 1px solid #000;
    padding: 6px 7px; font-weight: bold; font-size: 12.5px; text-align: left;
}
.b-items tbody td { padding: 6px 7px; vertical-align: middle; border: none; font-size: 12.5px; }
.b-items tbody tr:nth-child(even) td { background: #f5f5f5; }
.b-items tfoot td {
    border-top: 1px solid #000; font-weight: bold; padding: 6px 7px; font-size: 12.5px;
}

/* ── Resumen inferior ── */
.b-summary   { width: 100%; border-top: 1px solid #000; padding-top: 8px; display: table; margin-top: 6px; }
.b-sum-left  { display: table-cell; width: 55%; vertical-align: top; font-size: 12.5px; }
.b-sum-right { display: table-cell; width: 45%; vertical-align: top; text-align: right; }
.b-sum-tbl   { width: 100%; border-collapse: collapse; float: right; }
.b-sum-tbl td { padding: 4px 6px; text-align: right; font-size: 12.5px; }
.b-sum-tbl td.lbl { font-weight: bold; }

/* ── Firmas ── */
.b-sigs { width: 100%; display: table; margin-top: 36px; }
.b-sig  { display: table-cell; width: 50%; text-align: center; padding: 0 24px; }
.b-sig .line { border-top: 1px solid #555; padding-top: 6px; margin-top: 32px; font-size: 12px; }
.b-sig .role { font-size: 11px; color: #555; }

/* ── Pie ── */
.b-footer { margin-top: 24px; border-top: 1px solid #ccc; padding-top: 8px;
            text-align: center; font-size: 11px; color: #888; }

/* ── Helpers ── */
.tr { text-align: right; }
.tc { text-align: center; }
.diff-neg { color: #c00; font-weight: bold; }
.diff-ok  { color: #155724; }
.diff-pos { color: #856404; font-weight: bold; }

/* ══════════════════════════════════════════════════════
   IMPRESIÓN — todo en pt para que llene la hoja A4
══════════════════════════════════════════════════════ */
@media print {
    .b-company   { font-size: 16pt !important; margin-bottom: 3pt !important; }
    .b-subtitle  { font-size: 9pt  !important; margin-bottom: 10pt !important; }

    .b-title-box    { padding: 6pt 0 !important; margin-bottom: 10pt !important; }
    .b-title-box h2 { font-size: 16pt !important; }
    .b-title-box p  { font-size: 10pt !important; }

    .b-meta    { font-size: 10pt !important; margin-bottom: 10pt !important; }
    .b-meta td { padding: 3pt 5pt !important; font-size: 10pt !important; }
    .b-meta td.lbl { width: 100pt !important; }

    .b-items             { font-size: 9pt !important; margin-bottom: 10pt !important; }
    .b-items thead th    { font-size: 9pt !important; padding: 5pt 6pt !important; }
    .b-items tbody td    { font-size: 9pt !important; padding: 5pt 6pt !important; }
    .b-items tbody tr:nth-child(even) td { background: #f5f5f5 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .b-items tfoot td    { font-size: 9pt !important; padding: 5pt 6pt !important; }

    .b-summary   { margin-top: 4pt !important; padding-top: 5pt !important; }
    .b-sum-left  { font-size: 9pt !important; }
    .b-sum-tbl td { font-size: 9pt !important; padding: 3pt 5pt !important; }

    .b-sigs        { margin-top: 28pt !important; }
    .b-sig .line   { margin-top: 24pt !important; padding-top: 5pt !important; font-size: 9pt !important; }
    .b-sig .role   { font-size: 8pt !important; }

    .b-footer { margin-top: 14pt !important; padding-top: 5pt !important; font-size: 8pt !important; }

    .diff-neg { color: #c00 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .diff-ok  { color: #155724 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>

<!-- Cabecera: nombre empresa + hora impresión arriba derecha -->
<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
    <div>
        <div class="b-company"><?= defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) : 'FARMACIA SOLEI' ?></div>
        <div class="b-subtitle">Sistema de Inventarios — Traslado entre Sucursales</div>
    </div>
    <div style="text-align:right;font-size:11px;color:#555;">
        <span style="font-weight:bold;">Hora de Impresión:</span><br>
        <span id="print-timestamp" style="font-size:12px;color:#c00;font-weight:bold;">---</span>
    </div>
</div>

<!-- Título centrado -->
<div class="b-title-box">
    <h2>BOLETA DE TRASLADO DE INVENTARIO</h2>
    <p>N° BT-<?= $transferNum ?> &nbsp;·&nbsp; Estado: <?= $statusLabel ?></p>
</div>

<!-- Metadatos -->
<table class="b-meta">
    <tr>
        <td class="lbl">Sucursal Origen:</td>
        <td><?= htmlspecialchars($transfer['from_branch']) ?></td>
        <td class="sep"></td>
        <td class="lbl" style="text-align:right;">Fecha Impresión:</td>
        <td style="text-align:right;"><?= date('d/m/Y h:i:s A') ?></td>
    </tr>
    <tr>
        <td class="lbl">Sucursal Destino:</td>
        <td><?= htmlspecialchars($transfer['to_branch']) ?></td>
        <td class="sep"></td>
        <td class="lbl" style="text-align:right;">Fecha Emisión:</td>
        <td style="text-align:right;"><?= date('d/m/Y', strtotime($transfer['created_at'])) ?></td>
    </tr>
    <tr>
        <td class="lbl">Emitido por:</td>
        <td style="text-transform:capitalize;"><?= htmlspecialchars(strtolower($creator)) ?></td>
        <td class="sep"></td>
        <?php if ($isReceived): ?>
        <td class="lbl" style="text-align:right;">Hora de Recepción:</td>
        <td style="text-align:right;font-weight:bold;"><?= date('d/m/Y H:i:s', strtotime($transfer['received_at'])) ?></td>
        <?php else: ?>
        <td colspan="2"></td>
        <?php endif; ?>
    </tr>
    <?php if ($isReceived && $receiver): ?>
    <tr>
        <td class="lbl">Recibido por:</td>
        <td style="text-transform:capitalize;"><?= htmlspecialchars(strtolower($receiver)) ?></td>
        <td colspan="3"></td>
    </tr>
    <?php endif; ?>
    <?php if ($transfer['notes']): ?>
    <tr>
        <td class="lbl">Observaciones:</td>
        <td colspan="4"><?= htmlspecialchars($transfer['notes']) ?></td>
    </tr>
    <?php endif; ?>
</table>

<!-- Tabla de ítems -->
<table class="b-items">
    <thead>
        <tr>
            <th style="width:12%">SKU</th>
            <th style="width:<?= $isReceived ? '28%' : '36%' ?>">Producto</th>
            <th class="tr" style="width:11%">Costo Unit.</th>
            <th class="tc" style="width:10%">Uds. Env.</th>
            <th class="tr" style="width:12%">Val. Enviado</th>
            <?php if ($isReceived): ?>
            <th class="tc" style="width:10%">Uds. Rec.</th>
            <th class="tr" style="width:12%">Val. Recibido</th>
            <th class="tc" style="width:7%">Dif.</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item):
            $cost    = floatval($item['unit_cost'] ?? 0);
            $sentVal = intval($item['quantity_sent']) * $cost;
            $recvQty = $isReceived ? intval($item['quantity_received'] ?? 0) : null;
            $recvVal = $isReceived ? $recvQty * $cost : null;
            $diff    = $isReceived ? ($recvQty - intval($item['quantity_sent'])) : null;
        ?>
        <tr>
            <td><?= htmlspecialchars($item['sku'] ?? '—') ?></td>
            <td><?= htmlspecialchars($item['product_name']) ?></td>
            <td class="tr"><?= $cost > 0 ? number_format($cost, 2) : '—' ?></td>
            <td class="tc"><?= number_format($item['quantity_sent']) ?></td>
            <td class="tr"><?= $cost > 0 ? number_format($sentVal, 2) : '—' ?></td>
            <?php if ($isReceived): ?>
            <td class="tc"><?= number_format($recvQty) ?></td>
            <td class="tr"><?= $cost > 0 ? number_format($recvVal, 2) : '—' ?></td>
            <td class="tc <?=
                $diff === 0 ? 'diff-ok' :
                ($diff < 0  ? 'diff-neg' : 'diff-pos')
            ?>">
                <?= $diff === 0 ? '✓' : ($diff > 0 ? "+{$diff}" : $diff) ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;">TOTALES:</td>
            <td class="tc"><?= number_format($totalSentQty) ?></td>
            <td class="tr"><?= $totalSentValue > 0 ? number_format($totalSentValue, 2) : '—' ?></td>
            <?php if ($isReceived): ?>
            <td class="tc"><?= number_format($totalRecvQty) ?></td>
            <td class="tr"><?= $totalRecvValue > 0 ? number_format($totalRecvValue, 2) : '—' ?></td>
            <td></td>
            <?php endif; ?>
        </tr>
    </tfoot>
</table>

<!-- Resumen inferior -->
<div class="b-summary">
    <div class="b-sum-left">
        Total de líneas: &nbsp;&nbsp;<u style="font-weight:bold;"><?= count($items) ?></u><br>
        Total unidades enviadas: &nbsp;&nbsp;<u style="font-weight:bold;"><?= number_format($totalSentQty) ?></u>
        <?php if ($isReceived): ?>
        <br>Total unidades recibidas: &nbsp;&nbsp;<u style="font-weight:bold;"><?= number_format($totalRecvQty) ?></u>
        <?php endif; ?>
    </div>
    <?php if ($totalSentValue > 0): ?>
    <div class="b-sum-right">
        <table class="b-sum-tbl">
            <tr>
                <td class="lbl">Valor Total Enviado</td>
                <td style="width:90px;">C$ <?= number_format($totalSentValue, 2) ?></td>
            </tr>
            <?php if ($isReceived): ?>
            <tr>
                <td class="lbl">Valor Total Recibido</td>
                <td>C$ <?= number_format($totalRecvValue, 2) ?></td>
            </tr>
            <?php if (abs($totalSentValue - $totalRecvValue) > 0.001): ?>
            <tr>
                <td class="lbl" style="color:#c00;">Diferencia de Valor</td>
                <td style="color:#c00;">C$ <?= number_format($totalRecvValue - $totalSentValue, 2) ?></td>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Firmas -->
<div class="b-sigs">
    <div class="b-sig">
        <div class="line">
            <strong><?= htmlspecialchars(ucwords(strtolower($creator))) ?></strong>
        </div>
        <div class="role">Emitido por — <?= htmlspecialchars($transfer['from_branch']) ?></div>
    </div>
    <div class="b-sig">
        <div class="line">
            <?php if ($receiver): ?>
            <strong><?= htmlspecialchars(ucwords(strtolower($receiver))) ?></strong>
            <?php else: ?>
            <span style="color:#bbb;">_________________________________</span>
            <?php endif; ?>
        </div>
        <div class="role">Recibido por — <?= htmlspecialchars($transfer['to_branch']) ?></div>
    </div>
</div>

<!-- Pie de página -->
<div class="b-footer">
    <?= defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) : 'Farmacia Solei' ?>
    &nbsp;·&nbsp; BT-<?= $transferNum ?>
    &nbsp;·&nbsp; Generado el <?= date('d/m/Y H:i:s') ?>
</div>

</div><!-- /#boleta-print-area -->
</div>
</div>

<script>
(function () {
    function setTimestamp() {
        var el = document.getElementById('print-timestamp');
        if (!el) return;
        var now = new Date();
        var pad = function(n){ return String(n).padStart(2,'0'); };
        el.textContent =
            pad(now.getDate()) + '/' + pad(now.getMonth()+1) + '/' + now.getFullYear() +
            ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }
    // Actualizar al cargar la página
    setTimestamp();
    // Actualizar justo antes de imprimir (momento real de impresión)
    window.addEventListener('beforeprint', setTimestamp);
})();
</script>
