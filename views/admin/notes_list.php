<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Listado de Notas de Crédito/Débito</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Listado de Notas de Crédito/Débito</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <a href="<?= APP_BASE ?>/notes/add" class="btn btn-success mb-3">Crear Nueva Nota</a>
        <?php if (!empty($notes)): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Monto</th>
                        <th>Admin</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notes as $note): ?>
                        <tr>
                            <td><?= $note['id'] ?></td>
                            <td><?= ucfirst($note['type']) ?></td>
                            <td><?= htmlspecialchars($note['note_number']) ?></td>
                            <td><?= htmlspecialchars($note['client_name']) ?></td>
                            <td><?= htmlspecialchars($note['client_document']) ?></td>
                            <td>$<?= number_format($note['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($note['admin_name']) ?></td>
                            <td><?= $note['created_at'] ?></td>
                            <td><?= ucfirst($note['status'] ?? 'active') ?></td>
                            <td>
                                <?php if (($note['status'] ?? 'active') == 'active'): ?>
                                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] == 'superadmin'): ?>
                                        <button class="btn btn-danger btn-sm annul-note" data-id="<?= $note['id'] ?>">Anular</button>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Sin permisos</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Anulada</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        <?php else: ?>
            <div class="alert alert-info">No hay notas registradas.</div>
        <?php endif; ?>
    </div>
</section>

</section>

<!-- Incluir jQuery (Micromodal JS ya se incluye globalmente en admin_footer) -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    $(document).ready(function () {
        $(".annul-note").click(function () {
            let currentNoteId = $(this).data("id");
            
            window.ActionModal.show({
                title: 'Anular Nota',
                description: 'Ingrese credenciales de usuario superior (superadmin) para anular la nota:',
                fields: [
                    { id: 'modal-input-username', type: 'text', placeholder: 'Usuario' },
                    { id: 'modal-input-password', type: 'password', placeholder: 'Contraseña' }
                ],
                confirmText: 'Anular',
                onConfirm: function(data) {
                    const username = data['modal-input-username'] ? data['modal-input-username'].trim() : '';
                    const password = data['modal-input-password'] ? data['modal-input-password'].trim() : '';

                    if (!username || !password) {
                        window.ActionModal.showError('Debe ingresar usuario y contraseña');
                        return;
                    }

                    window.ActionModal.hide();

                    $.ajax({
                        url: "<?= APP_BASE ?>/notes/cancel?id=" + currentNoteId,
                        type: "POST",
                        data: {
                            confirm_username: username,
                            confirm_password: password
                        },
                        dataType: "json",
                        success: function (response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Nota anulada',
                                    text: response.message
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Ocurrió un error en el servidor.'
                            });
                        }
                    });
                }
            });
        });
    });
</script>