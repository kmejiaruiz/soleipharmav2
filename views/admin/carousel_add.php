<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Agregar Nuevo Slide</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Agregar Nuevo Slide</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form action="/soleipharmav2/carousel/save" method="post">
            <div class="form-group">
                <label>Imagen (URL)</label>
                <input type="text" name="image" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="title" class="form-control">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-success">Agregar Slide</button>
            <a href="/soleipharmav2/carousel/index" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</section>