<?php
// views/admin/bodega_transfer.php
$preProduct = intval($_GET['product_id']  ?? 0);
$preFrom    = $_GET['from_bodega'] ?? 'sucursal';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-exchange-alt"></i> Registrar Traslado entre Bodegas</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/bodega/stock">Stock por Bodega</a></li>
                    <li class="breadcrumb-item active">Traslado</li>
                </ol>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?= APP_BASE ?>/bodega/history" class="btn btn-secondary btn-sm">
                    <i class="fas fa-history"></i> Historial de Traslados
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <!-- Alerta de resultado -->
                <div id="transfer-alert" class="alert" style="display:none;" role="alert"></div>

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-dolly"></i> Datos del Traslado</h3>
                    </div>
                    <div class="card-body">

                        <!-- Producto -->
                        <div class="form-group">
                            <label for="product_id">Producto <span class="text-danger">*</span></label>
                            <select id="product_id" name="product_id" class="form-control" required>
                                <option value="">— Seleccione un producto —</option>
                                <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>"
                                    <?= $preProduct == $p['id'] ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($p['sku'] ?? '?') ?>]
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Origen → Destino -->
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="from_bodega">Bodega de Origen <span class="text-danger">*</span></label>
                                    <select id="from_bodega" class="form-control" required>
                                        <?php foreach ($labels as $key => $lbl): ?>
                                        <option value="<?= $key ?>" <?= $preFrom === $key ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($lbl) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small id="stock-disponible" class="form-text text-muted mt-1">
                                        Stock disponible: <strong id="stock-val">—</strong>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-center justify-content-center" style="padding-top:8px;">
                                <i class="fas fa-arrow-right fa-2x text-secondary"></i>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="to_bodega">Bodega de Destino <span class="text-danger">*</span></label>
                                    <select id="to_bodega" class="form-control" required>
                                        <?php foreach ($labels as $key => $lbl): ?>
                                        <option value="<?= $key ?>">
                                            <?= htmlspecialchars($lbl) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Cantidad -->
                        <div class="form-group">
                            <label for="quantity">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" id="quantity" class="form-control" min="1" placeholder="0" required>
                            <small id="qty-warn" class="text-danger" style="display:none;">
                                La cantidad supera el stock disponible en el origen.
                            </small>
                        </div>

                        <!-- Motivo -->
                        <div class="form-group">
                            <label for="reason">Motivo / Observación</label>
                            <textarea id="reason" class="form-control" rows="3"
                                      placeholder="Ej: Productos dañados, devolución a proveedor, etc."></textarea>
                        </div>

                        <!-- Resumen visual -->
                        <div id="transfer-summary" class="alert alert-info py-2 px-3" style="display:none;font-size:0.9rem;">
                            Moviendo <strong id="s-qty">—</strong> unidades de
                            <strong id="s-product">—</strong> desde
                            <strong id="s-from">—</strong> hacia
                            <strong id="s-to">—</strong>.
                        </div>

                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="<?= APP_BASE ?>/bodega/stock" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button id="btn-save" class="btn btn-primary" onclick="saveTransfer()">
                            <i class="fas fa-save"></i> Confirmar Traslado
                        </button>
                    </div>
                </div>

                <!-- Últimos traslados rápidos -->
                <div class="card card-outline card-secondary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-clock"></i> Últimos traslados</h3>
                        <div class="card-tools">
                            <a href="<?= APP_BASE ?>/bodega/history" class="btn btn-tool">Ver todos</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="recent-transfers" class="text-center text-muted py-3 small">
                            <i class="fas fa-spinner fa-spin"></i> Cargando…
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
const LABELS = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
const BASE_URL = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>';
let stockCache = {};

// ── Auto-set to-bodega al cargar (distinta de from) ──────────────────────────
(function initDefaults() {
    syncTo();
    loadStock();
    loadRecentTransfers();
})();

document.getElementById('from_bodega').addEventListener('change', function () {
    syncTo();
    loadStock();
    updateSummary();
});
document.getElementById('to_bodega').addEventListener('change', updateSummary);
document.getElementById('product_id').addEventListener('change', function () {
    stockCache = {};
    loadStock();
    updateSummary();
});
document.getElementById('quantity').addEventListener('input', function () {
    validateQty();
    updateSummary();
});

function syncTo() {
    const from    = document.getElementById('from_bodega').value;
    const toSel   = document.getElementById('to_bodega');
    const current = toSel.value;

    // Si el destino actual es igual al origen, elegir el siguiente disponible
    if (current === from) {
        const opts = [...toSel.options].map(o => o.value).filter(v => v !== from);
        if (opts.length) toSel.value = opts[0];
    }
}

function loadStock() {
    const pid  = document.getElementById('product_id').value;
    const from = document.getElementById('from_bodega').value;
    const key  = pid + '_' + from;
    const el   = document.getElementById('stock-val');

    if (!pid) { el.textContent = '—'; return; }

    if (stockCache[key] !== undefined) {
        el.textContent = stockCache[key];
        return;
    }
    el.textContent = '…';
    fetch(`${BASE_URL}/bodega/stockAjax?product_id=${pid}&bodega=${from}`)
        .then(r => r.json())
        .then(d => {
            stockCache[key] = d.stock;
            el.textContent  = d.stock;
            validateQty();
        })
        .catch(() => { el.textContent = '?'; });
}

function validateQty() {
    const qty   = parseInt(document.getElementById('quantity').value) || 0;
    const avail = parseInt(document.getElementById('stock-val').textContent) || 0;
    const warn  = document.getElementById('qty-warn');
    const ok    = qty <= avail && qty >= 1;
    warn.style.display = (!ok && qty > 0) ? 'block' : 'none';
    return ok;
}

function updateSummary() {
    const pid   = document.getElementById('product_id');
    const qty   = parseInt(document.getElementById('quantity').value) || 0;
    const from  = document.getElementById('from_bodega').value;
    const to    = document.getElementById('to_bodega').value;
    const sumEl = document.getElementById('transfer-summary');

    if (!pid.value || qty < 1 || from === to) { sumEl.style.display = 'none'; return; }

    document.getElementById('s-qty').textContent     = qty;
    document.getElementById('s-product').textContent = pid.options[pid.selectedIndex].text.replace(/^\[.*?\]\s*/, '');
    document.getElementById('s-from').textContent    = LABELS[from] || from;
    document.getElementById('s-to').textContent      = LABELS[to]   || to;
    sumEl.style.display = 'block';
}

async function saveTransfer() {
    if (!validateQty()) { return; }

    const pid  = document.getElementById('product_id').value;
    const from = document.getElementById('from_bodega').value;
    const to   = document.getElementById('to_bodega').value;
    const qty  = parseInt(document.getElementById('quantity').value) || 0;
    const rsn  = document.getElementById('reason').value.trim();

    if (!pid || !from || !to || qty < 1 ) {
        showAlert('Completa todos los campos requeridos.', 'warning');
        return;
    }
    if (from === to) {
        showAlert('El origen y el destino no pueden ser iguales.', 'warning');
        return;
    }

    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

    const body = new URLSearchParams({ product_id: pid, from_bodega: from, to_bodega: to, quantity: qty, reason: rsn });

    try {
        const res  = await fetch(`${BASE_URL}/bodega/saveTransfer`, { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('quantity').value = '';
            document.getElementById('reason').value   = '';
            document.getElementById('transfer-summary').style.display = 'none';
            stockCache = {};
            loadStock();
            loadRecentTransfers();
        } else {
            showAlert(data.message, 'danger');
        }
    } catch (e) {
        showAlert('Error de red: ' + e.message, 'danger');
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Confirmar Traslado';
}

function showAlert(msg, type) {
    const el = document.getElementById('transfer-alert');
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    el.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => { el.style.display = 'none'; }, 6000);
}

async function loadRecentTransfers() {
    const container = document.getElementById('recent-transfers');
    try {
        const res  = await fetch(`${BASE_URL}/bodega/history?limit=5&ajax=1`);
        // history devuelve HTML; usaremos la URL normal con un query marker
        // En su lugar, generamos tabla directo en JS
        container.innerHTML = '<span class="text-muted px-3">Ver el historial completo <a href="' + BASE_URL + '/bodega/history">aquí</a>.</span>';
    } catch (e) {
        container.innerHTML = '<span class="text-muted px-3">No se pudo cargar.</span>';
    }
}
</script>
