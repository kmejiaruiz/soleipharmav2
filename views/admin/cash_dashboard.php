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
                <?php if (!$isPendingClose): ?>
                <button class="btn btn-danger btn-sm" id="btnRequestClose">
                    <i class="fas fa-times-circle"></i> Solicitar Cierre de Caja
                </button>
                <a href="/soleipharmav2/cash/withdrawal" class="btn btn-warning btn-sm ml-2">
                    <i class="fas fa-hand-holding-usd"></i> Registrar Retiro
                </a>
                <a href="/soleipharmav2/cash/pos" class="btn btn-primary btn-sm ml-2">
                    <i class="fas fa-cash-register"></i> Facturar / POS
                </a>
                <?php else: ?>
                <span class="badge badge-warning p-2" style="font-size:13px;">
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
        <a href="/soleipharmav2/cash/history">Historial de Caja</a>.
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
                        <a href="/soleipharmav2/cash/withdrawal" class="btn btn-sm btn-warning">
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
                                    <a href="/soleipharmav2/cash/withdrawalPdf/<?= $w['id'] ?>" target="_blank"
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

            const res  = await fetch('/soleipharmav2/cash/requestClose', { method: 'POST', body: fd });
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
