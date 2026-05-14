<?php // views/admin/supplier_form.php
$isEdit = isset($supplier) && $supplier !== null;
$title  = $isEdit ? 'Editar Proveedor' : 'Nuevo Proveedor';
?>
<section class="content-header">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-truck"></i> <?= $title ?></h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/supplier/index">Proveedores</a></li>
                <li class="breadcrumb-item active"><?= $title ?></li>
            </ol>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?>"></i> <?= $title ?></h3>
                    </div>
                    <div class="card-body">
                        <form id="supplierForm">
                            <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?= $supplier['id'] ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Nombre del Proveedor <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" required
                                            placeholder="Ej. Distribuidora Pharma S.A."
                                            value="<?= htmlspecialchars($supplier['name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>RUC / Cédula Jurídica</label>
                                        <input type="text" name="ruc" class="form-control"
                                            placeholder="Ej. J0310000012345"
                                            value="<?= htmlspecialchars($supplier['ruc'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nombre del Contacto</label>
                                        <input type="text" name="contact_name" class="form-control"
                                            placeholder="Ej. Juan Pérez"
                                            value="<?= htmlspecialchars($supplier['contact_name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Teléfono</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            </div>
                                            <input type="text" name="phone" class="form-control"
                                                placeholder="Ej. 2222-3333"
                                                value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Correo Electrónico</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" name="email" class="form-control"
                                        placeholder="Ej. ventas@proveedor.com"
                                        value="<?= htmlspecialchars($supplier['email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Dirección</label>
                                <textarea name="address" class="form-control" rows="3"
                                    placeholder="Ej. Del semáforo 2 cuadras al norte..."><?= htmlspecialchars($supplier['address'] ?? '') ?></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-right">
                        <a href="<?= APP_BASE ?>/supplier/index" class="btn btn-secondary mr-2">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="button" id="btnSaveSupplier" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $isEdit ? 'Actualizar' : 'Guardar' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
$(document).ready(function() {
    const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    const url = isEdit ? '<?= APP_BASE ?>/supplier/update' : '<?= APP_BASE ?>/supplier/store';

    $('#btnSaveSupplier').click(function() {
        const btn = $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
        $.post(url, $('#supplierForm').serialize(), function(r) {
            if (r.success) {
                Swal.fire({ icon: 'success', title: '¡Listo!', text: r.message, timer: 1800, showConfirmButton: false })
                    .then(() => {
                        if (!isEdit && r.id) {
                            window.location = '<?= APP_BASE ?>/supplier/catalog?id=' + r.id;
                        } else {
                            window.location = '<?= APP_BASE ?>/supplier/index';
                        }
                    });
            } else {
                Swal.fire('Error', r.message, 'error');
                $('#btnSaveSupplier').prop('disabled', false).html('<i class="fas fa-save"></i> <?= $isEdit ? "Actualizar" : "Guardar" ?>');
            }
        }, 'json').fail(function() {
            Swal.fire('Error', 'Fallo de conexión.', 'error');
            $('#btnSaveSupplier').prop('disabled', false).html('<i class="fas fa-save"></i> <?= $isEdit ? "Actualizar" : "Guardar" ?>');
        });
    });
});
</script>
