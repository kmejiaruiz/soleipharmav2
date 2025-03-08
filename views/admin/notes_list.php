<section class="content-header">
    <div class="container-fluid">
        <h1>Listado de Notas de Crédito/Débito</h1>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <a href="index.php?controller=notes&action=add" class="btn btn-success mb-3">Crear Nueva Nota</a>
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

<!-- Incluir SweetAlert2 y jQuery -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    $(document).ready(function () {
        $(".annul-note").click(function () {
            var noteId = $(this).data("id");
            Swal.fire({
                title: 'Anular Nota',
                html: `<p>Ingrese credenciales de usuario superior (superadmin) para anular la nota:</p>
                   <input type="text" id="swal-input-username" class="swal2-input" placeholder="Usuario">
                   <input type="password" id="swal-input-password" class="swal2-input" placeholder="Contraseña">`,
                focusConfirm: false,
                preConfirm: () => {
                    const username = Swal.getPopup().querySelector('#swal-input-username').value;
                    const password = Swal.getPopup().querySelector('#swal-input-password').value;
                    if (!username || !password) {
                        Swal.showValidationMessage('Debe ingresar usuario y contraseña');
                    }
                    return { username: username, password: password };
                },
                showCancelButton: true,
                confirmButtonText: 'Anular',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "index.php?controller=notes&action=cancel&id=" + noteId,
                        type: "POST",
                        data: {
                            confirm_username: result.value.username,
                            confirm_password: result.value.password
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