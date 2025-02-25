<section class="content-header">
  <div class="container-fluid">
    <h1>Top 10 Productos Más Vendidos</h1>
  </div>
</section>
<section class="content">
  <div class="container-fluid">
    <?php if(!empty($topProducts)): ?>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>ID Producto</th>
            <th>Nombre</th>
            <th>Cantidad Vendida</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($topProducts as $product): ?>
            <tr>
              <td><?= $product['id'] ?></td>
              <td><?= $product['name'] ?></td>
              <td><?= $product['total_quantity'] ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="alert alert-info">No hay datos de ventas.</div>
    <?php endif; ?>
  </div>
</section>
