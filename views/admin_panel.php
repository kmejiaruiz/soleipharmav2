<h1>Panel de Administración</h1>
<a href="index.php?controller=admin&action=create" class="btn btn-success mb-3">Agregar Producto</a>

<table class="table">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Precio</th>
      <th>Stock</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($products as $product): ?>
    <tr>
      <td><?= $product['id'] ?></td>
      <td><?= $product['name'] ?></td>
      <td>$<?= $product['price'] ?></td>
      <td><?= $product['stock'] ?></td>
      <td>
          <a href="index.php?controller=admin&action=edit&id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">Editar</a>
          <a href="index.php?controller=admin&action=delete&id=<?= $product['id'] ?>" class="btn btn-danger btn-sm">Eliminar</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
