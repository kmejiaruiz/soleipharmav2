<?php // views/admin/cash_history.php ?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-7">
                <h1><i class="fas fa-history"></i> Historial de Caja</h1>
            </div>
            <div class="col-sm-5 text-right">
                <a href="<?= APP_BASE ?>/cash/index" class="btn btn-sm btn-success">
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

    <!-- ══ PANEL DE FILTROS (hero) ═══════════════════════════════════════════ -->
    <div id="histFilterPanel">
        <div class="hist-filter-hero">
            <div class="text-center mb-4">
                <div class="hist-filter-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h4 class="hist-filter-title">Consulta de Historial de Caja</h4>
                <p class="text-muted mb-0">Selecciona los filtros y presiona <strong>Ver Historial</strong></p>
            </div>

            <form id="histFilterForm" method="GET" action="<?= APP_BASE ?>/cash/history">
                <div class="hist-filter-grid" style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; max-width:580px; margin:0 auto;">
                    <div class="hist-filter-field" style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:0.4rem;">
                            <i class="fas fa-calendar-alt"></i> Fecha desde <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="date_from" id="histDateFrom" class="form-control"
                               value="<?= htmlspecialchars($filterDateFrom ?? '') ?>" required>
                    </div>
                    <div class="hist-filter-field" style="flex:1; min-width:200px;">
                        <label style="display:block; font-weight:600; margin-bottom:0.4rem;">
                            <i class="fas fa-calendar-check"></i> Fecha hasta <span class="text-danger">*</span>
                        </label>
                        <input type="date" name="date_to" id="histDateTo" class="form-control"
                               value="<?= htmlspecialchars($filterDateTo ?? '') ?>" required>
                    </div>
                </div>
                <div class="hist-filter-actions" style="margin-top:1.5rem;">
                    <button type="submit" id="btnHistSearch" class="hist-btn-search">
                        <i class="fas fa-search"></i> Ver Historial
                    </button>
                    <?php if (!empty($filterDateFrom)): ?>
                    <a href="<?= APP_BASE ?>/cash/history" class="hist-btn-clear">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ══ LOADER ════════════════════════════════════════════════════════════ -->
    <div id="histLoader" style="display:none;">
        <div class="hist-loader-inner">
            <div class="hist-loader-spinner">
                <div class="hist-loader-ring"></div>
                <i class="fas fa-cash-register hist-loader-icon"></i>
            </div>
            <div class="hist-loader-text">Cargando historial...</div>
            <div class="hist-loader-sub">Esto tardará solo un momento</div>
        </div>
    </div>

    <!-- ══ RESULTADOS ════════════════════════════════════════════════════════ -->
    <?php $hasResults = !empty($sessions) && !empty($filterDateFrom); ?>


    <div id="histResultsPanel" style="opacity:0;display:none;">

        <!-- Barra de contexto con filtro activo + botón cambiar -->
        <div class="hist-results-bar mb-3">
            <div class="hist-results-info">
                <i class="fas fa-filter"></i>
                <?php if ($filterDateFrom): ?>
                    <strong><?= date('d/m/Y', strtotime($filterDateFrom)) ?></strong>
                    <?php if ($filterDateTo && $filterDateTo !== $filterDateFrom): ?>
                        &nbsp;&mdash;&nbsp;<strong><?= date('d/m/Y', strtotime($filterDateTo)) ?></strong>
                    <?php endif; ?>
                <?php else: ?>
                    Todos los registros
                <?php endif; ?>
                &mdash;
                <strong><?= count($sessions) ?></strong> sesión<?= count($sessions) !== 1 ? 'es' : '' ?> encontrada<?= count($sessions) !== 1 ? 's' : '' ?>
            </div>
            <button id="btnChangeFilter" class="hist-btn-change">
                <i class="fas fa-sliders-h"></i> Cambiar filtro
            </button>
        </div>

    <!-- ══ Lista de sesiones ════════════════════════════════════════════════ -->
    <?php if ($hasResults): ?>

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
                <a href="<?= APP_BASE ?>/cash/closingPdf/<?= $s['id'] ?>" target="_blank"
                   class="btn btn-xs btn-outline-dark mr-1" title="Reporte de cierre">
                    <i class="fas fa-file-pdf"></i> Cierre PDF
                </a>
                <?php elseif ($isPending): ?>
                <button class="btn btn-xs btn-danger mr-1"
                        onclick="openCloseModal(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes(ucwords(strtolower($s['opener_name'])))) ?>', <?= $expected ?>)">
                    <i class="fas fa-times-circle"></i> Confirmar Cierre
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
                            <a href="<?= APP_BASE ?>/cash/withdrawalPdf/<?= $w['id'] ?>" target="_blank"
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

        <?php
        // Ventas de la sesión (solo para admin/superadmin)
        if (in_array($_SESSION['user']['role'] ?? '', ['admin', 'superadmin'])):
            // Sesión activa: mostrar TODAS las ventas (completadas + anuladas) con botón anular
            // Sesión cerrada: mostrar SOLO ventas anuladas (para trazabilidad)
            $statusFilter = $isActive
                ? "AND o.status IN ('completado','anulado')"
                : "AND o.status = 'anulado'";

            $stmtOrds = $pdo->prepare(
                "SELECT o.id, o.total, o.discount, o.pay_method, o.client_name, o.status, o.created_at,
                        CONCAT(u.first_name,' ',u.last_name) AS cashier
                 FROM orders o JOIN users u ON u.id = o.user_id
                 WHERE o.user_id = ? AND o.created_at >= ?
                   AND o.created_at <= IFNULL(?, NOW())
                   $statusFilter
                 ORDER BY o.created_at DESC"
            );
            $stmtOrds->execute([$s['opened_by'], $s['opened_at'], $s['closed_at'] ?? null]);
            $sessionOrders = $stmtOrds->fetchAll(PDO::FETCH_ASSOC);
        endif;
        ?>
        <?php if (!empty($sessionOrders) && in_array($_SESSION['user']['role'] ?? '', ['admin','superadmin'])): ?>
        <div class="card-footer p-0 border-top-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th colspan="7" class="py-1 px-3 text-muted small">
                            <?php if ($isActive): ?>
                                <i class="fas fa-receipt"></i> Ventas de esta sesión
                            <?php else: ?>
                                <i class="fas fa-ban text-danger"></i> Ventas anuladas en esta sesión
                            <?php endif; ?>
                        </th>
                    </tr>
                    <tr>
                        <th>Recibo</th>
                        <th>Hora</th>
                        <th>Cliente</th>
                        <th>Método</th>
                        <th class="text-right">Total</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($sessionOrders as $ord):
                    $ordDt = new DateTime($ord['created_at']);
                    $ordDt->setTimezone(new DateTimeZone('America/Managua'));
                    $isAnulado = $ord['status'] === 'anulado';
                    $payIcon = match($ord['pay_method'] ?? 'efectivo') {
                        'tarjeta' => '💳', 'transferencia' => '🏦', default => '💵'
                    };
                ?>
                <tr class="<?= $isAnulado ? 'table-danger' : '' ?>">
                    <td><strong>#<?= str_pad($ord['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                    <td><small><?= $ordDt->format('H:i') ?></small></td>
                    <td><small><?= htmlspecialchars($ord['client_name'] ?? 'Consumidor Final') ?></small></td>
                    <td><small><?= $payIcon ?> <?= ucfirst($ord['pay_method'] ?? 'efectivo') ?></small></td>
                    <td class="text-right <?= $isAnulado ? 'text-danger' : 'text-success font-weight-bold' ?>">
                        <?= $isAnulado ? '<s>' : '' ?>C$ <?= number_format($ord['total'], 2) ?><?= $isAnulado ? '</s>' : '' ?>
                    </td>
                    <td>
                        <?php if ($isAnulado): ?>
                        <span class="badge badge-danger">Anulada</span>
                        <?php else: ?>
                        <span class="badge badge-success">Completada</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="<?= APP_BASE ?>/cash/posReceipt/<?= $ord['id'] ?>" target="_blank"
                           class="btn btn-xs btn-outline-secondary" title="Ver recibo">
                            <i class="fas fa-print"></i>
                        </a>
                        <?php if ($isActive && !$isAnulado): ?>
                        <button class="btn btn-xs btn-outline-danger ml-1"
                                onclick="voidSale(<?= $ord['id'] ?>, '#<?= str_pad($ord['id'],6,'0',STR_PAD_LEFT) ?>')"
                                title="Anular venta">
                            <i class="fas fa-ban"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php else: ?>
        <div class="card"><div class="card-body text-center text-muted py-5">
            <i class="fas fa-inbox fa-3x mb-3"></i><br>
            No se encontraron sesiones con los filtros seleccionados para el rango indicado.
        </div></div>
    <?php endif; ?>

    <?php if ($totalPages > 1 && $hasResults): ?>
    <?php
        $baseUrl = APP_BASE . '/cash/history?date_from=' . urlencode($filterDateFrom)
                 . '&date_to=' . urlencode($filterDateTo);
        $window = 2; // páginas a cada lado del actual
    ?>
    <nav aria-label="Paginación de sesiones" class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
            Mostrando página <strong><?= $page ?></strong> de <strong><?= $totalPages ?></strong>
            &mdash; <?= $totalSessions ?> sesión<?= $totalSessions !== 1 ? 'es' : '' ?> en total
        </small>
        <ul class="pagination pagination-sm mb-0">
            <!-- Anterior -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $page - 1 ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>

            <?php if ($page - $window > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $baseUrl ?>&page=1">1</a>
                </li>
                <?php if ($page - $window > 2): ?>
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($p = max(1, $page - $window); $p <= min($totalPages, $page + $window); $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $baseUrl ?>&page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page + $window < $totalPages): ?>
                <?php if ($page + $window < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $baseUrl ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a>
                </li>
            <?php endif; ?>

            <!-- Siguiente -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $baseUrl ?>&page=<?= $page + 1 ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    </div><!-- /#histResultsPanel -->

</div>
</section>

<!-- ── Modal: Cierre de caja por admin (denominaciones — no usa ActionModal por UI compleja) -->
<div class="modal micromodal-slide" id="adminCloseCashModal" aria-hidden="true">
    <div class="modal__overlay" tabindex="-1" data-micromodal-close>
        <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="acmTitle" style="max-width:700px;width:95%;">
            <header class="modal__header" style="background:#dc3545;color:#fff;border-radius:4px 4px 0 0;padding:16px 20px;margin:-30px -30px 20px;">
                <h5 class="modal__title" id="acmTitle" style="color:#fff;font-size:1rem;">
                    <i class="fas fa-times-circle"></i>
                    Cerrar Caja de <span id="acmCajero"></span>
                </h5>
                <button class="modal__close" aria-label="Cerrar" data-micromodal-close style="color:#fff;"></button>
            </header>
            <form id="adminCloseCashForm">
                <input type="hidden" id="acmSessionId" name="session_id">
                <main class="modal__content">
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
                </main>
                <footer class="modal__footer" style="justify-content:flex-end;gap:8px;">
                    <button type="button" class="modal__btn" data-micromodal-close aria-label="Cancelar">Cancelar</button>
                    <button type="submit" class="modal__btn" id="acmBtnConfirm" style="background:#dc3545;color:#fff;">
                        <i class="fas fa-check"></i> Confirmar Cierre
                    </button>
                </footer>
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
    MicroModal.show('adminCloseCashModal');
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
    const res = await fetch('<?= APP_BASE ?>/cash/closeCash', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
        MicroModal.close('adminCloseCashModal');
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

            const res  = await fetch('<?= APP_BASE ?>/cash/voidSale', { method: 'POST', body: fd });
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

<script>
// Re-init Micromodal para nuevos modales en esta página
document.addEventListener('DOMContentLoaded', function() {
    if (typeof MicroModal !== 'undefined') MicroModal.init({ disableScroll: true });
});
</script>

<script>
// ═════════════════════════════════════════════════════════════════
// HISTORIAL DE CAJA — Animación Hero Filter
// ═════════════════════════════════════════════════════════════════
(function () {
    'use strict';

    var filterPanel  = document.getElementById('histFilterPanel');
    var loader       = document.getElementById('histLoader');
    var resultsPanel = document.getElementById('histResultsPanel');
    var filterForm   = document.getElementById('histFilterForm');
    var btnChange    = document.getElementById('btnChangeFilter');

    var hasResults   = resultsPanel !== null;
    var fromSubmit   = sessionStorage.getItem('hist_from_submit') === '1';
    var ANIM_MS      = 350;

    // ── Utilidades de transición ──────────────────────────────────────────────
    function fadeOut(el, ms, cb) {
        el.style.transition = 'opacity ' + ms + 'ms ease, transform ' + ms + 'ms ease';
        el.style.opacity    = '0';
        el.style.transform  = 'translateY(-24px)';
        setTimeout(function () {
            el.style.display = 'none';
            el.style.transition = '';
            el.style.transform  = '';
            if (cb) cb();
        }, ms);
    }

    function fadeIn(el, ms, delay) {
        delay = delay || 0;
        el.style.opacity   = '0';
        el.style.transform = 'translateY(20px)';
        el.style.display   = 'block';
        setTimeout(function () {
            el.style.transition = 'opacity ' + ms + 'ms ease, transform ' + ms + 'ms ease';
            el.style.opacity    = '1';
            el.style.transform  = 'translateY(0)';
            setTimeout(function () { el.style.transition = ''; }, ms);
        }, delay);
    }

    function showLoader() {
        loader.style.opacity   = '0';
        loader.style.display   = 'flex';
        loader.style.transform = 'scale(0.9)';
        setTimeout(function () {
            loader.style.transition = 'opacity 300ms ease, transform 300ms ease';
            loader.style.opacity    = '1';
            loader.style.transform  = 'scale(1)';
        }, 30);
    }

    function hideLoader(cb) {
        loader.style.transition = 'opacity 300ms ease';
        loader.style.opacity    = '0';
        setTimeout(function () {
            loader.style.display    = 'none';
            loader.style.transition = '';
            if (cb) cb();
        }, 320);
    }

    // ── Estado inicial al cargar la página ──────────────────────────────────────
    if (fromSubmit) {
        // Viene de un submit — animar: loader → resultados
        sessionStorage.removeItem('hist_from_submit');
        filterPanel.style.display = 'none';
        loader.style.display      = 'flex';
        loader.style.opacity      = '1';
        loader.style.transform    = 'scale(1)';

        setTimeout(function () {
            hideLoader(function () {
                fadeIn(resultsPanel, 500, 80);
            });
        }, 900); // Loader visible 900ms

    } else if (hasResults) {
        // Navegación directa con parámetros en URL
        filterPanel.style.display = 'none';
        resultsPanel.style.display  = 'block';
        resultsPanel.style.opacity  = '0';
        resultsPanel.style.transform = 'translateY(20px)';
        setTimeout(function () {
            resultsPanel.style.transition = 'opacity 500ms ease, transform 500ms ease';
            resultsPanel.style.opacity    = '1';
            resultsPanel.style.transform  = 'translateY(0)';
        }, 200);

    } else {
        // Sin resultados: mostrar panel de filtros
        filterPanel.style.display = 'block';
        filterPanel.style.opacity = '0';
        setTimeout(function () {
            filterPanel.style.transition = 'opacity 400ms ease';
            filterPanel.style.opacity    = '1';
            setTimeout(function () { filterPanel.style.transition = ''; }, 420);
        }, 50);
    }

    // ── Interceptar submit del formulario ───────────────────────────────────
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            var dateFrom = document.getElementById('histDateFrom').value;
            var dateTo   = document.getElementById('histDateTo').value;

            if (!dateFrom || !dateTo) {
                e.preventDefault();
                var emptyField = !dateFrom
                    ? document.getElementById('histDateFrom')
                    : document.getElementById('histDateTo');
                emptyField.focus();
                emptyField.classList.add('is-invalid');
                setTimeout(function () { emptyField.classList.remove('is-invalid'); }, 2500);
                return;
            }
            if (dateFrom > dateTo) {
                e.preventDefault();
                document.getElementById('histDateFrom').classList.add('is-invalid');
                document.getElementById('histDateTo').classList.add('is-invalid');
                setTimeout(function () {
                    document.getElementById('histDateFrom').classList.remove('is-invalid');
                    document.getElementById('histDateTo').classList.remove('is-invalid');
                }, 2500);
                return;
            }

            e.preventDefault();

            // Marcar que viene de submit para la próxima carga
            sessionStorage.setItem('hist_from_submit', '1');

            // Animar botón
            var btn = document.getElementById('btnHistSearch');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Buscando...';
            }

            // Fade out del panel de filtros
            fadeOut(filterPanel, ANIM_MS, function () {
                showLoader();
                // Navegar tras mostrar el loader
                setTimeout(function () {
                    filterForm.submit();
                }, 600);
            });
        });
    }

    // ── Botón "Cambiar filtro" ─────────────────────────────────────────────
    if (btnChange) {
        btnChange.addEventListener('click', function () {
            fadeOut(resultsPanel, ANIM_MS, function () {
                fadeIn(filterPanel, 400, 60);
            });
        });
    }

})();
</script>

