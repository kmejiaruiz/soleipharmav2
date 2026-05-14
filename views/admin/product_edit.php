<!-- views/product_edit.php -->
<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<div class="row mb-2">
    <div class="col-sm-6">
        <h1>Editar Producto</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
            <li class="breadcrumb-item active">Editar Producto</li>
        </ol>
    </div>
</div>

<div class="container-fluid">
<div class="card card-outline card-primary" style="max-width:600px;margin:0 auto;">
    <div class="card-header"><strong><i class="fas fa-edit mr-1"></i> Editar Producto #<?= $product['id'] ?></strong></div>
    <div class="card-body">
        <form action="<?= APP_BASE ?>/product/update?id=<?= $product['id'] ?>" method="POST">

            <div class="form-group">
                <label>Nombre <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Stock</label>
                <input type="number" name="stock" class="form-control"
                       value="<?= intval($product['stock']) ?>" min="0" required>
            </div>

            <div class="form-group">
                <label>Imagen (URL)</label>
                <input type="text" name="image" class="form-control"
                       value="<?= htmlspecialchars($product['image'] ?? '') ?>">
            </div>

            <div class="alert alert-info py-2 mb-3" style="font-size:13px;">
                <i class="fas fa-info-circle"></i>
                Los campos de <strong>Costo, IVA y Utilidad</strong> se gestionan desde
                <a href="<?= APP_BASE ?>/product/updateCostsForm">Costos e IVA de Productos</a>.
            </div>

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