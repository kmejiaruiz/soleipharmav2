<form action="index.php?controller=product&action=update&id=<?= $product['id'] ?>" method="POST">
    <!-- campos habituales: name, desc, stock -->
    <?php if ($_SESSION['user']['role'] === 'superadmin'): ?>
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
        <p>Precio Venta: <strong><?= $product['sale_price'] ?></strong></p>
    <?php endif; ?>
    <button type="submit">Guardar</button>
</form>