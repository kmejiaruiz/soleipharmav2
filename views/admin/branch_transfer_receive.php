<?php // views/admin/branch_transfer_receive.php
$transferNum = str_pad($transfer['id'], 4, '0', STR_PAD_LEFT);
?>

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
                    Se requieren credenciales de admin para registrar la recepción.
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
                <button class="btn btn-sm btn-success" id="btn-cred-ok"
                        onclick="submitCredAndReceive()">Confirmar Recepción</button>
            </footer>
        </div>
    </div>
</div>

<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-boxes"></i> Recibir Traslado #<?= $transferNum ?></h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/branchTransfer/index">Traslados</a></li>
                    <li class="breadcrumb-item active">Recibir #<?= $transferNum ?></li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
<div class="row justify-content-center">
<div class="col-lg-8">

    <div id="alert-box" class="alert" style="display:none;"></div>

    <!-- Info del traslado -->
    <div class="card card-outline card-warning mb-3">
        <div class="card-body py-2">
            <div class="row text-center">
                <div class="col-4">
                    <div class="text-muted small">Desde</div>
                    <div class="font-weight-bold"><?= htmlspecialchars($transfer['from_branch']) ?></div>
                </div>
                <div class="col-4 d-flex align-items-center justify-content-center text-muted">
                    <i class="fas fa-long-arrow-alt-right fa-2x"></i>
                </div>
                <div class="col-4">
                    <div class="text-muted small">Hacia</div>
                    <div class="font-weight-bold"><?= htmlspecialchars($transfer['to_branch']) ?></div>
                </div>
            </div>
            <?php if ($transfer['notes']): ?>
            <div class="mt-2 text-center text-muted small">
                <i class="fas fa-sticky-note"></i>
                <?= htmlspecialchars($transfer['notes']) ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de ítems -->
    <div class="card card-outline card-dark">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list-check"></i> Verificar Cantidades Recibidas</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th class="text-center">Enviadas</th>
                        <th class="text-center" style="width:140px;">Recibidas <span class="text-warning">*</span></th>
                        <th class="text-center">Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr id="item-row-<?= $item['id'] ?>">
                        <td><code><?= htmlspecialchars($item['sku'] ?? '—') ?></code></td>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td class="text-center font-weight-bold"><?= number_format($item['quantity_sent']) ?></td>
                        <td class="text-center">
                            <input type="number" class="form-control form-control-sm text-center qty-recv"
                                   id="qty-<?= $item['id'] ?>"
                                   data-sent="<?= $item['quantity_sent'] ?>"
                                   data-item="<?= $item['id'] ?>"
                                   min="0" max="<?= $item['quantity_sent'] ?>"
                                   value="<?= $item['quantity_sent'] ?>"
                                   oninput="updateDiff(<?= $item['id'] ?>, <?= $item['quantity_sent'] ?>)"
                                   style="width:90px;display:inline-block;">
                        </td>
                        <td class="text-center" id="diff-<?= $item['id'] ?>">
                            <span class="badge badge-success">0</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                Ajusta las cantidades si hay faltantes. Las diferencias quedarán registradas en la boleta.
            </small>
            <div>
                <a href="<?= APP_BASE ?>/branchTransfer/index" class="btn btn-secondary mr-2">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button class="btn btn-success" id="btn-confirm" onclick="requestReceive()">
                    <i class="fas fa-check-double"></i> Confirmar Recepción
                </button>
            </div>
        </div>
    </div>

</div>
</div>
</div>
</section>

<script>
const BASE_URL    = '<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>';
const TRANSFER_ID = <?= $transfer['id'] ?>;

function requestReceive() {
    document.getElementById('cred-user').value = '';
    document.getElementById('cred-pass').value = '';
    document.getElementById('cred-error').style.display = 'none';
    MicroModal.show('modal-cred', { disableFocus: false });
    setTimeout(() => document.getElementById('cred-user').focus(), 200);
}

async function submitCredAndReceive() {
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
            btn.disabled = false; btn.textContent = 'Confirmar Recepción';
            return;
        }
    } catch (e) {
        errEl.textContent = 'Error de red.';
        errEl.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Confirmar Recepción';
        return;
    }

    MicroModal.close('modal-cred');
    await confirmReceive();
    btn.disabled = false; btn.textContent = 'Confirmar Recepción';
}

function updateDiff(itemId, sent) {
    const recv  = parseInt(document.getElementById(`qty-${itemId}`).value) || 0;
    const diff  = recv - sent;
    const el    = document.getElementById(`diff-${itemId}`);
    if (diff === 0) {
        el.innerHTML = '<span class="badge badge-success">0</span>';
    } else if (diff < 0) {
        el.innerHTML = `<span class="badge badge-danger">${diff}</span>`;
    } else {
        el.innerHTML = `<span class="badge badge-warning">+${diff}</span>`;
    }
}

async function confirmReceive() {
    const confirm = await Swal.fire({
        title: '¿Confirmar recepción?',
        html:  'El stock de esta sucursal se actualizará con las cantidades ingresadas.',
        icon:  'question',
        showCancelButton:  true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText:  'Cancelar'
    });
    if (!confirm.isConfirmed) return;

    const btn  = document.getElementById('btn-confirm');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando…';

    const body = new URLSearchParams({ transfer_id: TRANSFER_ID });
    document.querySelectorAll('.qty-recv').forEach(inp => {
        body.append(`quantities[${inp.dataset.item}]`, inp.value);
    });

    try {
        const res  = await fetch(`${BASE_URL}/branchTransfer/confirmReceive`, { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
            showAlert(data.message + ' Redirigiendo a la boleta…', 'success');
            setTimeout(() => { window.location.href = data.receipt_url; }, 1500);
        } else {
            showAlert(data.message, 'danger');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-double"></i> Confirmar Recepción';
        }
    } catch (e) {
        showAlert('Error de red: ' + e.message, 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-double"></i> Confirmar Recepción';
    }
}

function showAlert(msg, type) {
    const el = document.getElementById('alert-box');
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    el.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Inicializar diffs
document.querySelectorAll('.qty-recv').forEach(inp => {
    updateDiff(inp.dataset.item, parseInt(inp.dataset.sent));
});
</script>
