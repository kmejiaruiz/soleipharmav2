<?php
// Se espera que el controlador pase la variable $orders (arreglo de pedidos).
?>
<section class="content-header">
    <div class="container-fluid">
        <h1>Listado de Pedidos</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <a href="index.php?controller=order&action=create" class="btn btn-success mb-3">Realizar Pedido</a>
        <?php if (!empty($orders)): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Admin</th>
                        <th>Fecha</th>
                        <th>Status</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['id']) ?></td>
                            <td><?= htmlspecialchars($order['admin_name']) ?></td>
                            <td><?= htmlspecialchars($order['order_date']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($order['status'])) ?></td>
                            <td>
                                <a href="index.php?controller=order&action=edit&id=<?= $order['id'] ?>"
                                    class="btn btn-primary btn-sm">Ver/Editar</a>

                                <?php if ($order['status'] == 'pending'): ?>
                                    <button class="btn btn-success btn-sm apply-order"
                                        data-id="<?= $order['id'] ?>">Aplicar</button>
                                <?php endif; ?>
                                <?php if (($order['status'] ?? 'pending') == 'applied'): ?>
                                    <a href="index.php?controller=order&action=goodsEntry&id=<?= $order['id'] ?>"
                                        class="btn btn-info btn-sm">Dar Entrada</a>
                                <?php endif; ?>


                                <?php if ($order['status'] == 'received'): ?>
                                    <a href="index.php?controller=order&action=goodsEntryReport&id=<?= $order['id'] ?>"
                                        class="btn btn-info btn-sm">Ver Reporte Entrada</a>
                                    <!-- <a href="#!" 
                                        class="btn btn-info btn-sm" onclick="alert('Referencia a Objeto no establecida como Objeto.')">Ver Reporte Entrada</a> -->
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
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function () {
        $(".apply-order").click(function () {
            var orderId = $(this).data("id");
            Swal.fire({
                title: 'Aplicar Pedido',
                text: '¿Está seguro de aplicar este pedido?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, aplicar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "index.php?controller=order&action=updateStatus&id=" + orderId,
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