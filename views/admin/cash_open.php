<?php // views/admin/cash_open.php ?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-cash-register"></i> Apertura de Caja</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                    <li class="breadcrumb-item active">Apertura de Caja</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
<div class="row justify-content-center">
<div class="col-md-6">

<div class="card card-primary card-outline">
    <div class="card-header text-center">
        <h3 class="card-title"><i class="fas fa-lock-open"></i> Abrir Sesión de Caja</h3>
    </div>
    <div class="card-body">
        <div class="text-center mb-4">
            <div style="font-size:64px;color:#28a745;"><i class="fas fa-cash-register"></i></div>
            <p class="text-muted">Ingrese el monto inicial del fondo de caja para comenzar la sesión.</p>
        </div>

        <form id="openCashForm">
            <div class="form-group">
                <label><i class="fas fa-coins"></i> Monto de Apertura (C$) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text">C$</span></div>
                    <input type="number" id="opening_amount" name="opening_amount" class="form-control form-control-lg"
                           min="0" step="0.01" placeholder="0.00" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-sticky-note"></i> Observaciones (opcional)</label>
                <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Notas de apertura..."></textarea>
            </div>
            <button type="submit" class="btn btn-success btn-lg btn-block mt-3">
                <i class="fas fa-lock-open"></i> Abrir Caja
            </button>
        </form>
    </div>
    <div class="card-footer text-muted text-center small">
        Cajero: <strong><?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?></strong>
        &bull; <?= date('d/m/Y H:i') ?>
        &bull; <?= htmlspecialchars(defined('BRANCH') ? BRANCH : '') ?>
    </div>
</div>

</div>
</div>
</div>
</section>

<script>
document.getElementById('openCashForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Abriendo...';

    const fd = new FormData(this);
    const res = await fetch('<?= APP_BASE ?>/cash/store', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
        Swal.fire({ icon: 'success', title: '¡Caja Abierta!', text: 'La sesión de caja ha iniciado.', timer: 1500, showConfirmButton: false })
            .then(() => window.location.href = data.redirect);
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-lock-open"></i> Abrir Caja';
    }
});
</script>
