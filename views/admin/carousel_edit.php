<section class="content-header">
    <div class="container-fluid">
        <h1>Editar Slide</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form action="index.php?controller=carousel&action=update&id=<?= $slide['id'] ?>" method="post">
            <div class="form-group">
                <label>Imagen (URL)</label>
                <input type="text" name="image" class="form-control" value="<?= htmlspecialchars($slide['image']) ?>"
                    required>
            </div>
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($slide['title']) ?>">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description"
                    class="form-control"><?= htmlspecialchars($slide['description']) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Actualizar Slide</button>
            <a href="index.php?controller=carousel&action=index" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</section>