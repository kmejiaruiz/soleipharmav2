<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Retiro de Caja</title>
    <style>
        @page { size: 80mm auto; margin: 4mm 3mm; }
        body { font-family: 'Courier New', Courier, monospace; font-size: 10px; color: #000; margin: 0; padding: 0; }
        .center { text-align: center; }
        .right  { text-align: right; }
        .left   { text-align: left; }
        .bold   { font-weight: bold; }
        .line   { border: none; border-top: 1px dashed #000; margin: 4px 0; }
        table   { width: 100%; border-collapse: collapse; }
        td      { padding: 1px 2px; vertical-align: top; font-size: 10px; }
    </style>
</head>
<body>

<p class="center bold" style="font-size:12px;"><?= strtoupper(htmlspecialchars($company)) ?></p>
<p class="center"><?= htmlspecialchars($branch) ?></p>
<hr class="line">
<p class="center bold">COMPROBANTE DE RETIRO</p>
<p class="center">No. <?= str_pad($withdrawal['id'], 5, '0', STR_PAD_LEFT) ?></p>
<hr class="line">

<table>
    <tr><td class="bold">Fecha:</td><td class="right"><?= date('d/m/Y', strtotime($withdrawal['created_at'])) ?></td></tr>
    <tr><td class="bold">Hora:</td><td class="right"><?php
        $dt = new DateTime($withdrawal['created_at']);
        $dt->setTimezone(new DateTimeZone('America/Managua'));
        echo $dt->format('H:i:s');
    ?></td></tr>
    <tr><td class="bold">Cajero:</td><td class="right"><?= htmlspecialchars(ucwords(strtolower($withdrawal['withdrawer_name']))) ?></td></tr>
    <?php if (!empty($withdrawal['reason'])): ?>
    <tr><td class="bold">Motivo:</td><td class="right"><?= htmlspecialchars($withdrawal['reason']) ?></td></tr>
    <?php endif; ?>
</table>

<hr class="line">
<p class="center bold">DENOMINACIONES RETIRADAS</p>
<hr class="line">

<table>
    <tr>
        <td class="bold">Denom.</td>
        <td class="center bold">Cant.</td>
        <td class="right bold">Subtotal</td>
    </tr>
    <?php
    $denomLabels = [1000=>'C$1,000', 500=>'C$500', 200=>'C$200', 100=>'C$100',
                    50=>'C$50', 20=>'C$20', 10=>'C$10', 5=>'C$5', 1=>'C$1'];
    foreach ($denominations as $val => $qty):
        $sub = $val * $qty;
    ?>
    <tr>
        <td><?= $denomLabels[$val] ?? "C$$val" ?></td>
        <td class="center">x<?= $qty ?></td>
        <td class="right">C$<?= number_format($sub, 2) ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<hr class="line">
<table>
    <tr>
        <td class="bold" style="font-size:11px;">TOTAL RETIRADO</td>
        <td class="right bold" style="font-size:11px;">C$<?= number_format($withdrawal['total_amount'], 2) ?></td>
    </tr>
</table>
<hr class="line">

<p class="center" style="font-size:9px;">Comprobante generado por SoleiPharma</p>
<br><br>

</body>
</html>
