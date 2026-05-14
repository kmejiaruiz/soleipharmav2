<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-edit"></i> Editar Producto</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Editar Producto</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-primary" style="max-width:640px;margin:0 auto;">
            <div class="card-header">
                <strong>#<?= $product['id'] ?> — <?= htmlspecialchars($product['name']) ?></strong>
            </div>
            <div class="card-body">
                <form id="editProductForm"
                      action="<?= APP_BASE ?>/admin/updateProduct?id=<?= $product['id'] ?>"
                      method="post">

                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="description" class="form-control" rows="3"
                                  required><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Stock actual</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-boxes"></i></span>
                            </div>
                            <input type="text" id="stockDisplay" class="form-control"
                                   value="<?= intval($product['stock']) ?> unidades"
                                   readonly style="background:#f4f6f9;cursor:pointer;color:#555;">
                        </div>
                        <div id="stockAlert" class="alert alert-warning alert-dismissible py-2 mt-2 mb-0"
                             style="display:none;font-size:13px;">
                            <button type="button" class="close py-1" onclick="document.getElementById('stockAlert').style.display='none'">
                                <span>&times;</span>
                            </button>
                            <i class="fas fa-lock mr-1"></i>
                            <strong>Stock de solo lectura.</strong>
                            Ajústalo desde
                            <a href="<?= APP_BASE ?>/order/create" class="alert-link">Pedidos</a> o
                            <a href="<?= APP_BASE ?>/branchTransfer/create" class="alert-link">Traslados</a>.
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Imagen (URL)</label>
                        <input type="text" name="image" class="form-control"
                               value="<?= htmlspecialchars($product['image'] ?? '') ?>">
                    </div>

                    <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                        <i class="fas fa-info-circle"></i>
                        Los campos de <strong>Costo, IVA y Utilidad</strong> se gestionan desde
                        <a href="<?= APP_BASE ?>/product/updateCostsForm" target="_blank">
                            Costos e IVA de Productos
                        </a>.
                        &nbsp;—&nbsp; Precio de venta actual:
                        <strong>C$ <?= number_format($product['sale_price'] ?? 0, 2) ?></strong>
                    </div>

                    <!-- Credenciales ocultas para validación -->
                    <input type="hidden" name="confirm_username" id="confirmUsername">
                    <input type="hidden" name="confirm_password" id="confirmPassword">

                    <div class="d-flex justify-content-between">
                        <a href="<?= APP_BASE ?>/admin/index" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
// Mostrar alerta al hacer clic en el campo de stock
document.getElementById('stockDisplay').addEventListener('click', function () {
    const alert = document.getElementById('stockAlert');
    alert.style.display = '';
    // Auto-ocultar después de 4 segundos
    clearTimeout(alert._timer);
    alert._timer = setTimeout(() => alert.style.display = 'none', 4000);
});

$('#editProductForm').on('submit', function (e) {
    e.preventDefault();
    window.ActionModal.show({
        title: 'Confirmar actualización',
        description: 'Ingrese credenciales de administrador:',
        fields: [
            { id: 'modal-input-username', type: 'text',     placeholder: 'Usuario' },
            { id: 'modal-input-password', type: 'password', placeholder: 'Contraseña' }
        ],
        onConfirm: function (data) {
            const username = (data['modal-input-username'] || '').trim();
            const password = (data['modal-input-password'] || '').trim();

            if (!username || !password) {
                window.ActionModal.showError('Ingrese usuario y contraseña.');
                return;
            }

            window.ActionModal.hide();
            $('#confirmUsername').val(username);
            $('#confirmPassword').val(password);

            $.ajax({
                url:      $('#editProductForm').attr('action'),
                type:     'POST',
                data:     $('#editProductForm').serialize(),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        Swal.fire({ icon: 'success', title: 'Actualizado', text: res.message })
                            .then(() => window.location.href = '<?= APP_BASE ?>/admin/index');
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    }
                },
                error: function (xhr) {
                    Swal.fire({ icon: 'error', title: 'Error interno', text: xhr.responseText });
                }
            });
        }
    });
});
</script>