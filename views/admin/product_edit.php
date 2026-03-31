<!-- views/product_edit.php -->
<?php if (session_status() === PHP_SESSION_NONE)
    session_start(); ?>
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
<form action="/soleipharmav2/product/update?id=<?= $product['id'] ?>" method="POST">
    <div>
        <label>Nombre:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
    </div>
    <div>
        <label>Descripción:</label>
        <textarea name="description"><?= htmlspecialchars($product['description']) ?></textarea>
    </div>
    <div>
        <label>Stock:</label>
        <input type="number" name="stock" value="<?= $product['stock'] ?>" required>
    </div>

    <?php if ($_SESSION['user']['role'] === 'superadmin'): ?>
        <hr>
        <h2>Costos e Impuestos</h2>
        <div>
            <label>Costo Unitario:</label>
            <input type="number" step="0.01" name="cost" value="<?= $product['cost'] ?>" required>
        </div>
        <div>
            <label>% Utilidad:</label>
            <input type="number" step="0.01" name="utility_percent" value="<?= $product['utility_percent'] ?>" required>
        </div>
        <div>
            <label>% Impuesto:</label>
            <input type="number" step="0.01" name="tax_percent" value="<?= $product['tax_percent'] ?>" required>
        </div>
        <div>
            <strong>Precio Venta:</strong> <?= number_format($product['sale_price'], 2) ?>
        </div>
    <?php endif; ?>

    <button type="submit">Guardar</button>
</form>