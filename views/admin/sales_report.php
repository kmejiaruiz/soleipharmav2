<!-- views/admin/sales_report.php -->
<section class="content-header">
    <div class="container-fluid">
        <h1>Reporte de Ventas</h1>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <!-- Tarjeta para el gráfico de barras -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ventas por Día</h3>
            </div>
            <div class="card-body">
                <?php if($hasData): ?>
                <div style="max-width: 100%; height: 180px;">
                    <canvas id="salesBarChart"></canvas>
                </div>
                <?php else: ?>
                <div class="alert alert-info">No hay datos de ventas.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Incluir Chart.js desde CDN, solo si hay datos -->
<?php if($hasData): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const labels = <?= $labels; ?>;
    const data = {
        labels: labels,
        datasets: [{
            label: 'Ventas',
            data: <?= $totals; ?>,
            backgroundColor: 'rgba(75, 0, 130, 0.7)',
            borderColor: 'rgba(75, 0, 130, 1)',
            borderWidth: 1
        }]
    };

    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: true, // Permite ajustar el tamaño
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#4B0082'
                    }
                },
                x: {
                    ticks: {
                        color: '#4B0082'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#4B0082'
                    }
                }
            }
        }
    };

    new Chart(document.getElementById('salesBarChart'), config);
});
</script>
<?php endif; ?>