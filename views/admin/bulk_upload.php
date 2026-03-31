<!-- views/admin/bulk_upload.php -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Carga Masiva de Productos</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Carga Masiva de Productos</li>
                </ol>
            </div>
        </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <form action="/soleipharmav2/admin/bulkUpload" method="post" enctype="multipart/form-data">
      <div class="form-group">
        <label for="excelFile">Selecciona el archivo Excel</label>
        <input type="file" id="excelFile" name="excel" class="form-control" accept=".xlsx, .xls" required>
      </div>
      <button type="submit" class="btn btn-primary">Cargar Productos</button>
    </form>
    <p class="mt-2 text-muted">El archivo debe tener las columnas: nombre, descripción, precio, imagen, stock.</p>
  </div>
</section>
