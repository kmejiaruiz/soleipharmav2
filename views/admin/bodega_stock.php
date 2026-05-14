<?php
// views/admin/bodega_stock.php
$pageTitle  = 'Stock de ' . htmlspecialchars($labels[$bodega] ?? $bodega);
$totalStock = array_sum(array_column($products, 'stock'));
$sinStock   = $sinStock ?? false;
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-warehouse"></i> Stock por Bodega</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item active">Stock — <?= htmlspecialchars($labels[$bodega] ?? $bodega) ?></li>
                </ol>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?= APP_BASE ?>/bodega/transfer" class="btn btn-primary btn-sm">
                    <i class="fas fa-exchange-alt"></i> Registrar Traslado
                </a>
                <a href="<?= APP_BASE ?>/bodega/history" class="btn btn-secondary btn-sm ml-1">
                    <i class="fas fa-history"></i> Historial
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <!-- Selectores de Bodega -->
        <div class="row mb-3">
            <?php foreach ($labels as $key => $label): ?>
            <div class="col-md-4 mb-2">
                <a href="<?= APP_BASE ?>/bodega/stock?bodega=<?= $key ?>"
                   class="btn btn-block <?= $bodega === $key ? 'btn-dark' : 'btn-outline-secondary' ?>">
                    <?php $icons = ['sucursal' => 'fa-store', 'debito' => 'fa-undo', 'merma' => 'fa-trash']; ?>
                    <i class="fas <?= $icons[$key] ?? 'fa-box' ?>"></i>
                    <?= htmlspecialchars($label) ?>
                    <?php if ($bodega === $key): ?>
                        <span class="badge badge-light ml-1"><?= number_format($totalStock) ?> uds.</span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card card-outline card-dark">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">
                    <i class="fas fa-boxes"></i>
                    <?= htmlspecialchars($labels[$bodega] ?? $bodega) ?>
                </h3>
                <div>
                    <form method="GET" action="<?= APP_BASE ?>/bodega/stock" class="form-inline">
                        <input type="hidden" name="bodega" value="<?= htmlspecialchars($bodega) ?>">
                        <input type="text" name="q" class="form-control form-control-sm mr-2"
                               placeholder="Buscar producto…" value="<?= htmlspecialchars($search) ?>">
                        <button class="btn btn-sm btn-secondary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if ($search): ?>
                        <a href="<?= APP_BASE ?>/bodega/stock?bodega=<?= $bodega ?>"
                           class="btn btn-sm btn-link text-muted ml-1">Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0" id="bodega-stock-table">
                    <thead class="thead-dark">
                        <tr>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th class="text-right">Stock</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($p['sku'] ?? '—') ?></code></td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($p['stock']) ?></td>
                            <td class="text-center">
                                <?php if ($p['stock'] <= (defined('LOW_STOCK_THRESHOLD') ? LOW_STOCK_THRESHOLD : 9)): ?>
                                    <span class="badge badge-warning">Stock Bajo</span>
                                <?php else: ?>
                                    <span class="badge badge-success">OK</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= APP_BASE ?>/bodega/transfer?product_id=<?= $p['id'] ?>&from_bodega=<?= $bodega ?>"
                                   class="btn btn-xs btn-outline-primary" title="Trasladar">
                                    <i class="fas fa-exchange-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($products)): ?>
            <div class="card-footer d-flex justify-content-end align-items-center bg-light py-2">
                <strong class="mr-2">Total unidades:</strong>
                <span class="badge badge-dark" style="font-size:1rem;padding:6px 14px;"><?= number_format($totalStock) ?></span>
            </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- ── Micromodal: Sin existencias en bodega ──────────────────────────────── -->
<div class="modal micromodal-slide" id="modal-sin-stock" aria-hidden="true">
    <div class="modal__overlay" tabindex="-1">
        <!-- Sin data-micromodal-close en el overlay → click fuera no cierra -->
        <div class="modal__container" role="dialog" aria-modal="true"
             aria-labelledby="modal-sin-stock-title"
             style="max-width:400px;text-align:center;position:relative;">

            <!-- X de cerrar → al cerrar redirige a sucursal -->
            <button class="modal__close" id="btn-close-sinstock"
                    aria-label="Cerrar"
                    style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:22px;color:#999;cursor:pointer;line-height:1;"
                    onclick="closeSinStock()">&#x2715;</button>

            <div style="padding:16px 0 14px;">
                <svg xmlns="http://www.w3.org/2000/svg"
                     style="width:64px;height:64px;color:#adb5bd;"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.3">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                </svg>
            </div>

            <h2 class="modal__title" id="modal-sin-stock-title"
                style="font-size:1.1rem;font-weight:700;margin-bottom:10px;color:#343a40;">
                Sin existencias
            </h2>

            <div class="modal__content" style="color:#6c757d;font-size:0.92rem;line-height:1.65;margin-bottom:8px;">
                La <strong><?= htmlspecialchars($labels[$bodega] ?? $bodega) ?></strong>
                no tiene unidades registradas actualmente.<br>
                Al cerrar esta ventana serás redirigido al inventario de la sucursal principal.
            </div>

        </div>
    </div>
</div>

<script>
function closeSinStock() {
    if (typeof MicroModal !== 'undefined') {
        MicroModal.close('modal-sin-stock');
    }
    window.location.href = '<?= APP_BASE ?>/bodega/stock?bodega=sucursal';
}

document.addEventListener('DOMContentLoaded', function () {

    <?php if ($sinStock): ?>
    if (typeof MicroModal !== 'undefined') {
        MicroModal.show('modal-sin-stock', {
            disableFocus:              true,
            disableScroll:             true,
            // Evitar que el click en el overlay lo cierre
            onShow: function (modal) {
                modal.querySelector('.modal__overlay').addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            }
        });
    }
    <?php endif; ?>

    if (typeof $.fn.DataTable !== 'undefined') {
        $('#bodega-stock-table').DataTable({
            paging:    false,
            searching: false,
            info:      false,
            order:     [[1, 'asc']],
            language: {
                emptyTable: '<div class="text-center text-muted py-4"><i class="fas fa-box-open fa-2x mb-2 d-block"></i>No hay productos con existencias<?= $search ? " que coincidan con la búsqueda" : "" ?>.</div>'
            }
        });
    }
});
</script>
