<?php // views/admin/branch_transfer_create.php ?>

<!-- Modal de credenciales -->
<div class="modal micromodal-slide" id="modal-cred" aria-hidden="true">
    <div class="modal__overlay" tabindex="-1">
        <div class="modal__container" role="dialog" aria-modal="true"
             aria-labelledby="modal-cred-title" style="max-width:380px;">
            <header class="modal__header">
                <h2 class="modal__title" id="modal-cred-title" style="font-size:1rem;">
                    <i class="fas fa-lock text-warning mr-1"></i>
                    Confirmar Identidad
                </h2>
                <button class="modal__close" aria-label="Cerrar"
                        onclick="MicroModal.close('modal-cred')"></button>
            </header>
            <div class="modal__content">
                <p class="text-muted small mb-3">
                    Se requieren credenciales de admin para emitir el traslado.
                </p>
                <div class="form-group">
                    <label class="small font-weight-bold">Usuario</label>
                    <input type="text" id="cred-user" class="form-control form-control-sm"
                           autocomplete="username" placeholder="Usuario">
                </div>
                <div class="form-group mb-1">
                    <label class="small font-weight-bold">Contraseña</label>
                    <input type="password" id="cred-pass" class="form-control form-control-sm"
                           autocomplete="current-password" placeholder="Contraseña">
                </div>
                <div id="cred-error" class="text-danger small mt-1" style="display:none;"></div>
            </div>
            <footer style="margin-top:16px;display:flex;justify-content:flex-end;gap:8px;">
                <button class="btn btn-sm btn-secondary"
                        onclick="MicroModal.close('modal-cred')">Cancelar</button>
                <button class="btn btn-sm btn-primary" id="btn-cred-ok"
                        onclick="submitCredAndSave()">Confirmar y Enviar</button>
            </footer>
        </div>
    </div>
</div>


<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-paper-plane"></i> Nuevo Traslado entre Sucursales</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/branchTransfer/index">Traslados</a></li>
                    <li class="breadcrumb-item active">Nuevo</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
<div class="row justify-content-center">
<div class="col-xl-9">

    <div id="alert-box" class="alert" style="display:none;"></div>

    <!-- Cabecera del traslado -->
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-info-circle"></i> Información del Traslado</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Sucursal de Origen</label>
                        <input class="form-control" value="<?= htmlspecialchars($currentBranch) ?>" disabled>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="to_branch">Sucursal de Destino <span class="text-danger">*</span></label>
                        <select id="to_branch" class="form-control" required>
                            <option value="">— Seleccione sucursal de destino —</option>
                            <?php foreach ($knownBranches as $b): ?>
                            <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="notes">Notas u Observaciones</label>
                <textarea id="notes" class="form-control" rows="2"
                          placeholder="Ej: Productos solicitados por necesidad urgente…"></textarea>
            </div>
        </div>
    </div>

    <!-- Agregar productos -->
    <div class="card card-outline card-dark">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-boxes"></i> Productos a Trasladar</h3>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">
                <i class="fas fa-plus"></i> Agregar Producto
            </button>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0" id="items-table">
                <thead class="thead-light">
                    <tr>
                        <th style="width:45%">Producto</th>
                        <th style="width:18%" class="text-center">Disponible</th>
                        <th style="width:22%" class="text-center">Cantidad a Enviar</th>
                        <th style="width:8%"></th>
                    </tr>
                </thead>
                <tbody id="items-body">
                    <!-- filas dinámicas -->
                </tbody>
            </table>
            <div id="empty-items" class="text-center text-muted py-4 small">
                Usa el botón "Agregar Producto" para incluir ítems en el traslado.
            </div>
        </div>
        <div class="card-footer text-right d-flex justify-content-between align-items-center">
            <span class="text-muted small">
                <strong id="total-items">0</strong> producto(s) ·
                <strong id="total-units">0</strong> unidades totales
            </span>
            <div>
                <a href="<?= APP_BASE ?>/branchTransfer/index" class="btn btn-secondary mr-2">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button class="btn btn-primary" id="btn-save" onclick="requestSave()">
                    <i class="fas fa-paper-plane"></i> Enviar Traslado
                </button>
            </div>
        </div>
    </div>

</div>
</div>
</div>
</section>

<script>
const PRODUCTS  = <?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>;
const BASE_URL  = '<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>';
let rowCount    = 0;
const usedProds = new Set();

// Mapa id → producto para acceso rápido
const prodMap   = {};
PRODUCTS.forEach(p => { prodMap[p.id] = p; });

function addRow() {
    rowCount++;
    const tbody = document.getElementById('items-body');
    const tr    = document.createElement('tr');
    tr.id       = `row-${rowCount}`;
    tr.innerHTML = `
        <td>
            <select class="form-control form-control-sm prod-select" onchange="onProductChange(${rowCount})" required>
                <option value="">— Seleccione un producto —</option>
                ${PRODUCTS.map(p =>
                    `<option value="${p.id}" ${usedProds.has(String(p.id)) ? 'disabled' : ''}>
                        [${escHtml(p.sku||'?')}] ${escHtml(p.name)}
                    </option>`
                ).join('')}
            </select>
        </td>
        <td class="text-center align-middle">
            <span id="avail-${rowCount}" class="badge badge-secondary">—</span>
        </td>
        <td class="text-center align-middle">
            <input type="number" class="form-control form-control-sm text-center qty-input"
                   id="qty-${rowCount}" min="1" value="1" style="width:90px;display:inline-block;"
                   oninput="validate(${rowCount}); updateTotals();" disabled>
            <div id="warn-${rowCount}" class="text-danger" style="font-size:.72rem;display:none;">Supera el stock</div>
        </td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeRow(${rowCount})">
                <i class="fas fa-trash"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    document.getElementById('empty-items').style.display = 'none';
    updateTotals();
}

function onProductChange(n) {
    const sel   = document.querySelector(`#row-${n} .prod-select`);
    const pid   = sel.value;
    const avail = document.getElementById(`avail-${n}`);
    const qty   = document.getElementById(`qty-${n}`);

    // Actualizar used set
    usedProds.forEach(id => {
        if (document.querySelector(`.prod-select[value="${id}"]`) === null) usedProds.delete(id);
    });
    if (pid) usedProds.add(pid);

    if (!pid) { avail.textContent = '—'; avail.className = 'badge badge-secondary'; qty.disabled = true; return; }

    const prod = prodMap[pid];
    if (prod) {
        avail.textContent = prod.stock;
        avail.className   = prod.stock > 0 ? 'badge badge-success' : 'badge badge-danger';
        qty.max           = prod.stock;
        qty.disabled      = prod.stock <= 0;
        if (qty.value > prod.stock) qty.value = prod.stock;
    }
    validate(n);
    updateTotals();
}

function validate(n) {
    const sel   = document.querySelector(`#row-${n} .prod-select`);
    const qty   = document.getElementById(`qty-${n}`);
    const warn  = document.getElementById(`warn-${n}`);
    const prod  = prodMap[sel?.value];
    if (!prod) return;
    const over  = parseInt(qty.value) > prod.stock;
    warn.style.display = over ? 'block' : 'none';
}

function removeRow(n) {
    const row = document.getElementById(`row-${n}`);
    const sel = row?.querySelector('.prod-select');
    if (sel?.value) usedProds.delete(sel.value);
    row?.remove();
    updateTotals();
    if (!document.querySelectorAll('#items-body tr').length) {
        document.getElementById('empty-items').style.display = 'block';
    }
}

function updateTotals() {
    const rows  = document.querySelectorAll('#items-body tr');
    let items   = 0, units = 0;
    rows.forEach(r => {
        const sel = r.querySelector('.prod-select');
        const qty = r.querySelector('.qty-input');
        if (sel?.value && qty && !qty.disabled) {
            items++;
            units += parseInt(qty.value) || 0;
        }
    });
    document.getElementById('total-items').textContent = items;
    document.getElementById('total-units').textContent = units;
}

// Validar y abrir modal de credenciales antes de enviar
function requestSave() {
    const toBranch = document.getElementById('to_branch').value.trim();
    if (!toBranch) { showAlert('Indica la sucursal de destino.', 'warning'); return; }
    const rows = document.querySelectorAll('#items-body tr');
    if (!rows.length) { showAlert('Agrega al menos un producto.', 'warning'); return; }

    // Validación rápida de items
    let valid = true;
    rows.forEach(r => {
        const sel = r.querySelector('.prod-select');
        const qty = r.querySelector('.qty-input');
        if (!sel?.value || parseInt(qty?.value) < 1) valid = false;
    });
    if (!valid) { showAlert('Revisa que todos los productos tengan cantidad válida.', 'warning'); return; }

    // Abrir modal
    document.getElementById('cred-user').value = '';
    document.getElementById('cred-pass').value = '';
    document.getElementById('cred-error').style.display = 'none';
    MicroModal.show('modal-cred', { disableFocus: false });
    setTimeout(() => document.getElementById('cred-user').focus(), 200);
}

async function submitCredAndSave() {
    const user  = document.getElementById('cred-user').value.trim();
    const pass  = document.getElementById('cred-pass').value;
    const errEl = document.getElementById('cred-error');
    const btn   = document.getElementById('btn-cred-ok');

    if (!user || !pass) {
        errEl.textContent = 'Ingresa usuario y contraseña.';
        errEl.style.display = 'block';
        return;
    }
    btn.disabled = true; btn.textContent = 'Verificando…';

    try {
        const vRes  = await fetch(`${BASE_URL}/branchTransfer/verifyCredentials`,
                         { method: 'POST', body: new URLSearchParams({ username: user, password: pass }) });
        const vData = await vRes.json();
        if (!vData.success) {
            errEl.textContent = vData.message;
            errEl.style.display = 'block';
            btn.disabled = false; btn.textContent = 'Confirmar y Enviar';
            return;
        }
    } catch (e) {
        errEl.textContent = 'Error de red.';
        errEl.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Confirmar y Enviar';
        return;
    }

    MicroModal.close('modal-cred');
    await saveTransfer(); // ejecutar traslado real
    btn.disabled = false; btn.textContent = 'Confirmar y Enviar';
}

async function saveTransfer() {
    const toBranch = document.getElementById('to_branch').value.trim();
    const notes    = document.getElementById('notes').value.trim();

    if (!toBranch) { showAlert('Indica la sucursal de destino.', 'warning'); return; }

    const rows = document.querySelectorAll('#items-body tr');
    if (!rows.length) { showAlert('Agrega al menos un producto.', 'warning'); return; }

    const items = [];
    let valid = true;
    rows.forEach(r => {
        const sel = r.querySelector('.prod-select');
        const qty = r.querySelector('.qty-input');
        if (!sel?.value) { valid = false; return; }
        const q = parseInt(qty?.value) || 0;
        if (q < 1) { valid = false; return; }
        items.push({ product_id: sel.value, quantity: q });
    });

    if (!valid || !items.length) { showAlert('Revisa que todos los productos tengan cantidad válida.', 'warning'); return; }

    const btn = document.getElementById('btn-save');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando…';

    const body = new URLSearchParams({ to_branch: toBranch, notes });
    items.forEach((it, i) => {
        body.append(`items[${i}][product_id]`, it.product_id);
        body.append(`items[${i}][quantity]`,   it.quantity);
    });

    try {
        const res  = await fetch(`${BASE_URL}/branchTransfer/save`, { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                window.location.href = `${BASE_URL}/branchTransfer/receipt/${data.transfer_id}`;
            }, 1200);
        } else {
            showAlert(data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Traslado';
        }
    } catch (e) {
        showAlert('Error de red: ' + e.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Traslado';
    }
}

function showAlert(msg, type) {
    const el = document.getElementById('alert-box');
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    el.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Agregar primera fila al cargar
document.addEventListener('DOMContentLoaded', addRow);
</script>
