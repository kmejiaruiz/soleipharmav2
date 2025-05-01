<?php
// Se espera que el controlador pase: $salesGrouped (arreglo agrupado por día), $startDate y $endDate.
?>
<section class="content-header">
    <div class="container-fluid">
        <h1>Reporte de Ventas</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <!-- Formulario para seleccionar el rango de fechas -->
        <form class="form-inline mb-4" method="GET" action="index.php">
            <input type="hidden" name="controller" value="salesReport">
            <input type="hidden" name="action" value="index">
            <div class="form-group mr-2">
                <label for="start_date" class="mr-2">Desde:</label>
                <input type="date" id="start_date" name="start_date" class="form-control"
                    value="<?= htmlspecialchars($startDate) ?>" required>
            </div>
            <div class="form-group mr-2">
                <label for="end_date" class="mr-2">Hasta:</label>
                <input type="date" id="end_date" name="end_date" class="form-control"
                    value="<?= htmlspecialchars($endDate) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary mr-2">Filtrar</button>
            <a id="pdfButton" href="#" class="btn btn-success">Generar PDF</a>
        </form>

        <?php if (!empty($salesGrouped)): ?>
            <table class="table table-striped table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Número de Ventas</th>
                        <th>Total del Día</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($salesGrouped as $day): ?>
                        <tr>
                            <td><?= htmlspecialchars($day['sale_date']) ?></td>
                            <td><?= htmlspecialchars($day['num_sales']) ?></td>
                            <td>$<?= number_format($day['total_sales'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-right">Total Ventas:</th>
                        <th>$<?= number_format(array_sum(array_column($salesGrouped, 'total_sales')), 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No se encontraron ventas en el rango seleccionado.</div>
        <?php endif; ?>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $("#pdfButton").click(function (e) {
        e.preventDefault();
        var startDate = $("#start_date").val();
        var endDate = $("#end_date").val();
        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar ambas fechas para generar el reporte.'
            });
        } else {
            window.location.href = "index.php?controller=salesReport&action=generatePDF&start_date=" + startDate + "&end_date=" + endDate;
        }
    });

    $("#filterForm").on("submit", function (e) {
        var startDate = $("#start_date").val();
        var endDate = $("#end_date").val();
        if (!startDate || !endDate) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar ambas fechas para filtrar el reporte.'
            });
        }
    });
</script>