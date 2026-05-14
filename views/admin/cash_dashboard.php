<?php // views/admin/cash_dashboard.php
$openedAt = new DateTime($session['opened_at']);
$openedAt->setTimezone(new DateTimeZone('America/Managua'));
$isPendingClose = ($session['status'] === 'pending_close');
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-7">
                <h1>
                    <i class="fas fa-cash-register <?= $isPendingClose ? 'text-warning' : 'text-success' ?>"></i>
                    Panel de Caja
                    <?php if ($isPendingClose): ?>
                    <span class="badge badge-warning ml-2" style="font-size:12px;">POR CERRAR</span>
                    <?php else: ?>
                    <span class="badge badge-success ml-2" style="font-size:12px;">ABIERTA</span>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-sm-5 text-right">
                <button class="btn btn-secondary btn-sm" id="btnReprint" title="Reimpresiones">
                    <i class="fas fa-print"></i> Reimpresiones
                </button>
                <?php if ($isAdmin): ?>
                <button class="btn btn-outline-danger btn-sm ml-2" id="btnVoidSales" title="Anular ventas del día">
                    <i class="fas fa-ban"></i> Anular Ventas
                </button>
                <?php endif; ?>
                <?php if (!$isPendingClose): ?>
                <button class="btn btn-danger btn-sm ml-2" id="btnRequestClose">
                    <i class="fas fa-times-circle"></i> Solicitar Cierre de Caja
                </button>
                <a href="<?= APP_BASE ?>/cash/withdrawal" class="btn btn-warning btn-sm ml-2">
                    <i class="fas fa-hand-holding-usd"></i> Registrar Retiro
                </a>
                <a href="<?= APP_BASE ?>/cash/pos" class="btn btn-primary btn-sm ml-2">
                    <i class="fas fa-cash-register"></i> Facturar / POS
                </a>
                <?php else: ?>
                <span class="badge badge-warning p-2 ml-2" style="font-size:13px;">
                    <i class="fas fa-hourglass-half"></i> Esperando cierre por administrador
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">

    <?php if ($isPendingClose): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Caja en proceso de cierre.</strong>
        Esta caja ha sido marcada para cerrar. Un administrador completará el proceso desde el
        <a href="<?= APP_BASE ?>/cash/history">Historial de Caja</a>.
    </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); endif; ?>

    <!-- Stats Row -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-lock-open"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Fondo Apertura</span>
                    <span class="info-box-number">C$ <?= number_format($session['opening_amount'], 2) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Ventas del Turno</span>
                    <span class="info-box-number">C$ <?= number_format($salesData['total_sales'], 2) ?></span>
                    <span class="progress-description"><?= $salesData['total_orders'] ?> órdenes</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Retiros</span>
                    <span class="info-box-number">C$ <?= number_format($totalWithdrawn, 2) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box bg-primary">
                <span class="info-box-icon"><i class="fas fa-coins"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Efectivo Esperado</span>
                    <span class="info-box-number">C$ <?= number_format($expectedCash, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Desglose por Método de Pago -->
    <?php
    $ef = floatval($payBreakdown['efectivo']['subtotal']      ?? 0);
    $tj = floatval($payBreakdown['tarjeta']['subtotal']       ?? 0);
    $tr = floatval($payBreakdown['transferencia']['subtotal'] ?? 0);
    if ($ef + $tj + $tr > 0):
    ?>
    <div class="row mb-2">
        <div class="col-12">
            <div class="card card-outline card-info mb-0">
                <div class="card-header py-2">
                    <h3 class="card-title"><i class="fas fa-credit-card"></i> Desglose por Método de Pago — Turno actual</h3>
                </div>
                <div class="card-body p-0">
                    <div class="row text-center" style="margin:0;">
                        <div class="col-4 py-2 border-right">
                            <div class="text-muted small">💵 Efectivo</div>
                            <div class="font-weight-bold text-success">C$ <?= number_format($ef, 2) ?></div>
                            <small class="text-muted"><?= intval($payBreakdown['efectivo']['qty'] ?? 0) ?> ventas</small>
                        </div>
                        <div class="col-4 py-2 border-right">
                            <div class="text-muted small">💳 Tarjeta</div>
                            <div class="font-weight-bold text-primary">C$ <?= number_format($tj, 2) ?></div>
                            <small class="text-muted"><?= intval($payBreakdown['tarjeta']['qty'] ?? 0) ?> ventas</small>
                        </div>
                        <div class="col-4 py-2">
                            <div class="text-muted small">🏦 Transferencia</div>
                            <div class="font-weight-bold text-info">C$ <?= number_format($tr, 2) ?></div>
                            <small class="text-muted"><?= intval($payBreakdown['transferencia']['qty'] ?? 0) ?> ventas</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Session info -->
        <div class="col-md-5">
            <div class="card card-outline <?= $isPendingClose ? 'card-warning' : 'card-success' ?>">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Información de Sesión</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Abierta por:</th>
                            <td><?= htmlspecialchars(ucwords(strtolower($session['opener_name']))) ?>
                                <small class="text-muted">(<?= htmlspecialchars($session['opener_username']) ?>)</small>
                            </td>
                        </tr>
                        <tr><th>Desde:</th><td><?= $openedAt->format('d/m/Y H:i:s') ?></td></tr>
                        <tr><th>Sucursal:</th><td><?= htmlspecialchars(defined('BRANCH') ? BRANCH : 'Principal') ?></td></tr>
                        <tr>
                            <th>Estado:</th>
                            <td>
                                <?php if ($isPendingClose): ?>
                                <span class="badge badge-warning">POR CERRAR</span>
                                <?php else: ?>
                                <span class="badge badge-success">ABIERTA</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($session['notes']): ?>
                        <tr><th>Notas:</th><td><?= htmlspecialchars($session['notes']) ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Withdrawals table -->
        <div class="col-md-7">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Retiros Registrados</h3>
                    <?php if (!$isPendingClose): ?>
                    <div class="card-tools">
                        <a href="<?= APP_BASE ?>/cash/withdrawal" class="btn btn-sm btn-warning">
                            <i class="fas fa-plus"></i> Nuevo Retiro
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($withdrawals)): ?>
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-inbox fa-2x"></i><br>Sin retiros registrados
                    </div>
                    <?php else: ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>Hora</th><th>Cajero</th><th class="text-right">Monto</th><th>Motivo</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($withdrawals as $w): ?>
                            <tr>
                                <td><small><?= date('H:i', strtotime($w['created_at'])) ?></small></td>
                                <td><small><?= htmlspecialchars(ucwords(strtolower($w['withdrawer_name']))) ?></small></td>
                                <td class="text-right"><strong>C$ <?= number_format($w['total_amount'], 2) ?></strong></td>
                                <td><small><?= htmlspecialchars($w['reason'] ?? '—') ?></small></td>
                                <td>
                                    <a href="<?= APP_BASE ?>/cash/withdrawalPdf/<?= $w['id'] ?>" target="_blank"
                                       class="btn btn-xs btn-outline-secondary" title="Ver comprobante">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
</section>

<script>
document.getElementById('btnRequestClose')?.addEventListener('click', () => {
    window.ActionModal.show({
        title: 'Solicitar Cierre de Caja',
        description: 'Para solicitar el cierre de caja confirme su identidad ingresando sus credenciales.',
        fields: [
            {
                id: 'rc_username',
                type: 'text',
                label: '<i class="fas fa-user"></i> Usuario',
                placeholder: 'Su usuario del sistema'
            },
            {
                id: 'rc_password',
                type: 'password',
                label: '<i class="fas fa-lock"></i> Contraseña',
                placeholder: 'Su contraseña'
            }
        ],
        confirmText: 'Confirmar Solicitud',
        cancelText: 'Cancelar',
        onConfirm: async function(data) {
            const username = (data['rc_username'] || '').trim();
            const password = (data['rc_password'] || '').trim();

            if (!username || !password) {
                window.ActionModal.showError('Usuario y contraseña son requeridos.');
                return;
            }

            window.ActionModal.hide();

            const fd = new FormData();
            fd.append('username', username);
            fd.append('password', password);

            const res  = await fetch('<?= APP_BASE ?>/cash/requestClose', { method: 'POST', body: fd });
            const resp = await res.json();

            if (resp.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Solicitud Enviada',
                    text: resp.message,
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: resp.message });
            }
        }
    });

    // Pre-fill username field after modal opens
    setTimeout(() => {
        const uField = document.getElementById('rc_username');
        if (uField) uField.value = '<?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?>';
        const pField = document.getElementById('rc_password');
        if (pField) pField.focus();
    }, 150);
});
</script>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  MODAL 1: Autenticación admin para reimpresiones     ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<div class="modal micromodal-slide" id="modalReprintAuth" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modalReprintAuthLabel" style="max-width:420px;">
      <header class="modal__header" style="background:#343a40;color:#fff;border-radius:4px 4px 0 0;padding:16px 20px;margin:-30px -30px 20px;">
        <h5 class="modal__title" id="modalReprintAuthLabel" style="color:#fff;font-size:1rem;">
          <i class="fas fa-lock"></i> Acceso a Reimpresiones
        </h5>
        <button class="modal__close" aria-label="Cerrar" data-micromodal-close style="color:#fff;"></button>
      </header>
      <main class="modal__content">
        <p class="text-muted small mb-3">Ingrese credenciales de <strong>admin</strong> o <strong>superadmin</strong> para continuar.</p>
        <div class="form-group mb-2">
          <label class="small mb-1"><i class="fas fa-user"></i> Usuario</label>
          <input type="text" id="rpAuthUser" class="form-control form-control-sm" placeholder="Nombre de usuario" autocomplete="off">
        </div>
        <div class="form-group mb-1">
          <label class="small mb-1"><i class="fas fa-lock"></i> Contraseña</label>
          <input type="password" id="rpAuthPass" class="form-control form-control-sm" placeholder="Contraseña">
        </div>
        <div id="rpAuthError" class="text-danger small mt-2" style="display:none;"></div>
      </main>
      <footer class="modal__footer" style="justify-content:flex-end;gap:8px;">
        <button type="button" class="modal__btn" data-micromodal-close aria-label="Cancelar">Cancelar</button>
        <button type="button" class="modal__btn modal__btn-primary" id="btnReprintAuthConfirm">
          <i class="fas fa-sign-in-alt"></i> Ingresar
        </button>
      </footer>
    </div>
  </div>
</div>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  MODAL 2: Panel de Reimpresiones                     ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<div class="modal micromodal-slide" id="modalReprintPanel" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="modalReprintPanelLabel" style="max-width:900px;width:95%;padding:0;">
      <header class="modal__header" style="background:#343a40;color:#fff;border-radius:4px 4px 0 0;padding:14px 20px;">
        <h5 class="modal__title" id="modalReprintPanelLabel" style="color:#fff;font-size:1rem;">
          <i class="fas fa-print"></i> Panel de Reimpresiones
        </h5>
        <button class="modal__close" aria-label="Cerrar" data-micromodal-close style="color:#fff;"></button>
      </header>
      <main class="modal__content" style="padding:0;margin:0;">

        <!-- Tabs -->
        <ul class="nav nav-tabs px-3 pt-2" id="reprintTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="tab-current-tab" data-toggle="tab" href="#tab-current" role="tab">
              <i class="fas fa-user-clock"></i> Ventas de Esta Sesión
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="tab-history-tab" data-toggle="tab" href="#tab-history" role="tab">
              <i class="fas fa-history"></i> Ventas Anteriores
            </a>
          </li>
        </ul>

        <div class="tab-content p-3" id="reprintTabContent">

          <!-- TAB: Ventas sesión actual -->
          <div class="tab-pane fade show active" id="tab-current" role="tabpanel">
            <div id="rpCurrentLoading" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Cargando ventas...</div>
            <div id="rpCurrentEmpty"  class="text-center py-4 text-muted" style="display:none;">
              <i class="fas fa-receipt fa-2x mb-2"></i><br>No hay ventas en esta sesión.
            </div>
            <div id="rpCurrentTable" style="display:none;">
              <div class="mb-2">
                <input type="text" id="rpCurrentSearch" class="form-control form-control-sm" placeholder="🔍 Buscar por # recibo o cliente...">
              </div>
              <div style="max-height:400px;overflow-y:auto;">
                <table class="table table-sm table-hover table-bordered mb-0" id="tblCurrentSales">
                  <thead class="thead-dark">
                    <tr>
                      <th style="width:90px;"># Recibo</th>
                      <th>Fecha/Hora</th>
                      <th>Cliente</th>
                      <th>Cajero</th>
                      <th class="text-right">Total</th>
                      <th>Método</th>
                      <th class="text-center" style="width:80px;">Reimprimir</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyCurrentSales"></tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- TAB: Ventas históricas -->
          <div class="tab-pane fade" id="tab-history" role="tabpanel">
            <!-- Filtros -->
            <div class="row mb-3">
              <div class="col-md-3">
                <label class="small mb-1">Fecha desde</label>
                <input type="date" id="rpHistDateFrom" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
              </div>
              <div class="col-md-3">
                <label class="small mb-1">Fecha hasta</label>
                <input type="date" id="rpHistDateTo" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
              </div>
              <div class="col-md-3">
                <label class="small mb-1">Usuario / Cajero</label>
                <input type="text" id="rpHistUser" class="form-control form-control-sm" placeholder="Todos los usuarios">
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary btn-sm btn-block" id="btnRpHistSearch">
                  <i class="fas fa-search"></i> Buscar
                </button>
              </div>
            </div>
            <div id="rpHistLoading" class="text-center py-4" style="display:none;"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Buscando ventas...</div>
            <div id="rpHistEmpty"   class="text-center py-4 text-muted" style="display:none;">
              <i class="fas fa-search fa-2x mb-2"></i><br>No se encontraron ventas con esos filtros.
            </div>
            <div id="rpHistTable" style="display:none;">
              <div style="max-height:400px;overflow-y:auto;">
                <table class="table table-sm table-hover table-bordered mb-0" id="tblHistSales">
                  <thead class="thead-dark">
                    <tr>
                      <th style="width:90px;"># Recibo</th>
                      <th>Fecha/Hora</th>
                      <th>Cliente</th>
                      <th>Cajero</th>
                      <th class="text-right">Total</th>
                      <th>Método</th>
                      <th class="text-center" style="width:80px;">Reimprimir</th>
                    </tr>
                  </thead>
                  <tbody id="tbodyHistSales"></tbody>
                </table>
              </div>
            </div>
          </div>

        </div><!-- /tab-content -->
      </main>
      <footer class="modal__footer" style="justify-content:flex-end;padding:12px 20px;">
        <button type="button" class="modal__btn" data-micromodal-close aria-label="Cerrar">Cerrar</button>
      </footer>
    </div>
  </div>
</div>

<script>
// ─── Reprint flow ──────────────────────────────────────────────────────────
document.getElementById('btnReprint').addEventListener('click', function() {
    // Clear auth fields
    document.getElementById('rpAuthUser').value = '';
    document.getElementById('rpAuthPass').value = '';
    document.getElementById('rpAuthError').style.display = 'none';
    MicroModal.show('modalReprintAuth');
    setTimeout(() => document.getElementById('rpAuthUser').focus(), 150);
});

document.getElementById('rpAuthPass').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('btnReprintAuthConfirm').click();
});

document.getElementById('btnReprintAuthConfirm').addEventListener('click', async function() {
    const username = document.getElementById('rpAuthUser').value.trim();
    const password = document.getElementById('rpAuthPass').value.trim();
    const errDiv   = document.getElementById('rpAuthError');
    errDiv.style.display = 'none';

    if (!username || !password) {
        errDiv.textContent = 'Usuario y contraseña son requeridos.';
        errDiv.style.display = 'block';
        return;
    }

    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';

    const fd = new FormData();
    fd.append('username', username);
    fd.append('password', password);

    try {
        const res  = await fetch('<?= APP_BASE ?>/cash/reprintAuth', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            MicroModal.close('modalReprintAuth');
            openReprintPanel();
        } else {
            errDiv.textContent = data.message || 'Credenciales incorrectas.';
            errDiv.style.display = 'block';
        }
    } catch(e) {
        errDiv.textContent = 'Error de conexión.';
        errDiv.style.display = 'block';
    }

    this.disabled = false;
    this.innerHTML = '<i class="fas fa-sign-in-alt"></i> Ingresar';
});

function openReprintPanel() {
    MicroModal.show('modalReprintPanel');
    // Auto-load current session sales
    loadCurrentSales();
}

// ─── Tab: Current Session Sales ────────────────────────────────────────────
const currentSessionId = <?= (int)($session['id'] ?? 0) ?>;

async function loadCurrentSales() {
    document.getElementById('rpCurrentLoading').style.display = 'block';
    document.getElementById('rpCurrentEmpty').style.display   = 'none';
    document.getElementById('rpCurrentTable').style.display   = 'none';

    try {
        const res  = await fetch(`/soleipharmav2/cash/reprintSales?session_id=${currentSessionId}`);
        const data = await res.json();

        document.getElementById('rpCurrentLoading').style.display = 'none';

        if (!data.length) {
            document.getElementById('rpCurrentEmpty').style.display = 'block';
            return;
        }

        renderSalesTable('tbodyCurrentSales', data);
        document.getElementById('rpCurrentTable').style.display = 'block';
        setupCurrentSearch(data);
    } catch(e) {
        document.getElementById('rpCurrentLoading').style.display = 'none';
        document.getElementById('rpCurrentEmpty').style.display = 'block';
    }
}

function setupCurrentSearch(allRows) {
    document.getElementById('rpCurrentSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        const rows = document.querySelectorAll('#tbodyCurrentSales tr');
        rows.forEach(tr => {
            tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// ─── Tab: Historical Sales ──────────────────────────────────────────────────
document.getElementById('btnRpHistSearch').addEventListener('click', loadHistSales);

async function loadHistSales() {
    const dateFrom = document.getElementById('rpHistDateFrom').value;
    const dateTo   = document.getElementById('rpHistDateTo').value;
    const user     = document.getElementById('rpHistUser').value.trim();

    document.getElementById('rpHistLoading').style.display = 'block';
    document.getElementById('rpHistEmpty').style.display   = 'none';
    document.getElementById('rpHistTable').style.display   = 'none';

    try {
        let url = `/soleipharmav2/cash/reprintSales?date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}`;
        if (user) url += `&search_user=${encodeURIComponent(user)}`;

        const res  = await fetch(url);
        const data = await res.json();

        document.getElementById('rpHistLoading').style.display = 'none';

        if (!data.length) {
            document.getElementById('rpHistEmpty').style.display = 'block';
            return;
        }

        renderSalesTable('tbodyHistSales', data);
        document.getElementById('rpHistTable').style.display = 'block';
    } catch(e) {
        document.getElementById('rpHistLoading').style.display = 'none';
        document.getElementById('rpHistEmpty').style.display = 'block';
    }
}

// ─── Shared render ──────────────────────────────────────────────────────────
const PAY_LABELS = { efectivo: '💵 Efectivo', tarjeta: '💳 Tarjeta', transferencia: '🏦 Transfer.' };

function renderSalesTable(tbodyId, sales) {
    const tbody = document.getElementById(tbodyId);
    tbody.innerHTML = '';
    sales.forEach(s => {
        const tr = document.createElement('tr');
        const numLabel = '#' + String(s.id).padStart(6, '0');
        const method   = PAY_LABELS[s.pay_method] || s.pay_method;
        const total    = 'C$ ' + parseFloat(s.total).toFixed(2);
        const dt = s.created_at ? formatDt12h(s.created_at) : '—';
        tr.innerHTML = `
            <td><span class="badge badge-secondary">${numLabel}</span></td>
            <td><small>${escHtml(dt)}</small></td>
            <td><small>${escHtml(s.client_name || 'Consumidor Final')}</small></td>
            <td><small>${escHtml(s.cashier_name || '—')}</small></td>
            <td class="text-right font-weight-bold text-success"><small>${total}</small></td>
            <td><small>${method}</small></td>
            <td class="text-center">
                <a href="<?= APP_BASE ?>/cash/posReceipt/${s.id}" target="_blank"
                   class="btn btn-xs btn-outline-dark" title="Reimprimir recibo">
                    <i class="fas fa-print"></i>
                </a>
            </td>`;
        tbody.appendChild(tr);
    });
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatDt12h(dateStr) {
    if (!dateStr) return '—';
    // dateStr format: "YYYY-MM-DD HH:MM:SS" or "YYYY-MM-DDTHH:MM:SS"
    var clean = dateStr.replace('T', ' ');
    var datePart = clean.substring(0, 10);  // YYYY-MM-DD
    var parts    = datePart.split('-');
    var dateLabel = parts[2] + '/' + parts[1] + '/' + parts[0]; // dd/mm/yyyy
    var timePart  = clean.substring(11, 16);
    var hm        = timePart.split(':');
    var h         = parseInt(hm[0], 10);
    var m         = hm[1];
    var ampm      = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return dateLabel + ' ' + h + ':' + m + '\u00a0' + ampm;
}

// Load hist tab on first click
document.getElementById('tab-history-tab').addEventListener('shown.bs.tab', function() {
    // only auto-search once
    if (!this.dataset.loaded) {
        this.dataset.loaded = '1';
        loadHistSales();
    }
});

// Re-init Micromodal after DOM is ready to pick up new modals
document.addEventListener('DOMContentLoaded', function() {
    if (typeof MicroModal !== 'undefined') MicroModal.init({ disableScroll: true });
});
</script>

<?php if ($isAdmin): ?>
<!-- ╔══════════════════════════════════════════════════════════════╗ -->
<!-- ║  MODAL: Autenticación admin para anulaciones                ║ -->
<!-- ╚══════════════════════════════════════════════════════════════╝ -->
<div class="modal micromodal-slide" id="modalVoidAuth" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container" role="dialog" aria-modal="true"
         aria-labelledby="modalVoidAuthLabel" style="max-width:420px;">
      <header class="modal__header"
              style="background:#c0392b;color:#fff;border-radius:4px 4px 0 0;padding:16px 20px;margin:-30px -30px 20px;">
        <h5 class="modal__title" id="modalVoidAuthLabel" style="color:#fff;font-size:1rem;">
          <i class="fas fa-ban"></i> Acceso a Anulación de Ventas
        </h5>
        <button class="modal__close" aria-label="Cerrar" data-micromodal-close style="color:#fff;"></button>
      </header>
      <main class="modal__content">
        <p class="text-muted small mb-3">
          Ingrese credenciales de <strong>admin</strong> o <strong>superadmin</strong> para acceder al módulo de anulación.
        </p>
        <div class="form-group mb-2">
          <label class="small mb-1"><i class="fas fa-user"></i> Usuario</label>
          <input type="text" id="voidAuthUser" class="form-control form-control-sm"
                 placeholder="Nombre de usuario" autocomplete="off">
        </div>
        <div class="form-group mb-1">
          <label class="small mb-1"><i class="fas fa-lock"></i> Contraseña</label>
          <input type="password" id="voidAuthPass" class="form-control form-control-sm"
                 placeholder="Contraseña">
        </div>
        <div id="voidAuthError" class="text-danger small mt-2" style="display:none;"></div>
      </main>
      <footer class="modal__footer" style="justify-content:flex-end;gap:8px;">
        <button type="button" class="modal__btn" data-micromodal-close>Cancelar</button>
        <button type="button" class="modal__btn modal__btn-primary" id="btnVoidAuthConfirm"
                style="background:#c0392b;border-color:#c0392b;">
          <i class="fas fa-sign-in-alt"></i> Ingresar
        </button>
      </footer>
    </div>
  </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════════╗ -->
<!-- ║  MODAL: Panel de Anulación de Ventas (2 pasos)             ║ -->
<!-- ╚══════════════════════════════════════════════════════════════╝ -->
<div class="modal micromodal-slide" id="modalVoidPanel" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container" role="dialog" aria-modal="true"
         aria-labelledby="modalVoidPanelLabel"
         style="max-width:820px;width:95%;padding:0;border-radius:10px;overflow:hidden;
                box-shadow:0 20px 60px rgba(0,0,0,0.25);">

      <!-- Header -->
      <header class="modal__header"
              style="background:#c0392b;color:#fff;padding:14px 20px;display:flex;
                     align-items:center;justify-content:space-between;">
        <h5 class="modal__title" id="modalVoidPanelLabel" style="color:#fff;font-size:1rem;margin:0;">
          <i class="fas fa-ban"></i> Anulación de Ventas — <span id="voidPanelDate"></span>
        </h5>
        <button class="modal__close" aria-label="Cerrar" data-micromodal-close style="color:#fff;"></button>
      </header>

      <main class="modal__content" style="padding:0;margin:0;">

        <!-- STEP 1: Lista de ventas del día -->
        <div id="voidStep1">
          <div style="padding:16px 20px 12px;border-bottom:1px solid #f0f0f0;background:#fafafa;">
            <div class="row align-items-center g-2">
              <div class="col-auto">
                <label class="small mb-0 font-weight-bold">
                  <i class="fas fa-calendar-day"></i> Fecha de búsqueda
                </label>
              </div>
              <div class="col-auto">
                <input type="date" id="voidDateInput" class="form-control form-control-sm"
                       style="min-width:160px;">
              </div>
              <div class="col-auto">
                <button class="btn btn-sm btn-danger" id="btnVoidSearch">
                  <i class="fas fa-search"></i> Buscar Ventas
                </button>
              </div>
              <div class="col">
                <span class="badge badge-warning text-dark">
                  <i class="fas fa-exclamation-triangle"></i>
                  Solo se pueden anular ventas del día de HOY
                </span>
              </div>
            </div>
          </div>

          <!-- Estado: cargando -->
          <div id="voidLoading" style="display:none;padding:40px;text-align:center;">
            <i class="fas fa-spinner fa-spin fa-2x text-danger"></i>
            <div class="mt-2 text-muted">Buscando ventas...</div>
          </div>

          <!-- Estado: vacío -->
          <div id="voidEmpty" style="display:none;padding:40px;text-align:center;">
            <i class="fas fa-receipt fa-2x text-muted mb-2"></i>
            <div class="text-muted">No se encontraron ventas completadas para esta fecha.</div>
          </div>

          <!-- Tabla de ventas -->
          <div id="voidSalesTable" style="display:none;max-height:400px;overflow-y:auto;">
            <table class="table table-sm table-hover mb-0" id="tblVoidSales">
              <thead class="thead-dark sticky-top" style="top:0;">
                <tr>
                  <th style="width:100px;"># Recibo</th>
                  <th>Hora</th>
                  <th>Cliente</th>
                  <th>Cajero</th>
                  <th>Método</th>
                  <th class="text-right">Total</th>
                  <th class="text-center" style="width:110px;">Artículos</th>
                </tr>
              </thead>
              <tbody id="tbodyVoidSales"></tbody>
            </table>
          </div>
        </div>

        <!-- STEP 2: Detalle de artículos de la factura seleccionada -->
        <div id="voidStep2" style="display:none;">
          <div style="padding:14px 20px;border-bottom:1px solid #f0f0f0;background:#fef9f9;
                      display:flex;align-items:center;justify-content:space-between;">
            <div>
              <button class="btn btn-sm btn-outline-secondary" id="btnVoidBack">
                <i class="fas fa-arrow-left"></i> Volver
              </button>
              <span class="ml-3 font-weight-bold text-danger" id="voidOrderLabel"></span>
            </div>
            <div>
              <small class="text-muted" id="voidOrderMeta"></small>
            </div>
          </div>

          <!-- Spinner artículos -->
          <div id="voidItemsLoading" style="display:none;padding:30px;text-align:center;">
            <i class="fas fa-spinner fa-spin fa-2x text-danger"></i>
            <div class="mt-2 text-muted">Cargando artículos...</div>
          </div>

          <!-- Tabla de artículos -->
          <div id="voidItemsTable" style="display:none;padding:16px 20px;">
            <p class="text-danger mb-2">
              <i class="fas fa-exclamation-triangle"></i>
              Al anular esta venta se restaurará el stock de <strong>todos los artículos</strong> listados.
            </p>
            <table class="table table-sm table-bordered mb-3" id="tblVoidItems">
              <thead class="thead-light">
                <tr>
                  <th>Producto</th>
                  <th>SKU</th>
                  <th class="text-center">Cantidad</th>
                  <th class="text-right">P. Unit.</th>
                  <th class="text-right">Subtotal</th>
                </tr>
              </thead>
              <tbody id="tbodyVoidItems"></tbody>
              <tfoot>
                <tr class="table-danger">
                  <td colspan="4" class="text-right font-weight-bold">TOTAL A ANULAR:</td>
                  <td class="text-right font-weight-bold" id="voidOrderTotal">C$ 0.00</td>
                </tr>
              </tfoot>
            </table>
            <div class="text-right">
              <button class="btn btn-danger" id="btnConfirmVoid">
                <i class="fas fa-ban"></i> Anular Esta Venta
              </button>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>
</div>

<script>
// ═══════════════════════════════════════════════════════════════
// MÓDULO DE ANULACIÓN DE VENTAS
// ═══════════════════════════════════════════════════════════════
(function () {
    'use strict';

    var APP = '<?= APP_BASE ?>';
    var TODAY = '<?= date('Y-m-d') ?>';
    var PAY_LABELS = { efectivo: '💵 Efectivo', tarjeta: '💳 Tarjeta', transferencia: '🏦 Transfer.' };
    var currentOrderId = null;

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ── Convierte hora ISO a formato 12h AM/PM ──────────────────────────────
    function to12h(dateStr) {
        if (!dateStr) return '—';
        var timePart = dateStr.substring(11, 16);
        var parts    = timePart.split(':');
        var h        = parseInt(parts[0], 10);
        var m        = parts[1];
        var ampm     = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        return h + ':' + m + ' ' + ampm;
    }

    // ─── 1. Botón principal → abre auth ──────────────────────────────────────
    var btnVoid = document.getElementById('btnVoidSales');
    if (!btnVoid) return;

    btnVoid.addEventListener('click', function () {
        document.getElementById('voidAuthUser').value = '';
        document.getElementById('voidAuthPass').value = '';
        document.getElementById('voidAuthError').style.display = 'none';
        MicroModal.show('modalVoidAuth', { disableScroll: true, awaitOpenAnimation: true, awaitCloseAnimation: true });
        setTimeout(function () { document.getElementById('voidAuthUser').focus(); }, 150);
    });

    document.getElementById('voidAuthPass').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') document.getElementById('btnVoidAuthConfirm').click();
    });

    // ─── 2. Verificar credenciales ────────────────────────────────────────────
    document.getElementById('btnVoidAuthConfirm').addEventListener('click', async function () {
        var username = document.getElementById('voidAuthUser').value.trim();
        var password = document.getElementById('voidAuthPass').value.trim();
        var errDiv   = document.getElementById('voidAuthError');
        errDiv.style.display = 'none';

        if (!username || !password) {
            errDiv.textContent = 'Usuario y contraseña son requeridos.';
            errDiv.style.display = 'block';
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';

        var fd = new FormData();
        fd.append('username', username);
        fd.append('password', password);

        try {
            var res  = await fetch(APP + '/cash/reprintAuth', { method: 'POST', body: fd });
            var data = await res.json();

            if (data.success) {
                MicroModal.close('modalVoidAuth');
                openVoidPanel();
            } else {
                errDiv.textContent = data.message || 'Credenciales incorrectas.';
                errDiv.style.display = 'block';
            }
        } catch (e) {
            errDiv.textContent = 'Error de conexión.';
            errDiv.style.display = 'block';
        }

        this.disabled = false;
        this.innerHTML = '<i class="fas fa-sign-in-alt"></i> Ingresar';
    });

    // ─── 3. Abrir panel de anulación ──────────────────────────────────────────
    function openVoidPanel() {
        // Inicializar fecha = hoy, restringida a hoy
        var inp = document.getElementById('voidDateInput');
        inp.value = TODAY;
        inp.max   = TODAY;
        inp.min   = TODAY;

        document.getElementById('voidPanelDate').textContent = new Date(TODAY + 'T00:00:00').toLocaleDateString('es-NI', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

        // Mostrar step1, ocultar step2
        showStep(1);

        // Limpiar tabla previa
        setVoidTableState('idle');

        MicroModal.show('modalVoidPanel', {
            disableScroll: true,
            awaitOpenAnimation: true,
            awaitCloseAnimation: true,
            onShow: function () { loadVoidSales(); }
        });
    }

    // ─── 4. Buscar ventas ─────────────────────────────────────────────────────
    document.getElementById('btnVoidSearch').addEventListener('click', loadVoidSales);

    async function loadVoidSales() {
        var date = document.getElementById('voidDateInput').value;
        if (!date) { return; }

        setVoidTableState('loading');

        try {
            var url = APP + '/cash/reprintSales?date_from=' + encodeURIComponent(date) + '&date_to=' + encodeURIComponent(date) + '&status=completado';
            var res  = await fetch(url);
            var data = await res.json();

            if (!Array.isArray(data) || !data.length) {
                setVoidTableState('empty');
                return;
            }

            renderVoidSales(data, date);
            setVoidTableState('table');
        } catch (e) {
            setVoidTableState('empty');
        }
    }

    function setVoidTableState(state) {
        document.getElementById('voidLoading').style.display    = state === 'loading' ? 'block' : 'none';
        document.getElementById('voidEmpty').style.display      = state === 'empty'   ? 'block' : 'none';
        document.getElementById('voidSalesTable').style.display = state === 'table'   ? 'block' : 'none';
    }

    function renderVoidSales(sales, date) {
        var tbody  = document.getElementById('tbodyVoidSales');
        var isToday = (date === TODAY);
        tbody.innerHTML = '';

        sales.forEach(function (s) {
            if (s.status === 'anulado') return; // skip already annulled
            var numLabel = '#' + String(s.id).padStart(6, '0');
            var method   = PAY_LABELS[s.pay_method] || s.pay_method;
            var total    = 'C$ ' + parseFloat(s.total).toFixed(2);
            var time     = s.created_at ? s.created_at.substring(11, 16) : '—';

            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><span class="badge badge-secondary">' + numLabel + '</span></td>' +
                '<td><small>' + esc(time) + '</small></td>' +
                '<td><small>' + esc(s.client_name || 'Consumidor Final') + '</small></td>' +
                '<td><small>' + esc(s.cashier_name || '—') + '</small></td>' +
                '<td><small>' + method + '</small></td>' +
                '<td class="text-right font-weight-bold text-success"><small>' + total + '</small></td>' +
                '<td class="text-center">' +
                    (isToday
                        ? '<button class="btn btn-xs btn-outline-danger btn-void-detail" data-id="' + s.id + '" data-label="' + numLabel + '">' +
                          '<i class="fas fa-eye"></i> Ver artículos</button>'
                        : '<span class="badge badge-light">Solo hoy</span>'
                    ) +
                '</td>';
            tbody.appendChild(tr);
        });

        // Bind detail buttons
        tbody.querySelectorAll('.btn-void-detail').forEach(function (btn) {
            btn.addEventListener('click', function () {
                loadOrderItems(parseInt(this.dataset.id), this.dataset.label);
            });
        });
    }

    // ─── 5. Cargar artículos de la factura seleccionada ───────────────────────
    async function loadOrderItems(orderId, label) {
        currentOrderId = orderId;

        showStep(2);
        document.getElementById('voidOrderLabel').textContent = 'Factura ' + label;
        document.getElementById('voidItemsLoading').style.display = 'block';
        document.getElementById('voidItemsTable').style.display   = 'none';

        try {
            var res  = await fetch(APP + '/cash/saleItems?order_id=' + orderId);
            var data = await res.json();

            document.getElementById('voidItemsLoading').style.display = 'none';

            if (!data.success) {
                if (window.Toast) window.Toast.show({ type: 'error', title: 'Error', message: data.message });
                showStep(1);
                return;
            }

            var ord = data.order;
            document.getElementById('voidOrderMeta').textContent =
                'Cliente: ' + (ord.client_name || 'Consumidor Final') +
                ' | Cajero: ' + (ord.cashier_name || '—') +
                ' | ' + (PAY_LABELS[ord.pay_method] || ord.pay_method);

            var tbody = document.getElementById('tbodyVoidItems');
            tbody.innerHTML = '';
            data.items.forEach(function (it) {
                var sub = (parseFloat(it.price) * parseInt(it.quantity));
                var tr  = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + esc(it.name) + '</td>' +
                    '<td><small class="text-muted">' + esc(it.sku || '—') + '</small></td>' +
                    '<td class="text-center">' + it.quantity + '</td>' +
                    '<td class="text-right">C$ ' + parseFloat(it.price).toFixed(2) + '</td>' +
                    '<td class="text-right font-weight-bold">C$ ' + sub.toFixed(2) + '</td>';
                tbody.appendChild(tr);
            });

            document.getElementById('voidOrderTotal').textContent = 'C$ ' + parseFloat(ord.total).toFixed(2);
            document.getElementById('voidItemsTable').style.display = 'block';

        } catch (e) {
            document.getElementById('voidItemsLoading').style.display = 'none';
            if (window.Toast) window.Toast.show({ type: 'error', title: 'Error de conexión', message: 'No se pudo cargar la factura.' });
            showStep(1);
        }
    }

    // ─── 6. Botón volver ──────────────────────────────────────────────────────
    document.getElementById('btnVoidBack').addEventListener('click', function () {
        showStep(1);
    });

    // ─── 7. Confirmar anulación ───────────────────────────────────────────────
    document.getElementById('btnConfirmVoid').addEventListener('click', function () {
        if (!currentOrderId) return;

        var label = document.getElementById('voidOrderLabel').textContent;

        // Cerrar el panel antes de mostrar ActionModal
        MicroModal.close('modalVoidPanel');

        window.ActionModal.show({
            title:        'Confirmar Anulación ' + label,
            description:  'Esta acción es irreversible. Se restaurará el stock de todos los productos de la factura.',
            fields: [
                {
                    id:          'void_password',
                    type:        'password',
                    label:       '<i class="fas fa-lock"></i> Contraseña del Administrador',
                    placeholder: '••••••••',
                    required:    true
                }
            ],
            confirmText:  'Sí, Anular Venta',
            cancelText:   'Cancelar',
            onConfirm: async function (data) {
                var password = (data['void_password'] || '').trim();
                if (!password) {
                    window.ActionModal.showError('La contraseña es requerida.');
                    return;
                }

                window.ActionModal.hide();

                var fd = new FormData();
                fd.append('order_id', currentOrderId);
                fd.append('password', password);

                try {
                    var res  = await fetch(APP + '/cash/voidSale', { method: 'POST', body: fd });
                    var resp = await res.json();

                    if (resp.success) {
                        if (window.Toast) window.Toast.show({ type: 'success', title: '¡Anulada!', message: resp.message, duration: 4000 });
                        setTimeout(function () { location.reload(); }, 1500);
                    } else {
                        window.Swal.fire({ icon: 'error', title: 'No se pudo anular', text: resp.message });
                    }
                } catch (e) {
                    window.Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión al anular la venta.' });
                }
            }
        });
    });

    // ─── Helpers ──────────────────────────────────────────────────────────────
    function showStep(n) {
        document.getElementById('voidStep1').style.display = n === 1 ? 'block' : 'none';
        document.getElementById('voidStep2').style.display = n === 2 ? 'block' : 'none';
        if (n === 1) currentOrderId = null;
    }

})();
</script>
<?php endif; ?>
