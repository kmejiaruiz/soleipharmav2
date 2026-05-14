<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Gestión del Carousel</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Gestión del Carousel</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <a href="<?= APP_BASE ?>/carousel/add" class="btn btn-success mb-3">Agregar Nuevo Slide</a>
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
                        <a href="<?= APP_BASE ?>/carousel/edit?id=<?= $slide['id'] ?>"
                            class="btn btn-primary btn-sm">Editar</a>
                        <a href="<?= APP_BASE ?>/carousel/delete?id=<?= $slide['id'] ?>"
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