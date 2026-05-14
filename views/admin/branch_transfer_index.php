<?php // views/admin/branch_transfer_index.php ?>

<!-- Modal de credenciales (compartido) -->
<div class="modal micromodal-slide" id="modal-cred" aria-hidden="true">
    <div class="modal__overlay" tabindex="-1">
        <div class="modal__container" role="dialog" aria-modal="true"
             aria-labelledby="modal-cred-title" style="max-width:380px;">
            <header class="modal__header">
                <h2 class="modal__title" id="modal-cred-title" style="font-size:1rem;">
                    <i class="fas fa-lock text-warning mr-1"></i>
                    <span id="cred-title-text">Confirmar Identidad</span>
                </h2>
                <button class="modal__close" aria-label="Cerrar"
                        onclick="MicroModal.close('modal-cred')"></button>
            </header>
            <div class="modal__content">
                <p id="cred-description" class="text-muted small mb-3">
                    Ingresa tus credenciales de administrador para continuar.
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
                        onclick="submitCredModal()">Confirmar</button>
            </footer>
        </div>
    </div>
</div>

<!-- Alerta de operación -->
<div id="index-alert" class="alert" style="display:none;margin:10px 16px 0;"></div>


<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center mb-2">
            <div class="col-sm-7">
                <h1><i class="fas fa-random"></i> Traslados entre Sucursales</h1>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item active">Traslados</li>
                </ol>
            </div>
            <?php if (($_SESSION['user']['role'] ?? '') === 'superadmin'): ?>
            <div class="col-sm-5 text-right">
                <a href="<?= APP_BASE ?>/branchTransfer/create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Traslado
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-3" id="transferTabs">
            <li class="nav-item">
                <a class="nav-link active" id="tab-pending" data-toggle="tab" href="#pending">
                    Pendientes de Recibir
                    <?php if (count($pendingTransfers) > 0): ?>
                    <span class="badge badge-warning ml-1"><?= count($pendingTransfers) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-history" data-toggle="tab" href="#history">
                    Historial
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ── Tab 1: Pendientes ── -->
            <div class="tab-pane fade show active" id="pending">
                <?php if (empty($pendingTransfers)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-check-circle mr-1"></i>
                    No hay traslados pendientes de recepción para <strong><?= htmlspecialchars($currentBranch) ?></strong>.
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($pendingTransfers as $t): ?>
                    <div class="col-lg-6 col-xl-4 mb-3">
                        <div class="card card-outline card-warning h-100">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-box-open text-warning"></i>
                                    Traslado #<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?>
                                </h5>
                                <div class="card-tools">
                                    <span class="badge badge-warning">Pendiente</span>
                                </div>
                            </div>
                            <div class="card-body py-2">
                                <table class="table table-sm table-borderless mb-0" style="font-size:.88rem;">
                                    <tr>
                                        <td class="text-muted" style="width:40%">Desde:</td>
                                        <td><strong><?= htmlspecialchars($t['from_branch']) ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Productos:</td>
                                        <td><?= $t['item_count'] ?> ítem(s) · <?= number_format($t['total_units']) ?> uds.</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Creado por:</td>
                                        <td><?= htmlspecialchars(trim($t['creator_name']) ?: $t['creator_username']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Fecha:</td>
                                        <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                    </tr>
                                    <?php if ($t['notes']): ?>
                                    <tr>
                                        <td class="text-muted">Nota:</td>
                                        <td class="small text-truncate" title="<?= htmlspecialchars($t['notes']) ?>" style="max-width:180px;">
                                            <?= htmlspecialchars($t['notes']) ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <div class="card-footer py-2 d-flex gap-1 justify-content-end">
                                <a href="<?= APP_BASE ?>/branchTransfer/receipt/<?= $t['id'] ?>"
                                   class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <a href="<?= APP_BASE ?>/branchTransfer/receive/<?= $t['id'] ?>"
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> Recibir
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Tab 2: Historial ── -->
            <div class="tab-pane fade" id="history">
                <div class="card card-outline card-dark">
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover table-striped mb-0" id="history-tbl">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Desde</th>
                                    <th>Hacia</th>
                                    <th>Ítems</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Creado por</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($allTransfers as $t):
                                $badge = match($t['status']) {
                                    'pendiente'  => 'warning',
                                    'recibido'   => 'success',
                                    'cancelado'  => 'danger',
                                    default      => 'secondary',
                                };
                                $label = match($t['status']) {
                                    'pendiente'  => 'Pendiente',
                                    'recibido'   => 'Recibido',
                                    'cancelado'  => 'Cancelado',
                                    default      => $t['status'],
                                };
                            ?>
                            <tr>
                                <td><code>#<?= str_pad($t['id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                                <td><?= htmlspecialchars($t['from_branch']) ?></td>
                                <td><?= htmlspecialchars($t['to_branch']) ?></td>
                                <td><?= $t['item_count'] ?> · <?= number_format($t['total_units']) ?> uds.</td>
                                <td><span class="badge badge-<?= $badge ?>"><?= $label ?></span></td>
                                <td class="text-nowrap small"><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
                                <td class="small"><?= htmlspecialchars(trim($t['creator_name']) ?: $t['creator_username']) ?></td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= APP_BASE ?>/branchTransfer/receipt/<?= $t['id'] ?>"
                                       class="btn btn-xs btn-outline-secondary" target="_blank" title="Ver boleta">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                    <?php if ($t['status'] === 'pendiente'
                                              && $t['to_branch'] === $currentBranch): ?>
                                    <a href="<?= APP_BASE ?>/branchTransfer/receive/<?= $t['id'] ?>"
                                       class="btn btn-xs btn-success" title="Recibir">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($t['status'] === 'pendiente'
                                              && ($_SESSION['user']['role'] ?? '') === 'superadmin'): ?>
                                    <button class="btn btn-xs btn-danger"
                                            onclick="cancelTransfer(<?= $t['id'] ?>)" title="Cancelar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
const BASE_URL     = '<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>';
let _credCallback  = null; // función a ejecutar tras verificar credenciales

function showCredModal(title, description, callback) {
    document.getElementById('cred-title-text').textContent   = title;
    document.getElementById('cred-description').textContent  = description;
    document.getElementById('cred-user').value = '';
    document.getElementById('cred-pass').value = '';
    document.getElementById('cred-error').style.display = 'none';
    _credCallback = callback;
    MicroModal.show('modal-cred', { disableFocus: false });
    setTimeout(() => document.getElementById('cred-user').focus(), 200);
}

async function submitCredModal() {
    const user = document.getElementById('cred-user').value.trim();
    const pass = document.getElementById('cred-pass').value;
    const errEl = document.getElementById('cred-error');
    const btn   = document.getElementById('btn-cred-ok');

    if (!user || !pass) {
        errEl.textContent = 'Ingresa usuario y contraseña.';
        errEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Verificando…';

    const body = new URLSearchParams({ username: user, password: pass });
    try {
        const res  = await fetch(`${BASE_URL}/branchTransfer/verifyCredentials`, { method: 'POST', body });
        const data = await res.json();
        if (data.success) {
            MicroModal.close('modal-cred');
            if (_credCallback) _credCallback();
        } else {
            errEl.textContent = data.message;
            errEl.style.display = 'block';
        }
    } catch (e) {
        errEl.textContent = 'Error de conexión.';
        errEl.style.display = 'block';
    }
    btn.disabled = false;
    btn.textContent = 'Confirmar';
}

// Enter en el campo de contraseña dispara el confirm
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('cred-pass')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') submitCredModal();
    });
});

function showAlert(msg, type) {
    const el = document.getElementById('index-alert');
    el.className = `alert alert-${type}`;
    el.textContent = msg;
    el.style.display = 'block';
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (type === 'success') setTimeout(() => { el.style.display = 'none'; }, 5000);
}

function cancelTransfer(id) {
    showCredModal(
        'Cancelar Traslado',
        `¿Cancelar el traslado #${String(id).padStart(4,'0')}? El stock regresará al origen. Ingresa tus credenciales.`,
        async function () {
            try {
                const res  = await fetch(`${BASE_URL}/branchTransfer/cancel/${id}`, { method: 'POST' });
                const data = await res.json();
                showAlert(data.message, data.success ? 'success' : 'danger');
                if (data.success) setTimeout(() => location.reload(), 1800);
            } catch (e) {
                showAlert('Error de red: ' + e.message, 'danger');
            }
        }
    );
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof $.fn.DataTable !== 'undefined') {
        $('#history-tbl').DataTable({
            order: [[5, 'desc']],
            pageLength: 20,
            language: {
                paginate: { previous: 'Ant.', next: 'Sig.' },
                info: 'Mostrando _START_–_END_ de _TOTAL_',
            }
        });
    }
});
</script>
