<section class="content-header">
    <div class="container-fluid">
        <h1>Gestión del Carousel</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <a href="index.php?controller=carousel&action=add" class="btn btn-success mb-3">Agregar Nuevo Slide</a>
        <?php if(!empty($slides)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($slides as $slide): ?>
                <tr>
                    <td><?= $slide['id'] ?></td>
                    <td><img src="<?= htmlspecialchars($slide['image']) ?>"
                            alt="<?= htmlspecialchars($slide['title']) ?>" style="width:100px;"></td>
                    <td><?= htmlspecialchars($slide['title']) ?></td>
                    <td><?= htmlspecialchars($slide['description']) ?></td>
                    <td>
                        <a href="index.php?controller=carousel&action=edit&id=<?= $slide['id'] ?>"
                            class="btn btn-primary btn-sm">Editar</a>
                        <a href="index.php?controller=carousel&action=delete&id=<?= $slide['id'] ?>"
                            class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar slide?');">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="alert alert-info">No hay slides en el carousel.</div>
        <?php endif; ?>
    </div>
</section>