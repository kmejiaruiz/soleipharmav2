<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        .totals {
            text-align: right;
            margin-top: 10px;
        }
    </style>
    <title>Boleta #<?= htmlspecialchars($entry['entry_id']) ?></title>
</head>

<body>
    <h2>Boleta de Recepción #<?= htmlspecialchars($entry['entry_id']) ?></h2>

    <p><strong>Fecha:</strong> <?= htmlspecialchars($receptionDate) ?></p>
    <p><strong>Recibido por:</strong> <?= htmlspecialchars($appliedUser) ?></p>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th>Costo Unitario</th>
                <th>Cant. Ordenada</th>
                <th>Cant. Recibida</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $it): ?>
                <tr>
                    <td><?= htmlspecialchars($it['sku']) ?></td>
                    <td><?= htmlspecialchars($it['name']) ?></td>
                    <td>$<?= number_format($it['cost_unit'], 2) ?></td>
                    <td><?= intval($it['ordered_qty']) ?></td>
                    <td><?= intval($it['received_qty']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <!-- <p><strong>Factura Subtotal:</strong> $<?= number_format($invoiceSubtotal, 2) ?></p>
        <p><strong>IVA Factura:</strong> $<?= number_format($invoiceTax, 2) ?></p> -->
        <p><strong>Total Factura:</strong> $<?= number_format($invoiceTotal, 2) ?></p>
        <p><strong>Total Sistema (costo * recibido):</strong> $<?= number_format($systemTotal, 2) ?></p>
        <hr>
        <!-- <p><strong>Total Sistema (costo * recibido):</strong> $<?= number_format($systemTotal, 2) ?></p> -->
    </div>
</body>

</html>