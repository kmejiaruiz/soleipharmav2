<?php // views/admin/suppliers_list.php ?>
<section class="content-header">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-truck"></i> Proveedores</h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                <li class="breadcrumb-item active">Proveedores</li>
            </ol>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <?php if ($isSuperAdmin): ?>
        <div class="mb-3">
            <a href="<?= APP_BASE ?>/supplier/create" class="btn btn-success">
                <i class="fas fa-plus"></i> Nuevo Proveedor
            </a>
        </div>
        <?php endif; ?>

        <?php if (!empty($suppliers)): ?>
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-bordered table-hover" id="suppliersTable">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>RUC</th>
                            <th>Contacto</th>
                            <th>Teléfono</th>
                            <th class="text-center">Productos</th>
                            <th class="text-center">Pedidos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $s): ?>
                        <tr id="row-sup-<?= $s['id'] ?>">
                            <td><?= $s['id'] ?></td>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                            <td><?= htmlspecialchars($s['ruc'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($s['contact_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
                            <td class="text-center">
                                <span class="badge badge-info p-1"><?= $s['product_count'] ?> productos</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary p-1"><?= $s['order_count'] ?> pedidos</span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-<?= $s['active'] ? 'success' : 'secondary' ?> p-1 stat-badge-<?= $s['id'] ?>">
                                    <?= $s['active'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= APP_BASE ?>/supplier/catalog?id=<?= $s['id'] ?>" class="btn btn-info btn-sm" title="Catálogo">
                                    <i class="fas fa-list"></i> Catálogo
                                </a>
                                <?php if ($isSuperAdmin): ?>
                                <a href="<?= APP_BASE ?>/supplier/edit?id=<?= $s['id'] ?>" class="btn btn-warning btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-<?= $s['active'] ? 'secondary' : 'success' ?> btn-sm btn-toggle"
                                    data-id="<?= $s['id'] ?>" data-active="<?= $s['active'] ?>"
                                    title="<?= $s['active'] ? 'Desactivar' : 'Activar' ?>">
                                    <i class="fas fa-power-off"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No hay proveedores registrados. <a href="<?= APP_BASE ?>/supplier/create">Crea el primero</a>.
        </div>
        <?php endif; ?>
    </div>
</section>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $('#suppliersTable').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        columnDefs: [{ orderable: false, targets: [6, 7] }]
    });

    $(document).on('click', '.btn-toggle', function() {
        const id = $(this).data('id');
        const btn = $(this);
        $.post('<?= APP_BASE ?>/supplier/toggle', { id: id }, function(r) {
            if (r.success) {
                const label = r.active ? 'Activo' : 'Inactivo';
                const badgeCls = r.active ? 'badge-success' : 'badge-secondary';
                const btnCls = r.active ? 'btn-secondary' : 'btn-success';
                $(`.stat-badge-${id}`).text(label).removeClass('badge-success badge-secondary').addClass(badgeCls);
                btn.removeClass('btn-secondary btn-success').addClass(btnCls);
                btn.data('active', r.active ? 1 : 0);
            }
        }, 'json');
    });
});
</script>
