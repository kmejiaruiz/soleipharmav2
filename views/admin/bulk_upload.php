<!-- views/admin/bulk_upload.php -->
<section class="content-header">
  <div class="container-fluid">
    <h1>Carga Masiva de Productos</h1>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <form action="index.php?controller=admin&action=bulkUpload" method="post" enctype="multipart/form-data">
      <div class="form-group">
        <label for="excelFile">Selecciona el archivo Excel</label>
        <input type="file" id="excelFile" name="excel" class="form-control" accept=".xlsx, .xls" required>
      </div>
      <button type="submit" class="btn btn-primary">Cargar Productos</button>
    </form>
    <p class="mt-2 text-muted">El archivo debe tener las columnas: nombre, descripción, precio, imagen, stock.</p>
  </div>
</section>
