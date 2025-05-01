<?php
// views/admin/orders_edit.php
// Se esperan las variables $order y $orderItems
?>
<section class="content-header">
    <div class="container-fluid">
        <h1>Editar Pedido #<?= htmlspecialchars($order['id']) ?></h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <?php if (!empty($orderItems)): ?>
            <form id="editOrderForm"
                action="index.php?controller=order&action=update&id=<?= htmlspecialchars($order['id']) ?>" method="post">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Producto</th>
                            <th>Cantidad Ordenada</th>
                            <th>Cantidad (Editar)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['sku'] ?? $item['product_id']) ?></td>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td>
                                    <input type="number" name="quantities[<?= $item['product_id'] ?>]"
                                        value="<?= htmlspecialchars($item['quantity']) ?>" min="0" class="form-control">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary">Actualizar Pedido</button>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">No hay ítems en el pedido seleccionado.</div>
        <?php endif; ?>
    </div>
</section>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $("#editOrderForm").on("submit", function (e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: response.message
                    }).then(() => {
                        window.location.href = "index.php?controller=order&action=index";
                    });
                } else {
                    // Si success es false, mostramos el mensaje en un swal
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
    });
</script>