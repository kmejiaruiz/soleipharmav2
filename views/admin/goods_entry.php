<?php
// Se esperan las variables: $order (datos del pedido) y $orderItems (arreglo de ítems con 'product_id', 'sku', 'name' y 'quantity').
?>
<section class="content-header">
    <div class="container-fluid">
        <h1>Entrada de Mercadería para Pedido #<?= htmlspecialchars($order['id']) ?></h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <p>Estado del pedido: <?= htmlspecialchars($order['status']) ?></p>
        <form id="goodsEntryForm"
            action="index.php?controller=order&action=storeGoodsEntry&id=<?= htmlspecialchars($order['id']) ?>"
            method="post">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th>Cantidad Ordenada</th>
                        <th>Cantidad Recibida</th>
                        <!-- <th>Justificación (si menor)</th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['sku'] ?? $item['product_id']) ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>
                                <input type="number" name="received_quantities[<?= $item['product_id'] ?>]"
                                    value="<?= $item['quantity'] ?>" min="0" class="form-control received-qty"
                                    data-ordered="<?= $item['quantity'] ?>">
                            </td>
                            <td>
                                <select name="justifications[<?= $item['product_id'] ?>]"
                                    class="form-control justification-select" style="display: none;">
                                    <option value="">Seleccione una justificación</option>
                                    <option value="Sin justificación por parte del proveedor">Sin justificación por parte
                                        del proveedor</option>
                                    <option value="Daño en transporte">Daño en transporte</option>
                                    <option value="Pérdida durante almacenamiento">Pérdida durante almacenamiento</option>
                                    <option value="Error en la entrega">Error en la entrega</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary" id="button">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                <span class="button-text">Registrar Entrada</span>
            </button>
        </form>

        <!-- Loader: Cubrir toda la pantalla -->
        <!-- <div id="loader">
            <div class="text-white text-xl" id="loader">Procesando...</div>
        </div> -->
    </div>

</section>
<!-- Incluir jQuery y SweetAlert2 -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(window).on('load', function () {
        $('#loader').slideUp(250);
    });

    $(document).ready(function () {
        $(".received-qty").on("input", function () {
            var orderedQty = parseInt($(this).data("ordered"));
            var receivedQty = parseInt($(this).val());
            var justificationSelect = $(this).closest("tr").find(".justification-select");
            justificationSelect.toggle(receivedQty < orderedQty);
            if (receivedQty >= orderedQty) {
                justificationSelect.val("");
            }
        });

        $("#goodsEntryForm").on("submit", function (e) {
            e.preventDefault();

            let button = $("#button");
            button.prop("disabled", true);
            button.find(".spinner-border").removeClass("d-none");
            button.find(".button-text").text("Procesando entrada");

            $("#loader").removeClass("slide-out").show();

            var formData = $(this).serialize();
            $.ajax({
                url: $(this).attr("action"),
                type: "POST",
                data: formData,
                dataType: "json",
                success: function (response) {
                    $("#loader").addClass("slide-out");
                    setTimeout(function () {
                        $("#loader").hide().removeClass("slide-out");

                        if (response.success) {
                            // Si la entrada es exitosa, mostrar un modal normal
                            Swal.fire({
                                icon: 'success',
                                title: 'Entrada registrada',
                                text: response.message,
                                confirmButtonText: 'Aceptar'
                            }).then(() => {
                                window.location.href = "index.php?controller=order&action=index";
                            });
                        } else {
                            // Si hay un error, mostrar un toast
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            });

                            button.prop("disabled", false);
                            button.find(".spinner-border").addClass("d-none");
                            button.find(".button-text").text("Registrar Entrada");
                        }
                    }, 500);
                },
                error: function () {
                    $("#loader").addClass("slide-out");
                    setTimeout(function () {
                        $("#loader").hide().removeClass("slide-out");

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error en el servidor.',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });

                        button.prop("disabled", false);
                        button.find(".spinner-border").addClass("d-none");
                        button.find(".button-text").text("Registrar Entrada");
                    }, 500);
                }
            });
        });
    });
</script>