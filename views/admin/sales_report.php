<?php
// Se espera que el controlador pase: $salesGrouped (arreglo agrupado por día), $startDate y $endDate.
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Reporte de Ventas</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Reporte de Ventas</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <!-- Formulario para seleccionar el rango de fechas -->
        <form id="filterForm" class="form-inline mb-4" method="GET" action="<?= APP_BASE ?>/salesReport/index">
            
            
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

<!-- SweetAlert2 Plugin -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    function validateDates() {
        var startDate = $("#start_date").val();
        var endDate = $("#end_date").val();
        
        if (!startDate || !endDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Atención',
                text: 'Debe seleccionar ambas fechas para realizar esta acción.',
                confirmButtonColor: '#6f42c1'
            });
            return false;
        }

        if (new Date(startDate) > new Date(endDate)) {
            Swal.fire({
                icon: 'error',
                title: 'Fechas Inválidas',
                text: 'La fecha "Desde" tiene que ser mayor o igual que la fecha "Hasta".',
                confirmButtonColor: '#6f42c1'
            });
            return false;
        }

        return true;
    }

    $("#pdfButton").click(function (e) {
        e.preventDefault();
        if (validateDates()) {
            var startDate = $("#start_date").val();
            var endDate = $("#end_date").val();
            window.location.href = "<?= APP_BASE ?>/salesReport/generatePDF?start_date=" + startDate + "&end_date=" + endDate;
        }
    });

    $("#filterForm").on("submit", function (e) {
        if (!validateDates()) {
            e.preventDefault();
        }
    });
</script>