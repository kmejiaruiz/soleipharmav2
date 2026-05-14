<?php
// Se espera que el controlador pase la variable $orders (arreglo de pedidos).
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Listado de Pedidos</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Listado de Pedidos</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <a href="<?= APP_BASE ?>/order/create" class="btn btn-success mb-3">Realizar Pedido</a>
        <?php if (!empty($orders)): ?>
            <table class="table table-bordered" id="ordersTable">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Admin</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Status</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($order['admin_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($order['order_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($order['supplier_name'] ?? '—') ?></td>
                            <td>
                                <?php
                                    $rawStat = $order['status'] ?? '';
                                    $statusMap = ['pending' => 'Pendiente', 'applied' => 'Aplicado', 'received' => 'Recibido'];
                                    $translated = $statusMap[$rawStat] ?? ucfirst($rawStat);
                                    
                                    $badgeClass = 'secondary';
                                    if ($rawStat === 'pending') $badgeClass = 'warning';
                                    if ($rawStat === 'applied') $badgeClass = 'primary';
                                    if ($rawStat === 'received') $badgeClass = 'success';
                                ?>
                                <span class="badge badge-<?= $badgeClass ?> p-2"><?= htmlspecialchars($translated) ?></span>
                            </td>
                            <td>
                                <a href="<?= APP_BASE ?>/order/edit?id=<?= $order['id'] ?>"
                                    class="btn btn-primary btn-sm"
                                    data-ux-tooltip="Ver o editar pedido">Ver/Editar</a>

                                <?php if ($order['status'] == 'pending'): ?>
                                    <button class="btn btn-success btn-sm apply-order"
                                        data-id="<?= $order['id'] ?>"
                                        data-ux-tooltip="Marcar pedido como aplicado">Aplicar</button>
                                <?php endif; ?>
                                <?php if (($order['status'] ?? 'pending') == 'applied'): ?>
                                    <a href="<?= APP_BASE ?>/order/goodsEntry?id=<?= $order['id'] ?>"
                                        class="btn btn-info btn-sm"
                                        data-ux-tooltip="Registrar entrada de mercancía">Dar Entrada</a>
                                <?php endif; ?>


                                <?php if ($order['status'] == 'received'): ?>
                                    <a href="<?= APP_BASE ?>/order/entrySummary?id=<?= $order['id'] ?>"
                                        class="btn btn-info btn-sm"
                                        data-ux-tooltip="Ver resumen de entrada">Ver Entrada</a>
                                <?php endif; ?>

                                <?php if (in_array(($order['status'] ?? ''), ['applied', 'received'])): ?>
                                    <a href="<?= APP_BASE ?>/order/appliedOrderReport?id=<?= $order['id'] ?>"
                                        target="_blank" class="btn btn-secondary btn-sm"
                                        data-ux-tooltip="Descargar boleta en PDF">Ver Boleta (PDF)</a>
                                <?php endif; ?>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No se encontraron pedidos.</div>
        <?php endif; ?>
    </div>
</section>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function () {
        $('#ordersTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            columnDefs: [{ orderable: false, targets: -1 }]
        });
        $(".apply-order").click(function () {
            let currentOrderId = $(this).data("id");
            
            window.ActionModal.show({
                title: 'Aplicar Pedido',
                description: '¿Está seguro de aplicar este pedido?',
                fields: [],
                confirmText: 'Sí, aplicar',
                onConfirm: function() {
                    window.ActionModal.hide();

                    $.ajax({
                        url: "<?= APP_BASE ?>/order/updateStatus?id=" + currentOrderId,
                        type: "POST",
                        dataType: "json",
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Pedido aplicado',
                                    text: response.message
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Ocurrió un error en el servidor.'
                            });
                        }
                    });
                }
            });
        });
    });
</script>