<?php // views/admin/cash_withdrawal.php ?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-hand-holding-usd"></i> Registrar Retiro de Efectivo</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/soleipharmav2/cash/dashboard">Caja</a></li>
                    <li class="breadcrumb-item active">Retiro</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
<div class="row justify-content-center">
<div class="col-md-8">

<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-coins"></i> Denominaciones a Retirar</h3>
    </div>
    <div class="card-body">
        <form id="withdrawalForm">
            <div class="row">
                <?php
                $denoms = [1000=>'C$1,000', 500=>'C$500', 200=>'C$200', 100=>'C$100',
                           50=>'C$50', 20=>'C$20', 10=>'C$10', 5=>'C$5', 1=>'C$1'];
                foreach ($denoms as $val => $label): ?>
                <div class="col-md-6 mb-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text" style="min-width:80px;font-weight:700;font-size:15px;"><?= $label ?></span>
                        </div>
                        <input type="number" class="form-control denom-input" name="denominations[<?= $val ?>]"
                               data-value="<?= $val ?>" min="0" value="0" placeholder="Cantidad de billetes/monedas">
                        <div class="input-group-append">
                            <span class="input-group-text subtotal" data-for="<?= $val ?>">= C$0</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group mt-2">
                <label><i class="fas fa-comment"></i> Motivo del Retiro</label>
                <input type="text" name="reason" class="form-control" placeholder="Ej: Pago a proveedor, Depósito bancario...">
            </div>

            <div class="alert alert-info text-center mt-3">
                <h4 class="mb-0">Total a Retirar: <strong id="totalDisplay">C$ 0.00</strong></h4>
            </div>

            <div class="row">
                <div class="col-6">
                    <a href="/soleipharmav2/cash/dashboard" class="btn btn-secondary btn-block">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                </div>
                <div class="col-6">
                    <button type="submit" class="btn btn-warning btn-block" id="btnSubmit">
                        <i class="fas fa-print"></i> Registrar y Generar Comprobante
                    </button>
                </div>
            </div>
        </form>
    </div>
    <div class="card-footer text-muted small">
        Sesión abierta desde:
        <?php
        $dt = new DateTime($session['opened_at']);
        $dt->setTimezone(new DateTimeZone('America/Managua'));
        echo $dt->format('d/m/Y H:i');
        ?>
        &bull; Cajero: <strong><?= htmlspecialchars($_SESSION['user']['username'] ?? '') ?></strong>
    </div>
</div>

</div>
</div>
</div>
</section>

<script>
document.querySelectorAll('.denom-input').forEach(inp => {
    inp.addEventListener('input', function() {
        const val = parseInt(this.dataset.value);
        const qty = Math.max(0, parseInt(this.value) || 0);
        const sub = val * qty;
        document.querySelector(`.subtotal[data-for="${val}"]`).textContent = `= C$${sub.toLocaleString()}`;
        updateTotal();
    });
});

function updateTotal() {
    let total = 0;
    document.querySelectorAll('.denom-input').forEach(inp => {
        total += parseInt(inp.dataset.value) * (Math.max(0, parseInt(inp.value) || 0));
    });
    document.getElementById('totalDisplay').textContent = `C$ ${total.toLocaleString('es-NI', {minimumFractionDigits:2})}`;
}

document.getElementById('withdrawalForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    const fd = new FormData(this);
    const res = await fetch('/soleipharmav2/cash/storeWithdrawal', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
        // Open PDF in new tab then redirect to dashboard
        window.open('/soleipharmav2/cash/withdrawalPdf/' + data.withdrawal_id, '_blank');
        setTimeout(() => window.location.href = '/soleipharmav2/cash/dashboard', 800);
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-print"></i> Registrar y Generar Comprobante';
    }
});
</script>
