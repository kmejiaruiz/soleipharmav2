<section class="content-header">
  <div class="container-fluid">
    <h1>Inventario</h1>
  </div>
</section>
<section class="content">
  <div class="container-fluid">
    <!-- Listado de Productos -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Productos</h3>
      </div>
      <div class="card-body">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Stock</th>
              <th>Disponible</th>
              <th>Última Actualización</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($products as $product): ?>
              <tr>
                <td><?= $product['id'] ?></td>
                <td><?= $product['name'] ?></td>
                <td><?= $product['stock'] ?></td>
                <td><?= $product['available'] ? 'Sí' : 'No' ?></td>
                <td><?= $product['updated_at'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- Historial de Cambios -->
    <div class="card mt-4">
      <div class="card-header">
        <h3 class="card-title">Historial de Cambios</h3>
      </div>
      <div class="card-body">
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>ID Log</th>
              <th>Producto</th>
              <th>Tipo de Cambio</th>
              <th>Stock Anterior</th>
              <th>Stock Nuevo</th>
              <th>Realizado Por</th>
              <th>Descripción</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($logs as $log): ?>
              <tr>
                <td><?= $log['id'] ?></td>
                <td><?= $log['product_id'] ?></td>
                <td><?= $log['change_type'] ?></td>
                <td><?= $log['previous_stock'] ?></td>
                <td><?= $log['new_stock'] ?></td>
                <td><?= $log['admin_name'] ?></td>
                <td><?= $log['description'] ?></td>
                <td><?= $log['created_at'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
