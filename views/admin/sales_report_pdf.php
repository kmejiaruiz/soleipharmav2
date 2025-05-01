<?php
// Variables esperadas: $salesGrouped, $startDate, $endDate
$tz = new DateTimeZone("-06:00");
$date = new DateTime("now", $tz);
$generatedAt = $date->format("d/m/Y H:i:s");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #000;
            margin-bottom: 10px;
        }

        .company {
            font-size: 16px;
            font-weight: bold;
        }

        .report-info {
            text-align: right;
            font-size: 12px;
        }
        .report-info p{
            margin: 0;
        }

        header {
            text-align: center;
            margin-bottom: 20px;
        }

        header h2 {
            margin: 0;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #e0e0e0;
        }

        tfoot td {
            font-weight: bold;
        }

        .fin-reporte {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header-container">
        <div class="company"><?= COMPANY_NAME ?></div>
        <div class="report-info">
            <p>Generado: <?= $generatedAt ?></p>
            <p>Desde: <?= htmlspecialchars($startDate) ?> - Hasta: <?= htmlspecialchars($endDate) ?></p>
            <p>Bodega Reporte: <?= BRANCH ?></p>
        </div>
    </div>
    <header>
        <h2>Reporte de Ventas Landing Page</h2>
    </header>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Número de Ventas</th>
                <th>Total del Día</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($salesGrouped)): ?>
                <?php foreach ($salesGrouped as $day): ?>
                    <tr>
                        <td><?= htmlspecialchars($day['sale_date']) ?></td>
                        <td><?= htmlspecialchars($day['num_sales']) ?></td>
                        <td>$<?= number_format($day['total_sales'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center;">No hay ventas en este rango.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;">Total Ventas:</td>
                <td>$<?= number_format(array_sum(array_column($salesGrouped, 'total_sales')), 2) ?></td>
            </tr>
        </tfoot>
    </table>
    <p class="fin-reporte">- Fin de Reporte -</p>
</body>

</html>