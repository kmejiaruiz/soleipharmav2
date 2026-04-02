<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cierre de Caja</title>
    <style>
        @page { size: 80mm auto; margin: 4mm 3mm; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 10px; color: #000; margin: 0; padding: 0; }
        .center { text-align: center; }
        .right  { text-align: right; }
        .bold   { font-weight: bold; }
        .line   { border: none; border-top: 1px dashed #000; margin: 4px 0; }
        table   { width: 100%; border-collapse: collapse; }
        td      { padding: 1px 3px; vertical-align: top; font-size: 10px; }
    </style>
</head>
<body>

<?php
$diff = floatval($closingCount['difference'] ?? 0);
$diffLabel = abs($diff) < 0.01 ? 'CUADRE PERFECTO' : ($diff > 0 ? 'SOBRANTE' : 'FALTANTE');
?>

<p class="center bold" style="font-size:12px;"><?= strtoupper(htmlspecialchars($company)) ?></p>
<p class="center"><?= htmlspecialchars($branch) ?></p>
<hr class="line">
<p class="center bold">REPORTE DE CIERRE DE CAJA</p>
<p class="center">Sesion No. <?= str_pad($session['id'], 5, '0', STR_PAD_LEFT) ?></p>
<p class="center"><?= date('d/m/Y H:i:s') ?></p>
<hr class="line">

<table>
    <tr><td class="bold">Abierta por:</td><td class="right"><?= htmlspecialchars(ucwords(strtolower($session['opener_name']))) ?></td></tr>
    <tr><td class="bold">Cerrada por:</td><td class="right"><?= htmlspecialchars(ucwords(strtolower($session['closer_name'] ?? '---'))) ?></td></tr>
    <tr><td class="bold">Apertura:</td><td class="right"><?php
        $dt = new DateTime($session['opened_at']);
        $dt->setTimezone(new DateTimeZone('America/Managua'));
        echo $dt->format('d/m/Y H:i');
    ?></td></tr>
    <tr><td class="bold">Cierre:</td><td class="right"><?php
        if ($session['closed_at']) {
            $dt2 = new DateTime($session['closed_at']);
            $dt2->setTimezone(new DateTimeZone('America/Managua'));
            echo $dt2->format('d/m/Y H:i');
        } else { echo '---'; }
    ?></td></tr>
</table>

<?php if (!empty($withdrawals)): ?>
<hr class="line">
<p class="center bold">RETIROS</p>
<hr class="line">
<table>
    <tr><td class="bold">Hora</td><td class="bold">Motivo</td><td class="right bold">Monto</td></tr>
    <?php foreach ($withdrawals as $w):
        $dtw = new DateTime($w['created_at']);
        $dtw->setTimezone(new DateTimeZone('America/Managua'));
    ?>
    <tr>
        <td><?= $dtw->format('H:i') ?></td>
        <td><?= htmlspecialchars(substr($w['reason'] ?? '---', 0, 14)) ?></td>
        <td class="right">C$<?= number_format($w['total_amount'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
    <tr><td colspan="2" class="bold">Total retiros:</td><td class="right bold">C$<?= number_format($totalWithdrawn, 2) ?></td></tr>
</table>
<?php endif; ?>

<?php if ($closingCount && !empty($closingDenoms)): ?>
<hr class="line">
<p class="center bold">CONTEO FISICO</p>
<hr class="line">
<table>
    <tr><td class="bold">Denom.</td><td class="center bold">Cant.</td><td class="right bold">Subtotal</td></tr>
    <?php
    $denomLabels = [1000=>'C$1,000', 500=>'C$500', 200=>'C$200', 100=>'C$100',
                    50=>'C$50', 20=>'C$20', 10=>'C$10', 5=>'C$5', 1=>'C$1'];
    foreach ($closingDenoms as $val => $qty):
        $sub = $val * $qty;
    ?>
    <tr>
        <td><?= $denomLabels[$val] ?? "C$$val" ?></td>
        <td class="center">x<?= $qty ?></td>
        <td class="right">C$<?= number_format($sub, 2) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<hr class="line">
<p class="center bold">RESUMEN</p>
<hr class="line">
<table>
    <tr><td>Fondo apertura:</td><td class="right">C$<?= number_format($session['opening_amount'], 2) ?></td></tr>
    <tr><td>Ventas (<?= $salesData['order_count'] ?> ord.):</td><td class="right">C$<?= number_format($salesData['total_sales'], 2) ?></td></tr>
    <tr><td>Retiros:</td><td class="right">-C$<?= number_format($totalWithdrawn, 2) ?></td></tr>
    <tr><td class="bold">Esperado:</td><td class="right bold">C$<?= number_format($closingCount['expected_amount'] ?? 0, 2) ?></td></tr>
    <tr><td>Contado:</td><td class="right">C$<?= number_format($closingCount['counted_amount'] ?? 0, 2) ?></td></tr>
</table>
<hr class="line">
<table>
    <tr>
        <td class="bold" style="font-size:11px;"><?= $diffLabel ?>:</td>
        <td class="right bold" style="font-size:11px;">C$<?= number_format(abs($diff), 2) ?></td>
    </tr>
</table>
<hr class="line">

<p class="center">Cajero: _____________________</p>
<br>
<p class="center">Supervisor: __________________</p>
<br>
<p class="center" style="font-size:9px;">SoleiPharma - <?= date('d/m/Y H:i:s') ?></p>
<br><br>

</body>
</html>
