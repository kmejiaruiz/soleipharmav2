<div class="row mb-2">
            <div class="col-sm-6">
                <h1>Historial de Descartes</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Historial de Descartes</li>
                </ol>
            </div>
        </div>

<div class="container">
    <div class="table-responsive p-3 shadow rounded bg-white">
        <table class="table table-bordered table-hover align-middle text-center">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Solicitante</th>
                    <th>Estado</th>
                    <th>Decisión por</th>
                    <th>Razón decisión</th>
                    <th>Fecha solicitud</th>
                    <th>Fecha decisión</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['product_name']) ?></td>
                        <td><?= $r['quantity'] ?></td>
                        <td><?= htmlspecialchars($r['requester_name']) ?></td>
                        <td>
                            <span
                                class="badge bg-<?= $r['status'] === 'approved' ? 'success' : ($r['status'] === 'rejected' ? 'danger' : 'secondary') ?>">
                                <?= ucfirst($r['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($r['decision_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['decision_reason'] ?? '-') ?></td>
                        <td><?= $r['created_at'] ?></td>
                        <td><?= $r['decision_at'] ?? '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div> 