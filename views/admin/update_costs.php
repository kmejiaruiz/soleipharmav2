<?php
// Se espera que el controlador pase la variable $products, donde cada producto tiene: id, sku, name y price.
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Actualizar Costos de Productos</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Actualizar Costos de Productos</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="updateCostsForm" action="/soleipharmav2/product/updateCosts" method="post">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th>Precio Venta (Ref)</th>
                        <th>Costo Actual</th>
                        <th>Nuevo Costo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['sku'] ?? $product['id']) ?></td>
                            <td><?= htmlspecialchars($product['name'] ?? '') ?></td>
                            <td>$<?= number_format($product['price'], 2) ?></td>
                            <td>$<?= number_format($product['cost'], 2) ?></td>
                            <td>
                                <input type="number" name="costs[<?= $product['id'] ?>]"
                                    value="<?= number_format($product['cost'], 2, '.', '') ?>" step="0.01"
                                    class="form-control">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Actualizar Costos</button>
        </form>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    $("#updateCostsForm").on("submit", function (e) {
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
                        title: 'Actualización exitosa',
                        text: response.message
                    }).then(function () {
                        window.location.href = "/soleipharmav2/product/updateCostsForm";
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
    });
</script>