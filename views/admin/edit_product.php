<section class="content-header">
    <div class="container-fluid">
        <h1>Editar Producto</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="editProductForm" action="index.php?controller=admin&action=updateProduct&id=<?= $product['id'] ?>"
            method="post">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>"
                    required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" class="form-control"
                    required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Stock</label>
                <?php if ($_SESSION['user']['role'] === 'superadmin'): ?>
                    <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" required>
                <?php else: ?>
                    <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" readonly
                        title="Necesitas tener credenciales de administrador superior" style="cursor:not-allowed;">
                <?php endif; ?>
            </div>

            <?php if ($_SESSION['user']['role'] === 'superadmin'): ?>
                <hr>
                <h5>Costos e Impuestos (solo Superadmin)</h5>
                <div class="form-group">
                    <label>Costo Unitario</label>
                    <input type="number" step="0.01" id="costInput" name="cost" class="form-control"
                        value="<?= number_format($product['cost'], 2) ?>" required>
                </div>
                <div class="form-group">
                    <label>% Utilidad</label>
                    <input type="number" step="0.01" id="utilityInput" name="utility_percent" class="form-control"
                        value="<?= number_format($product['utility_percent'], 2) ?>" required>
                </div>
                <div class="form-group">
                    <label>% Impuesto (IVA)</label>
                    <input type="number" step="0.01" id="taxInput" name="tax_percent" class="form-control"
                        value="<?= number_format($product['tax_percent'], 2) ?>" required>
                </div>
                <div class="form-group">
                    <label>Precio de Venta Calculado</label>
                    <input type="text" id="salePrice" class="form-control"
                        value="<?= number_format($product['sale_price'], 2) ?>" readonly>
                </div>
            <?php endif; ?>

            <!-- Campos ocultos para credenciales -->
            <input type="hidden" name="confirm_username" id="confirmUsername">
            <input type="hidden" name="confirm_password" id="confirmPassword">
            <button type="submit" class="btn btn-primary mb-4">Actualizar Producto</button>
        </form>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(function () {
        // Función para recalcular precio de venta incluyendo IVA
        function recalcSalePrice() {
            var cost = parseFloat($('#costInput').val()) || 0;
            var util = parseFloat($('#utilityInput').val()) || 0;
            var tax = parseFloat($('#taxInput').val()) || 0;
            // Precio neto con utilidad
            var net = cost * (1 + util / 100);
            // Precio con impuesto
            var sale = (net * (1 + tax / 100)).toFixed(2);
            $('#salePrice').val(sale);
        }

        // Aplicar al cambiar cualquiera de los tres campos
        $('#costInput, #utilityInput, #taxInput').on('input', recalcSalePrice);

        var form = $('#editProductForm');
        form.on('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Confirmar Actualización de Producto',
                html: `<p>Ingrese credenciales de Superadmin:</p>
                   <input type="text" id="swal-input-username" class="swal2-input" placeholder="Usuario">
                   <input type="password" id="swal-input-password" class="swal2-input" placeholder="Contraseña">`,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Confirmar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const username = Swal.getPopup().querySelector('#swal-input-username').value;
                    const password = Swal.getPopup().querySelector('#swal-input-password').value;
                    if (!username || !password) {
                        Swal.showValidationMessage('Ambos campos son obligatorios');
                    }
                    return { username: username, password: password };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#confirmUsername').val(result.value.username);
                    $('#confirmPassword').val(result.value.password);
                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({ icon: 'success', title: 'Actualizado', text: response.message })
                                    .then(() => window.location.href = 'index.php?controller=product&action=index');
                            } else {
                                Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                            }
                        },
                        error: function (xhr) {
                            Swal.fire({ icon: 'error', title: 'Error interno', text: xhr.responseText });
                        }
                    });
                }
            });
        });
    });
</script>