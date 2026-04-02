<?php // views/admin/cash_history.php ?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-7">
                <h1><i class="fas fa-history"></i> Historial de Caja</h1>
            </div>
            <div class="col-sm-5 text-right">
                <a href="/soleipharmav2/cash/index" class="btn btn-sm btn-success">
                    <i class="fas fa-cash-register"></i> Ir a Caja
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <!-- Current cash balance banner -->
    <?php if ($openSession && $currentCash !== null): ?>
    <div class="alert alert-success d-flex align-items-center justify-content-between">
        <span>
            <i class="fas fa-lock-open"></i>
            <strong>Caja Abierta</strong> &mdash; por
            <strong><?= htmlspecialchars(ucwords(strtolower($openSession['opener_name']))) ?></strong>
            desde <?php
                $dtO = new DateTime($openSession['opened_at']);
                $dtO->setTimezone(new DateTimeZone('America/Managua'));
                echo $dtO->format('d/m/Y H:i');
            ?>
        </span>
        <span style="font-size:1.3em;font-weight:700;">
            Efectivo en caja: C$ <?= number_format($currentCash, 2) ?>
        </span>
    </div>
    <?php else: ?>
    <div class="alert alert-secondary">
        <i class="fas fa-lock"></i> No hay caja abierta en este momento.
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <form method="GET" action="/soleipharmav2/cash/history" class="form-inline">
                <label class="mr-2"><i class="fas fa-user"></i> Cajero:</label>
                <select name="user_id" class="form-control form-control-sm mr-3">
                    <option value="0">— Todos —</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $filterUser == $u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars(ucwords(strtolower($u['full_name']))) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>

                <label class="mr-2"><i class="fas fa-calendar"></i> Fecha:</label>
                <input type="date" name="date" class="form-control form-control-sm mr-3"
                       value="<?= htmlspecialchars($filterDate) ?>">

                <button type="submit" class="btn btn-sm btn-primary mr-2">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="/soleipharmav2/cash/history" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </form>
        </div>
    </div>

    <!-- Sessions list -->
    <?php if (empty($sessions)): ?>
    <div class="card"><div class="card-body text-center text-muted py-5">
        <i class="fas fa-inbox fa-3x mb-3"></i><br>No se encontraron sesiones con los filtros seleccionados.
    </div></div>
    <?php else: ?>

    <?php foreach ($sessions as $s):
        $isOpen         = $s['status'] === 'open';
        $isPending      = $s['status'] === 'pending_close';
        $isActive       = $isOpen || $isPending;
        $openedDt = new DateTime($s['opened_at']); $openedDt->setTimezone(new DateTimeZone('America/Managua'));
        $closedDt = $s['closed_at'] ? (new DateTime($s['closed_at']))->setTimezone(new DateTimeZone('America/Managua')) : null;
        $diff     = floatval($s['difference'] ?? 0);
        $expected = floatval($s['expected_amount'] ?? (floatval($s['opening_amount']) + floatval($s['total_sales']) - floatval($s['total_withdrawals'])));
        $sessionWithdrawals = $withdrawalsBySession[$s['id']] ?? [];
        $cardColor = $isOpen ? 'card-success' : ($isPending ? 'card-warning' : 'card-secondary');
    ?>
    <div class="card card-outline <?= $cardColor ?> mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?php if ($isOpen): ?>
                <span class="badge badge-success mr-2">ABIERTA</span>
                <?php elseif ($isPending): ?>
                <span class="badge badge-warning mr-2">POR CERRAR</span>
                <?php else: ?>
                <span class="badge badge-secondary mr-2">CERRADA</span>
                <?php endif; ?>
                Sesión #<?= str_pad($s['id'], 4, '0', STR_PAD_LEFT) ?>
                &mdash; <strong><?= htmlspecialchars(ucwords(strtolower($s['opener_name']))) ?></strong>
                <small class="text-muted ml-2">(<?= htmlspecialchars($s['opener_username']) ?>)</small>
            </h5>
            <div>
                <?php if (!$isActive): ?>
                <a href="/soleipharmav2/cash/closingPdf/<?= $s['id'] ?>" target="_blank"
                   class="btn btn-xs btn-outline-dark mr-1" title="Reporte de cierre">
                    <i class="fas fa-file-pdf"></i> Cierre PDF
                </a>
                <?php elseif ($isPending): ?>
                <button class="btn btn-xs btn-danger mr-1"
                        onclick="openCloseModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes(ucwords(strtolower($s['opener_name'])))) ?>', <?= $expected ?>)">
                    <i class="fas fa-times-circle"></i> Confirmar Cierre
                </button>
                <?php endif; ?>
                <?php if (in_array($_SESSION['user']['role'] ?? '', ['admin','superadmin'])): ?>
                <button class="btn btn-xs btn-outline-info mr-1"
                        onclick="openSalesModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($openedDt->format('d/m/Y'))) ?>', <?= $isActive ? 'true' : 'false' ?>)"
                        title="Ver ventas de esta sesión">
                    <i class="fas fa-receipt"></i> Ventas
                </button>
                <?php endif; ?>
                <button class="btn btn-xs btn-outline-secondary" type="button"
                        data-toggle="collapse" data-target="#session-<?= $s['id'] ?>">
                    <i class="fas fa-chevron-down"></i> Detalles
                </button>
            </div>
        </div>

        <!-- Summary row -->
        <div class="card-body py-2">
            <div class="row text-center">
                <div class="col">
                    <small class="text-muted d-block">Apertura</small>
                    <strong><?= $openedDt->format('d/m/Y H:i') ?></strong>
                </div>
                <div class="col">
                    <small class="text-muted d-block">Fondo</small>
                    <strong>C$ <?= number_format($s['opening_amount'], 2) ?></strong>
                </div>
                <div class="col">
                    <small class="text-muted d-block">Ventas</small>
                    <strong class="text-info">C$ <?= number_format($s['total_sales'], 2) ?></strong>
                </div>
                <div class="col">
                    <small class="text-muted d-block">Retiros</small>
                    <strong class="text-warning">C$ <?= number_format($s['total_withdrawals'], 2) ?></strong>
                </div>
                <div class="col">
                    <small class="text-muted d-block">Esperado</small>
                    <strong>C$ <?= number_format($expected, 2) ?></strong>
                </div>
                <div class="col">
                    <?php if ($isOpen): ?>
                    <small class="text-muted d-block">En caja ahora</small>
                    <strong class="text-success">C$ <?= number_format($currentCash ?? $expected, 2) ?></strong>
                    <?php elseif ($s['counted_amount'] !== null): ?>
                    <small class="text-muted d-block">
                        <?= abs($diff) < 0.01 ? 'Cuadre' : ($diff > 0 ? 'Sobrante' : 'Faltante') ?>
                    </small>
                    <strong class="<?= abs($diff)<0.01 ? 'text-success' : ($diff>0 ? 'text-warning':'text-danger') ?>">
                        C$ <?= number_format(abs($diff), 2) ?>
                    </strong>
                    <?php else: ?>
                    <small class="text-muted d-block">Conteo</small>
                    <span class="text-muted">N/D</span>
                    <?php endif; ?>
                </div>
                <?php if (!$isOpen && $closedDt): ?>
                <div class="col">
                    <small class="text-muted d-block">Cierre</small>
                    <strong><?= $closedDt->format('d/m/Y H:i') ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Collapse: withdrawals detail -->
        <?php if (!empty($sessionWithdrawals)): ?>
        <div class="collapse" id="session-<?= $s['id'] ?>">
            <div class="card-footer p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th colspan="5" class="py-1 px-3 text-muted small">
                                <i class="fas fa-hand-holding-usd"></i> Retiros de esta sesión
                            </th>
                        </tr>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Cajero</th>
                            <th>Motivo</th>
                            <th class="text-right">Monto</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sessionWithdrawals as $w):
                        $wDt = new DateTime($w['created_at']); $wDt->setTimezone(new DateTimeZone('America/Managua'));
                    ?>
                    <tr>
                        <td><?= $wDt->format('d/m H:i') ?></td>
                        <td><?= htmlspecialchars(ucwords(strtolower($w['withdrawer_name']))) ?></td>
                        <td><?= htmlspecialchars($w['reason'] ?? '—') ?></td>
                        <td class="text-right font-weight-bold">C$ <?= number_format($w['total_amount'], 2) ?></td>
                        <td>
                            <a href="/soleipharmav2/cash/withdrawalPdf/<?= $w['id'] ?>" target="_blank"
                               class="btn btn-xs btn-outline-secondary">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                        <tr class="table-warning">
                            <td colspan="3" class="text-right font-weight-bold">Total retiros:</td>
                            <td class="text-right font-weight-bold">C$ <?= number_format($s['total_withdrawals'], 2) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif (!$isOpen): ?>
        <div class="collapse" id="session-<?= $s['id'] ?>">
            <div class="card-footer text-muted text-center py-2 small">Sin retiros en esta sesión.</div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</section>

<!-- ── Modal: Ventas de una sesión ──────────────────────────────────────────── -->
<div class="modal fade" id="sessionSalesModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h5 class="modal-title">
                    <i class="fas fa-receipt mr-1"></i>
                    Ventas — Sesión <span id="ssmDate"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <div id="ssmLoading" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                    <p class="text-muted mt-2">Cargando ventas…</p>
                </div>
                <div id="ssmContent" class="d-none">
                    <div id="ssmEmpty" class="text-center text-muted py-5 d-none">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mt-2">No hay ventas anuladas en esta sesión.</p>
                    </div>
                    <table class="table table-sm table-hover mb-0" id="ssmTable" style="display:none">
                        <thead class="thead-light">
                            <tr>
                                <th>Recibo</th>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Método</th>
                                <th class="text-right">Total</th>
                                <th>Estado</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="ssmBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: Cierre de caja por admin (denominaciones — no usa ActionModal por UI compleja) -->
<div class="modal fade" id="adminCloseCashModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle"></i>
                    Cerrar Caja de <span id="acmCajero"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="adminCloseCashForm">
                <input type="hidden" id="acmSessionId" name="session_id">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>¿Está seguro que desea cerrar esta caja?</strong><br>
                        Ingrese el conteo físico del efectivo presente.
                    </div>
                    <h6 class="text-center mb-3"><i class="fas fa-coins"></i> Conteo Físico de Efectivo</h6>
                    <div class="row">
                        <?php
                        $denoms = [1000=>'C$1,000', 500=>'C$500', 200=>'C$200', 100=>'C$100',
                                   50=>'C$50', 20=>'C$20', 10=>'C$10', 5=>'C$5', 1=>'C$1'];
                        foreach ($denoms as $val => $label): ?>
                        <div class="col-md-4 mb-2">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="min-width:70px;font-weight:600;"><?= $label ?></span>
                                </div>
                                <input type="number" class="form-control acm-denom-input" name="denominations[<?= $val ?>]"
                                       data-value="<?= $val ?>" min="0" value="0">
                                <div class="input-group-append">
                                    <span class="input-group-text acm-subtotal" data-for="<?= $val ?>">= C$0</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-right mt-3 p-3 bg-light rounded">
                        <h5>Total Contado: <strong class="text-success" id="acmTotal">C$ 0.00</strong></h5>
                        <p class="mb-0 text-muted small">
                            Efectivo esperado: <strong id="acmExpectedDisplay">C$ 0.00</strong>
                        </p>
                        <p class="mb-0" id="acmDiffLabel"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="acmBtnConfirm">
                        <i class="fas fa-check"></i> Confirmar Cierre
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let acmExpected = 0;

function openCloseModal(sessionId, cajeroName, expected) {
    acmExpected = parseFloat(expected) || 0;
    document.getElementById('acmSessionId').value = sessionId;
    document.getElementById('acmCajero').textContent = cajeroName;
    document.getElementById('acmExpectedDisplay').textContent =
        'C$ ' + acmExpected.toLocaleString('es-NI', {minimumFractionDigits:2});
    document.querySelectorAll('.acm-denom-input').forEach(i => { i.value = 0; });
    document.querySelectorAll('.acm-subtotal').forEach(s => { s.textContent = '= C$0'; });
    document.getElementById('acmTotal').textContent = 'C$ 0.00';
    document.getElementById('acmDiffLabel').textContent = '';
    $('#adminCloseCashModal').modal('show');
}

document.querySelectorAll('.acm-denom-input').forEach(inp => {
    inp.addEventListener('input', function() {
        const val = parseInt(this.dataset.value);
        const qty = Math.max(0, parseInt(this.value) || 0);
        document.querySelector(`.acm-subtotal[data-for="${val}"]`).textContent =
            `= C$${(val * qty).toLocaleString()}`;
        updateAcmTotal();
    });
});

function updateAcmTotal() {
    let total = 0;
    document.querySelectorAll('.acm-denom-input').forEach(inp => {
        total += parseInt(inp.dataset.value) * (Math.max(0, parseInt(inp.value) || 0));
    });
    document.getElementById('acmTotal').textContent =
        `C$ ${total.toLocaleString('es-NI', {minimumFractionDigits:2})}`;
    const diff = total - acmExpected;
    const lbl  = document.getElementById('acmDiffLabel');
    if (Math.abs(diff) < 0.01) {
        lbl.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> Cuadre perfecto</span>';
    } else if (diff > 0) {
        lbl.innerHTML = `<span class="text-warning"><i class="fas fa-arrow-up"></i> Sobrante: C$ ${diff.toFixed(2)}</span>`;
    } else {
        lbl.innerHTML = `<span class="text-danger"><i class="fas fa-arrow-down"></i> Faltante: C$ ${Math.abs(diff).toFixed(2)}</span>`;
    }
}

document.getElementById('adminCloseCashForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('acmBtnConfirm');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cerrando...';

    const fd  = new FormData(this);
    const res = await fetch('/soleipharmav2/cash/closeCash', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
        $('#adminCloseCashModal').modal('hide');
        Swal.fire({
            icon: 'success',
            title: 'Caja Cerrada',
            text: 'La caja fue cerrada correctamente.',
            confirmButtonText: 'Ver Reporte PDF'
        }).then(() => {
            window.open(data.pdf_url, '_blank');
            location.reload();
        });
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Confirmar Cierre';
    }
});

// ── Anular Venta ──────────────────────────────────────────────────────────────
function voidSale(orderId, label) {
    window.ActionModal.show({
        title: `Anular Venta ${label}`,
        icon:  'fas fa-ban text-danger',
        body:  `<p class="text-danger mb-3"><strong>Esta acción es irreversible.</strong><br>Se restaurará el stock de todos los productos de la venta.</p>`,
        fields: [
            { name: 'password', label: 'Contraseña del Administrador', type: 'password', placeholder: '••••••••', required: true }
        ],
        confirmText:  'Confirmar Anulación',
        confirmClass: 'btn-danger',
        onConfirm: async (values) => {
            const fd = new FormData();
            fd.append('order_id', orderId);
            fd.append('password', values.password);

            const res  = await fetch('/soleipharmav2/cash/voidSale', { method: 'POST', body: fd });
            const data = await res.json();

            // Cerrar ActionModal ANTES de mostrar Swal (patrón estándar del sistema)
            window.ActionModal.hide();

            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Venta Anulada', text: data.message, timer: 2000, showConfirmButton: false })
                    .then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        }
    });
}
</script>
