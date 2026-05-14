<?php // views/admin/inventory_movements.php ?>
<section class="content-header">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-exchange-alt"></i> Movimientos de Inventario</h1>
        </div>
        <div class="col-sm-6 d-flex align-items-center justify-content-end">
            <a href="<?= APP_BASE ?>/inventory/exportCsv" class="btn btn-success btn-sm mr-3">
                <i class="fas fa-file-csv"></i> Exportar CSV
            </a>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                <li class="breadcrumb-item active">Movimientos de Inventario</li>
            </ol>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <!-- Filtros -->
        <div class="card card-outline card-secondary mb-3">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-filter"></i> Filtros</h3></div>
            <div class="card-body">
                <form method="GET" action="<?= APP_BASE ?>/inventory/movements" class="row align-items-end">
                    <div class="col-md-3">
                        <label>Producto / SKU</label>
                        <input type="text" name="product" class="form-control form-control-sm"
                            placeholder="Buscar producto..." value="<?= htmlspecialchars($productFilter) ?>">
                    </div>
                    <div class="col-md-3">
                        <label>Categoría</label>
                        <select name="type" class="form-control form-control-sm">
                            <option value="">Todos los movimientos</option>
                            <option value="entrada_mercaderia" <?= $typeFilter==='entrada_mercaderia'?'selected':'' ?>>📦 Entrada de Mercadería</option>
                            <option value="venta"             <?= $typeFilter==='venta'            ?'selected':'' ?>>🛒 Salida por Venta</option>
                            <option value="descarte"          <?= $typeFilter==='descarte'         ?'selected':'' ?>>🗑️ Descarte</option>
                            <option value="oficial"           <?= $typeFilter==='oficial'          ?'selected':'' ?>>⚖️ Ajuste de Oficial</option>
                            <option value="manual"            <?= $typeFilter==='manual'           ?'selected':'' ?>>✏️ Edición Manual</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Desde</label>
                        <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($fromFilter) ?>">
                    </div>
                    <div class="col-md-2">
                        <label>Hasta</label>
                        <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($toFilter) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary btn-sm mr-1"><i class="fas fa-search"></i> Filtrar</button>
                        <a href="<?= APP_BASE ?>/inventory/movements" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cajas de resumen -->
        <?php
        $byCategoria = function($cat) use ($movements) {
            return count(array_filter($movements, fn($m) => $m['categoria'] === $cat));
        };
        ?>
        <div class="row mb-3">
            <div class="col-6 col-md">
                <div class="small-box bg-success">
                    <div class="inner"><h3><?= $byCategoria('entrada_mercaderia') ?></h3><p>Entradas de Mercadería</p></div>
                    <div class="icon"><i class="fas fa-truck-loading"></i></div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="small-box bg-primary">
                    <div class="inner"><h3><?= $byCategoria('venta') ?></h3><p>Salidas por Venta</p></div>
                    <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="small-box bg-warning">
                    <div class="inner"><h3><?= $byCategoria('descarte') ?></h3><p>Descartes</p></div>
                    <div class="icon"><i class="fas fa-trash-alt"></i></div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="small-box bg-secondary">
                    <div class="inner"><h3><?= $byCategoria('oficial') ?></h3><p>Ajustes de Oficial</p></div>
                    <div class="icon"><i class="fas fa-user-cog"></i></div>
                </div>
            </div>
            <div class="col-6 col-md">
                <div class="small-box bg-dark">
                    <div class="inner"><h3><?= $byCategoria('manual') ?></h3><p>Ediciones Manuales</p></div>
                    <div class="icon"><i class="fas fa-edit"></i></div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-body p-0" style="overflow-x:auto;">
                <table class="table table-bordered table-hover table-sm mb-0" id="movementsTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>Fecha / Hora</th>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th class="text-center">Tipo de Movimiento</th>
                            <th class="text-right">Cantidad</th>
                            <th class="text-right">Stk. Ant.</th>
                            <th class="text-right">Stk. Nuevo</th>
                            <th>Detalle / Referencia</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $badgeMap = [
                            'entrada_mercaderia' => ['success',   'fa-truck-loading',   'Entrada de Mercadería'],
                            'venta'              => ['primary',   'fa-shopping-cart',   'Salida por Venta'],
                            'descarte'           => ['warning',   'fa-trash-alt',       'Descarte'],
                            'oficial'            => ['secondary', 'fa-user-cog',        ''],  // uses tipo directly
                            'manual'             => ['dark',      'fa-edit',            'Edición Manual'],
                        ];
                        foreach ($movements as $m):
                            $cat = $m['categoria'] ?? 'manual';
                            $b   = $badgeMap[$cat] ?? ['secondary','fa-circle',''];
                            $label = $b[2] ?: $m['tipo'];
                            $qtyAbs = abs((float)($m['cantidad'] ?? 0));
                            $isIn   = $m['direccion'] === 'entrada';
                            $qtyClass = $isIn ? 'text-success font-weight-bold' : 'text-danger font-weight-bold';
                            $qtySign  = $isIn ? '+' : '-';

                            // Parsear referencia en líneas para mejor legibilidad
                            $refs = explode(' | ', $m['referencia'] ?? '');
                        ?>
                        <tr>
                            <td class="text-nowrap"><small><?= htmlspecialchars(substr($m['fecha'] ?? '', 0, 16)) ?></small></td>
                            <td><code><?= htmlspecialchars($m['sku'] ?? '—') ?></code></td>
                            <td><small><?= htmlspecialchars($m['producto']) ?></small></td>
                            <td class="text-center">
                                <span class="badge badge-<?= $b[0] ?> p-1 d-block">
                                    <i class="fas <?= $b[1] ?>"></i> <?= htmlspecialchars($label) ?>
                                </span>
                            </td>
                            <td class="text-right <?= $qtyClass ?>"><?= $qtySign . number_format($qtyAbs) ?></td>
                            <td class="text-right text-muted"><small><?= $m['previous_stock'] !== null ? number_format($m['previous_stock']) : '—' ?></small></td>
                            <td class="text-right text-muted"><small><?= $m['new_stock'] !== null ? number_format($m['new_stock']) : '—' ?></small></td>
                            <td>
                                <?php foreach ($refs as $ref): if (!trim($ref)) continue; ?>
                                    <small class="d-block text-muted"><?= htmlspecialchars(trim($ref)) ?></small>
                                <?php endforeach; ?>
                            </td>
                            <td><small><?= htmlspecialchars(trim($m['usuario'] ?? '—')) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($movements)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No se encontraron movimientos con los filtros aplicados.
                        </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $('#movementsTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        order: [[0, 'desc']],
        pageLength: 25,
    });
});
</script>
