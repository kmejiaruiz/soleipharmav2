<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Producto</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Editar Producto</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="editProductForm" action="/soleipharmav2/admin/updateProduct?id=<?= $product['id'] ?>"
            method="post">
            <div class="form-group">
                <label>Nombre</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name'] ?? '') ?>"
                    required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" class="form-control"
                    required><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
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
<script>
    $(function () {
        // Función para recalcular precio de venta incluyendo IVA
        function recalcSalePrice() {
            var cost = parseFloat($('#costInput').val()) || 0;
            var util = parseFloat($('#utilityInput').val()) || 0;
            var tax = parseFloat($('#taxInput').val()) || 0;
            var net = cost * (1 + util / 100);
            var sale = (net * (1 + tax / 100)).toFixed(2);
            $('#salePrice').val(sale);
        }

        $('#costInput, #utilityInput, #taxInput').on('input', recalcSalePrice);

        var form = $('#editProductForm');
        
        form.on('submit', function (e) {
            e.preventDefault();
            window.ActionModal.show({
                title: 'Confirmar Actualización',
                description: 'Ingrese credenciales de Superadmin:',
                fields: [
                    { id: 'modal-input-username', type: 'text', placeholder: 'Usuario' },
                    { id: 'modal-input-password', type: 'password', placeholder: 'Contraseña' }
                ],
                onConfirm: function(data) {
                    const username = data['modal-input-username'] ? data['modal-input-username'].trim() : '';
                    const password = data['modal-input-password'] ? data['modal-input-password'].trim() : '';

                    if (!username || !password) {
                        window.ActionModal.showError('Ambos campos son obligatorios');
                        return;
                    }

                    window.ActionModal.hide();

                    $('#confirmUsername').val(username);
                    $('#confirmPassword').val(password);
                    
                    $.ajax({
                        url: form.attr('action'),
                        type: 'POST',
                        data: form.serialize(),
                        dataType: 'json',
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({ icon: 'success', title: 'Actualizado', text: response.message })
                                    .then(() => window.location.href = '/soleipharmav2/admin/index'); // Redirigir a listado
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