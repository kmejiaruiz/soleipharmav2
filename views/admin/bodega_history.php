<?php
// views/admin/bodega_history.php
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-history"></i> Historial de Traslados entre Bodegas</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/bodega/stock">Bodegas</a></li>
                    <li class="breadcrumb-item active">Historial</li>
                </ol>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?= APP_BASE ?>/bodega/transfer" class="btn btn-primary btn-sm">
                    <i class="fas fa-exchange-alt"></i> Nuevo Traslado
                </a>
                <?php
                    $csvParams = http_build_query(array_merge($_GET, ['export' => 'csv']));
                ?>
                <a href="<?= APP_BASE ?>/bodega/history?<?= $csvParams ?>" class="btn btn-success btn-sm ml-1">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <!-- Filtros -->
        <div class="card card-outline card-secondary mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= APP_BASE ?>/bodega/history" class="row align-items-end">
                    <div class="col-md-2">
                        <label>Desde Bodega</label>
                        <select name="from_bodega" class="form-control form-control-sm">
                            <option value="">— Todas —</option>
                            <?php foreach ($labels as $k => $lbl): ?>
                            <option value="<?= $k ?>" <?= $filterFrom === $k ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lbl) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Hacia Bodega</label>
                        <select name="to_bodega" class="form-control form-control-sm">
                            <option value="">— Todas —</option>
                            <?php foreach ($labels as $k => $lbl): ?>
                            <option value="<?= $k ?>" <?= $filterTo === $k ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lbl) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Fecha Desde</label>
                        <input type="date" name="date1" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($filterDate1) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Fecha Hasta</label>
                        <input type="date" name="date2" class="form-control form-control-sm"
                               value="<?= htmlspecialchars($filterDate2) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Producto / Usuario</label>
                        <input type="text" name="q" class="form-control form-control-sm"
                               placeholder="Buscar…" value="<?= htmlspecialchars($filterQ) ?>">
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button class="btn btn-sm btn-primary mr-1">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="<?= APP_BASE ?>/bodega/history" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card card-outline card-dark">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <?= count($movements) ?> movimiento(s) encontrado(s)
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover table-striped mb-0" id="history-table">
                    <thead class="thead-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th>Desde</th>
                            <th>Hacia</th>
                            <th class="text-right">Cantidad</th>
                            <th>Motivo</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movements)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No se encontraron traslados con los filtros aplicados.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($movements as $m): ?>
                        <?php
                            $fromClass = ['sucursal' => 'success', 'debito' => 'warning', 'merma' => 'danger'][$m['from_bodega']] ?? 'secondary';
                            $toClass   = ['sucursal' => 'success', 'debito' => 'warning', 'merma' => 'danger'][$m['to_bodega']]   ?? 'secondary';
                        ?>
                        <tr>
                            <td class="text-nowrap">
                                <small><?= date('d/m/Y', strtotime($m['created_at'])) ?></small><br>
                                <small class="text-muted"><?= date('H:i', strtotime($m['created_at'])) ?></small>
                            </td>
                            <td><code><?= htmlspecialchars($m['sku'] ?? '—') ?></code></td>
                            <td><?= htmlspecialchars($m['product_name']) ?></td>
                            <td>
                                <span class="badge badge-<?= $fromClass ?>">
                                    <?= htmlspecialchars($labels[$m['from_bodega']] ?? $m['from_bodega']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $toClass ?>">
                                    <?= htmlspecialchars($labels[$m['to_bodega']] ?? $m['to_bodega']) ?>
                                </span>
                            </td>
                            <td class="text-right font-weight-bold">
                                <?= number_format($m['quantity']) ?>
                            </td>
                            <td class="text-muted small" style="max-width:200px;word-break:break-word;">
                                <?= htmlspecialchars($m['reason'] ?? '—') ?>
                            </td>
                            <td class="small">
                                <?php
                                    $userName = trim($m['user_name']);
                                    echo htmlspecialchars($userName ?: $m['username']);
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($movements)): ?>
            <div class="card-footer text-muted small">
                Total de unidades trasladadas:
                <strong><?= number_format(array_sum(array_column($movements, 'quantity'))) ?></strong>
            </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#history-table').DataTable({
            paging: true,
            pageLength: 25,
            searching: false,
            info: true,
            order: [[0, 'desc']],
            columnDefs: [{ orderable: false, targets: [6] }],
            language: {
                paginate: { previous: 'Ant.', next: 'Sig.' },
                info: 'Mostrando _START_–_END_ de _TOTAL_',
                emptyTable: 'Sin registros',
            }
        });
    }
});
</script>
