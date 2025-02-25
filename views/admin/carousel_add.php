<section class="content-header">
    <div class="container-fluid">
        <h1>Agregar Nuevo Slide</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form action="index.php?controller=carousel&action=save" method="post">
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
            <a href="index.php?controller=carousel&action=index" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</section>