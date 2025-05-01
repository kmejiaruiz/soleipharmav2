<?php
// views/admin/goods_entry_report.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Boleta de Recepción</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .header .left,
        .header .right {
            width: 48%;
        }

        .header .left h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .header .left p {
            margin: 2px 0;
            font-size: 10px;
        }

        .header .right table {
            width: 100%;
            font-size: 10px;
            border-collapse: collapse;
        }

        .header .right td {
            padding: 2px 4px;
        }

        .header .right td.label {
            font-weight: bold;
            width: 35%;
            text-align: right;
        }

        .header .right td.value {
            width: 65%;
            text-align: left;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 15px;
        }

        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        .main-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .main-table td {
            font-size: 10px;
        }

        .main-table tfoot td {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 10px;
            text-align: right;
            font-size: 10px;
            color: #555;
        }

        .footer p {
            margin: 0;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="left">
            <h1><?= htmlspecialchars($companyName) ?></h1>
            <p><strong>Sucursal:</strong> <?= htmlspecialchars(BRANCH) ?></p>
            <p><strong>Boleta N°:</strong> <?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></p>
            <p><strong>Estado:</strong> <?= ucfirst($order['status']) ?></p>
        </div>
        <div class="right">
            <table>
                <tr>
                    <td class="label">Usuario Aplica:</td>
                    <td class="value"><?= htmlspecialchars($appliedUser) ?></td>
                </tr>
                <tr>
                    <td class="label">Fecha/Hora Rec.:</td>
                    <td class="value"><?= htmlspecialchars($receptionDate) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Producto</th>
                <th>Ordenado</th>
                <th>Recibido</th>
                <th>Diferencia</th>
                <th>Justificación</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $it):
                $diff = $it['ordered_qty'] - $it['received_qty'];
                $diffDisplay = $diff > 0 ? "-" . $diff : "0";
                ?>
                <tr>
                    <td><?= htmlspecialchars($it['sku']) ?></td>
                    <td><?= htmlspecialchars($it['name']) ?></td>
                    <td><?= $it['ordered_qty'] ?></td>
                    <td><?= $it['received_qty'] ?></td>
                    <td><?= $diffDisplay ?></td>
                    <td><?= htmlspecialchars($it['justification'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">— Fin de Boleta —</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Documento generado automáticamente.</p>
    </div>

</body>

</html>