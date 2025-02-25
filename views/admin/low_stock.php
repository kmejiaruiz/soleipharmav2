<section class="content-header">
    <div class="container-fluid">
        <h1>Productos con Bajo Stock</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <?php if(!empty($lowStockProducts)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Producto</th>
                    <th>Nombre</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($lowStockProducts as $product): ?>
                <tr>
                    <td><?= $product['id'] ?></td>
                    <td><?= $product['name'] ?></td>
                    <td><?= $product['stock'] ?></td>
                    <td>
                        <a href="index.php?controller=admin&action=increaseStock&id=<?= $product['id'] ?>"
                            class="btn btn-sm btn-warning">
                            Aumentar Stock
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="alert alert-info">No hay productos con bajo stock.</div>
        <?php endif; ?>
    </div>
</section>