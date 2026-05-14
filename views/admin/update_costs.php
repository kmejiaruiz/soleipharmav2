<?php
// views/admin/update_costs.php
// Variables: $products (id, sku, name, sale_price, cost, utility_percent, tax_percent, available)
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-tags"></i> Costos, IVA y Disponibilidad</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item active">Costos e IVA</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <div class="card card-outline card-primary">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
            <div class="d-flex align-items-center" style="gap:10px;">
                <input type="text" id="searchInput" class="form-control form-control-sm"
                       placeholder="🔍 Buscar producto o SKU..." style="width:250px;">
                <span class="badge badge-secondary" id="countBadge"><?= count($products) ?> productos</span>
            </div>
            <div class="d-flex" style="gap:8px;">
                <button type="button" id="applyIvaBtn" class="btn btn-sm btn-warning">
                    <i class="fas fa-percentage"></i> IVA global
                </button>
                <button type="button" id="applyUtilBtn" class="btn btn-sm btn-info">
                    <i class="fas fa-chart-line"></i> Utilidad global
                </button>
                <button type="button" id="saveBtn" class="btn btn-sm btn-primary">
                    <i class="fas fa-save"></i> Guardar cambios
                </button>
            </div>
        </div>

        <!-- Panel IVA global -->
        <div id="ivaPanel" class="global-panel" style="display:none;background:#fff8e1;border-bottom:1px solid #ffc107;padding:10px 20px;">
            <div class="d-flex align-items-center" style="gap:12px;flex-wrap:wrap;">
                <label class="mb-0 font-weight-bold">IVA (%) para todos:</label>
                <input type="number" id="globalIva" class="form-control form-control-sm" style="width:90px;" step="0.01" min="0" max="100" value="15">
                <button type="button" class="btn btn-warning btn-sm" id="applyIvaConfirm"><i class="fas fa-check"></i> Aplicar</button>
                <button type="button" class="btn btn-secondary btn-sm panel-cancel">Cancelar</button>
            </div>
        </div>

        <!-- Panel Utilidad global -->
        <div id="utilPanel" class="global-panel" style="display:none;background:#e8f4fe;border-bottom:1px solid #17a2b8;padding:10px 20px;">
            <div class="d-flex align-items-center" style="gap:12px;flex-wrap:wrap;">
                <label class="mb-0 font-weight-bold">Utilidad (%) para todos:</label>
                <input type="number" id="globalUtil" class="form-control form-control-sm" style="width:90px;" step="0.01" min="0" value="30">
                <button type="button" class="btn btn-info btn-sm" id="applyUtilConfirm"><i class="fas fa-check"></i> Aplicar</button>
                <button type="button" class="btn btn-secondary btn-sm panel-cancel">Cancelar</button>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0" id="costsTable">
                <thead class="thead-dark">
                    <tr>
                        <th style="width:9%">SKU</th>
                        <th style="width:22%">Producto</th>
                        <th style="width:9%" class="text-right">
                            Costo
                            <small class="d-block text-warning font-weight-normal" style="font-size:9px;">editable</small>
                        </th>
                        <th style="width:7%" class="text-center">
                            IVA&nbsp;%
                            <small class="d-block text-warning font-weight-normal" style="font-size:9px;">editable</small>
                        </th>
                        <th style="width:9%" class="text-right">Costo+IVA</th>
                        <th style="width:7%" class="text-center">
                            Utilidad&nbsp;%
                            <small class="d-block text-warning font-weight-normal" style="font-size:9px;">editable</small>
                        </th>
                        <th style="width:9%" class="text-right">
                            P. Venta calc.
                            <small class="d-block" style="font-size:9px;font-weight:400;color:#ffc107;">costo×(1+util%)</small>
                        </th>
                        <th style="width:9%" class="text-right text-muted">P. Venta actual</th>
                        <th style="width:8%" class="text-center">Disponible</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p):
                    $cost    = floatval($p['cost'] ?? 0);
                    $iva     = floatval($p['tax_percent'] ?? 0);
                    $util    = floatval($p['utility_percent'] ?? 0);
                    $pvCalc  = round($cost * (1 + $util / 100), 2);
                    $cIva    = round($cost * (1 + $iva / 100), 2);
                    $avail   = intval($p['available'] ?? 1);
                ?>
                <tr class="product-row">
                    <td class="align-middle small text-muted"><?= htmlspecialchars($p['sku'] ?? $p['id']) ?></td>
                    <td class="align-middle product-name"><?= htmlspecialchars($p['name']) ?></td>

                    <!-- Costo -->
                    <td class="text-right p-1">
                        <input type="number" name="costs[<?= $p['id'] ?>]" data-id="<?= $p['id'] ?>"
                               value="<?= number_format($cost, 4, '.', '') ?>"
                               step="0.0001" min="0"
                               class="form-control form-control-sm text-right cost-input" style="width:100%;min-width:80px;">
                    </td>

                    <!-- IVA % -->
                    <td class="text-center p-1">
                        <input type="number" name="taxes[<?= $p['id'] ?>]" data-id="<?= $p['id'] ?>"
                               value="<?= number_format($iva, 2, '.', '') ?>"
                               step="0.01" min="0" max="100"
                               class="form-control form-control-sm text-center iva-input" style="width:65px;margin:0 auto;">
                    </td>

                    <!-- Costo + IVA preview -->
                    <td class="text-right align-middle small cost-iva-cell">C$ <?= number_format($cIva, 2) ?></td>

                    <!-- Utilidad % -->
                    <td class="text-center p-1">
                        <input type="number" name="utilities[<?= $p['id'] ?>]" data-id="<?= $p['id'] ?>"
                               value="<?= number_format($util, 2, '.', '') ?>"
                               step="0.01" min="0"
                               class="form-control form-control-sm text-center util-input" style="width:65px;margin:0 auto;">
                    </td>

                    <!-- P. Venta calculado (preview) -->
                    <td class="text-right align-middle font-weight-bold pv-calc-cell text-success">
                        C$ <?= number_format($pvCalc, 2) ?>
                    </td>

                    <!-- P. Venta actual en BD -->
                    <td class="text-right align-middle small text-muted">
                        C$ <?= number_format($p['price'] ?? $p['sale_price'] ?? 0, 2) ?>
                    </td>

                    <!-- Disponible toggle -->
                    <td class="text-center align-middle">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input avail-input"
                                   name="available[<?= $p['id'] ?>]"
                                   id="avail_<?= $p['id'] ?>"
                                   data-id="<?= $p['id'] ?>"
                                   <?= $avail ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="avail_<?= $p['id'] ?>">
                                <span class="avail-label" style="font-size:11px;color:<?= $avail ? '#28a745' : '#dc3545' ?>">
                                    <?= $avail ? 'Sí' : 'No' ?>
                                </span>
                            </label>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="card-footer text-right">
            <small class="text-muted mr-3">
                <strong>P. Venta calc.</strong> = Costo × (1 + Utilidad%). Ese valor se guarda como precio de venta.
            </small>
            <button type="button" id="saveBtn2" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
        </div>
    </div>

</div>
</section>

<script>
/* ── Recalcular previews de una fila ── */
function recalcRow(row) {
    const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
    const iva  = parseFloat(row.querySelector('.iva-input').value)  || 0;
    const util = parseFloat(row.querySelector('.util-input').value) || 0;

    const cIva  = Math.round(cost * (1 + iva  / 100) * 10000) / 10000;
    const pvCalc = Math.round(cost * (1 + util / 100) * 100)  / 100;

    row.querySelector('.cost-iva-cell').textContent = 'C$ ' + cIva.toFixed(2);
    row.querySelector('.pv-calc-cell').textContent  = 'C$ ' + pvCalc.toFixed(2);
}

document.querySelectorAll('.cost-input, .iva-input, .util-input').forEach(inp => {
    inp.addEventListener('input', () => recalcRow(inp.closest('tr')));
});

/* ── Toggle disponible label ── */
document.querySelectorAll('.avail-input').forEach(cb => {
    cb.addEventListener('change', function () {
        const lbl = this.closest('td').querySelector('.avail-label');
        lbl.textContent = this.checked ? 'Sí' : 'No';
        lbl.style.color = this.checked ? '#28a745' : '#dc3545';
    });
});

/* ── Búsqueda ── */
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('.product-row').forEach(row => {
        const match = row.querySelector('.product-name').textContent.toLowerCase().includes(q)
                   || row.cells[0].textContent.toLowerCase().includes(q);
        row.style.display = match ? '' : 'none';
        if (match) vis++;
    });
    document.getElementById('countBadge').textContent = vis + ' productos';
});

/* ── Paneles globales ── */
document.getElementById('applyIvaBtn').addEventListener('click', () => togglePanel('ivaPanel'));
document.getElementById('applyUtilBtn').addEventListener('click', () => togglePanel('utilPanel'));
document.querySelectorAll('.panel-cancel').forEach(b => b.addEventListener('click', closeAllPanels));

function togglePanel(id) {
    closeAllPanels();
    const p = document.getElementById(id);
    if (p) p.style.display = '';
}
function closeAllPanels() {
    document.querySelectorAll('.global-panel').forEach(p => p.style.display = 'none');
}

document.getElementById('applyIvaConfirm').addEventListener('click', () => {
    const val = document.getElementById('globalIva').value;
    document.querySelectorAll('.iva-input').forEach(inp => {
        inp.value = val;
        recalcRow(inp.closest('tr'));
    });
    closeAllPanels();
    toast('IVA del ' + val + '% aplicado a todos los productos.');
});

document.getElementById('applyUtilConfirm').addEventListener('click', () => {
    const val = document.getElementById('globalUtil').value;
    document.querySelectorAll('.util-input').forEach(inp => {
        inp.value = val;
        recalcRow(inp.closest('tr'));
    });
    closeAllPanels();
    toast('Utilidad del ' + val + '% aplicada a todos los productos.');
});

function toast(msg) {
    Swal.fire({ icon: 'info', title: msg, timer: 2500, showConfirmButton: false });
}

/* ── Guardar ── */
function doSave() {
    const params = new URLSearchParams();

    document.querySelectorAll('.cost-input').forEach(i => params.append('costs['   + i.dataset.id + ']', i.value));
    document.querySelectorAll('.iva-input').forEach(i  => params.append('taxes['   + i.dataset.id + ']', i.value));
    document.querySelectorAll('.util-input').forEach(i => params.append('utilities[' + i.dataset.id + ']', i.value));
    document.querySelectorAll('.avail-input').forEach(i => {
        params.append('available[' + i.dataset.id + ']', i.checked ? '1' : '0');
    });

    ['saveBtn','saveBtn2'].forEach(id => {
        document.getElementById(id).disabled = true;
        document.getElementById(id).innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    });

    fetch('<?= APP_BASE ?>/product/updateCosts', {
        method: 'POST', body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message, timer: 2000, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'Error de conexión' }))
    .finally(() => {
        ['saveBtn','saveBtn2'].forEach(id => {
            document.getElementById(id).disabled = false;
            document.getElementById(id).innerHTML = '<i class="fas fa-save"></i> Guardar cambios';
        });
    });
}

document.getElementById('saveBtn').addEventListener('click', doSave);
document.getElementById('saveBtn2').addEventListener('click', doSave);
</script>