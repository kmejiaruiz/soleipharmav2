<div class="row mb-2">
    <div class="col-sm-6">
        <h1>Mis Descartes</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
            <li class="breadcrumb-item active">Mis Descartes</li>
        </ol>
    </div>
</div>

<div class="container">
    <div class="table-responsive p-3 shadow rounded bg-white">
        <?php if (empty($requests)): ?>
            <div class="alert alert-info text-center">No has realizado ninguna solicitud de descarte.</div>
        <?php else: ?>
        <table class="table table-bordered table-hover align-middle text-center">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Producto</th>
                    <th>Cant.</th>
                    <th>Estado</th>
                    <th>Resuelto Por</th>
                    <th>Justificación Resolutor</th>
                    <th>Fecha Solicitud</th>
                    <th>Fecha Resolución</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['product_name']) ?></td>
                        <td><?= $r['quantity'] ?></td>
                        <td>
                            <?php if ($r['status'] === 'pending' && !$r['is_follow_up']): ?>
                                <span class="badge bg-secondary">Pendiente</span>
                            <?php elseif ($r['status'] === 'pending' && $r['is_follow_up']): ?>
                                <span class="badge bg-info text-dark">En Seguimiento</span>
                            <?php elseif ($r['status'] === 'approved'): ?>
                                <span class="badge bg-success">Aprobado</span>
                            <?php elseif ($r['status'] === 'rejected'): ?>
                                <span class="badge bg-danger">Rechazado</span>
                            <?php elseif ($r['status'] === 'in_revision'): ?>
                                <span class="badge bg-warning text-dark">En Revisión</span>
                                <?php if ($r['is_follow_up']): ?>
                                    <br><span class="badge bg-info text-dark mt-1">En Seguimiento</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($r['decision_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($r['decision_reason'] ?? '-') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                        <td><?= $r['decision_at'] ? date('d/m/Y H:i', strtotime($r['decision_at'])) : '-' ?></td>
                        <td>
                            <?php if ($r['status'] === 'in_revision'): ?>
                                <a href="/soleipharmav2/discard/edit?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Corregir
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
