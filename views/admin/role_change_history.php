<!-- views/admin/role_change_history.php -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-history"></i> Bitácora de Privilegios</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/manageRoles">Gestión de Privilegios</a></li>
                    <li class="breadcrumb-item active">Bitácora</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title">Historial de Modificaciones de Acceso</h3>
                        <div class="card-tools">
                            <a href="<?= APP_BASE ?>/admin/manageRoles" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver a Usuarios
                            </a>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table id="historyTable" class="table table-bordered table-striped w-100 table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha y Hora</th>
                                    <th>Afectado</th>
                                    <th>Rol Anterior</th>
                                    <th>Nuevo Rol</th>
                                    <th>Autorizado Por</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td data-order="<?= strtotime($log['created_at']) ?>"><?= date('d/m/Y h:i A', strtotime($log['created_at'])) ?></td>
                                            <td><strong><?= htmlspecialchars(trim($log['target_name'] ?? '')) ?></strong></td>
                                            <td><span class="badge badge-secondary"><?= strtoupper(htmlspecialchars($log['old_role'])) ?></span></td>
                                            <td>
                                                <?php 
                                                    $bg = 'badge-success';
                                                    if ($log['new_role'] === 'superadmin') $bg = 'badge-purple';
                                                    if ($log['new_role'] === 'user') $bg = 'badge-secondary';
                                                ?>
                                                <span class="badge <?= $bg ?>"><i class="fas fa-arrow-right"></i> <?= strtoupper(htmlspecialchars($log['new_role'])) ?></span>
                                            </td>
                                            <td class="text-primary"><i class="fas fa-user-shield"></i> <?= htmlspecialchars(trim($log['admin_name'] ?? '')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No se han registrado transferencias de privilegios aún en el sistema.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<style>
.badge-purple {
    background-color: #6f42c1;
    color: #fff;
}
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#historyTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "pageLength": 10,
                "responsive": true,
                "order": [[ 0, "desc" ]] // Sort by Date descendant
            });
        }
    });
</script>
