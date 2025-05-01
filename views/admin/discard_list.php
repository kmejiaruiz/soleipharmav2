<h1 class="mb-4 text-center">Solicitudes Pendientes</h1>

<div class="container mb-5">
    <div class="card shadow-sm p-3">
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Razón</th>
                        <th>Solicitante</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr data-id="<?= $r['id'] ?>">
                            <td><?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['product_name']) ?></td>
                            <td><?= $r['quantity'] ?></td>
                            <td><?= htmlspecialchars($r['reason']) ?></td>
                            <td><?= htmlspecialchars($r['requester_name']) ?></td>
                            <td>
                                <button class="btn btn-success btn-sm approve-btn me-1">Aprobar</button>
                                <button class="btn btn-danger btn-sm reject-btn">Rechazar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap y jQuery -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    document.querySelectorAll('.approve-btn, .reject-btn').forEach(btn => {
        btn.onclick = () => {
            const tr = btn.closest('tr');
            const id = tr.dataset.id;
            const productName = tr.querySelectorAll('td')[1].textContent.trim(); // Nombre del producto
            const quantity = tr.querySelectorAll('td')[2].textContent.trim(); // Cantidad
            const status = btn.classList.contains('approve-btn') ? 'approved' : 'rejected';

            Swal.fire({
                title: `${status === 'approved' ? 'Aprobar' : 'Rechazar'} descarte de ${quantity} unidades de "${productName}"?`,
                input: 'text',
                inputLabel: 'Razón de la decisión',
                inputPlaceholder: 'Escribe la razón aquí...',
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar'
            }).then(res => {
                if (res.isConfirmed) {
                    fetch('index.php?controller=discard&action=decide', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${id}&status=${status}&decision_reason=${encodeURIComponent(res.value)}`
                    }).then(r => r.json()).then(data => {
                        Swal.fire({
                            icon: data.success ? 'success' : 'error',
                            title: data.message
                        });
                        if (data.success) tr.remove();
                    });
                }
            });
        };
    });
</script>