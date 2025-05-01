<?php
// Se espera que el controlador pase la variable $products (arreglo de productos).
?>
<section class="content-header">
    <div class="container-fluid">
        <h1>Realizar Pedido de Productos</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="orderForm" action="index.php?controller=order&action=store" method="post">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Nombre</th>
                        <th>Stock Actual</th>
                        <th>Cantidad a Pedir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['sku'] ?? $product['id']) ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= $product['stock'] ?></td>
                            <td>
                                <input type="number" name="quantities[<?= $product['id'] ?>]" value="0" min="0"
                                    class="form-control">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Realizar Pedido</button>
        </form>
        <!-- Loader con fade -->
        <div id="loader" class="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center hidden">
            <div class="text-white text-xl">Cargando...</div>
        </div>
    </div>
</section>
<!-- Incluir jQuery y SweetAlert2 -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $("#orderForm").on("submit", function (e) {
        e.preventDefault(); // Evita el envío tradicional del formulario
        // Muestra el loader con efecto fade
        // $("#loader").removeClass("hidden").hide().fadeIn(500);

        var formData = $(this).serialize();

        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (response) {
                // Oculta el loader
                $("#loader").fadeOut(500, function () {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Pedido realizado',
                            text: response.message
                        }).then(() => {
                            // Redirige o limpia el formulario según tu flujo
                            window.location.href = "index.php?controller=order&action=create";
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                });
            },
            error: function () {
                $("#loader").fadeOut(500);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Ocurrió un error en el servidor.'
                });
            }
        });
    });
</script>