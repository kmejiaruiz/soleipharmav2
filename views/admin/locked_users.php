<!-- views/admin/locked_users.php -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Usuarios Bloqueados</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Usuarios Bloqueados</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                
                <!-- Alert Box for Feedback -->
                <div id="unlockAlertBox" class="alert" style="display: none;"></div>

                <!-- Caso 1: Hay usuarios bloqueados -->
                <?php if (!empty($lockedUsers)): ?>
                    <div class="card" id="lockedUsersCard">
                        <div class="card-header bg-danger text-white">
                            <h3 class="card-title">Listado de Cuentas Suspendidas</h3>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table id="lockedUsersTable" class="table table-bordered table-striped w-100 table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Nombre Completo</th>
                                        <th>Rol</th>
                                        <th>Fecha de Bloqueo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lockedUsers as $u): ?>
                                        <tr id="row-user-<?= $u['id'] ?>">
                                            <td><?= $u['id'] ?></td>
                                            <td><?= htmlspecialchars($u['username'] ?? '') ?></td>
                                            <td><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '') . ' ' . ($u['second_surname'] ?? ''))) ?></td>
                                            <td><span class="badge badge-warning"><?= htmlspecialchars($u['role'] ?? '') ?></span></td>
                                            <td data-order="<?= strtotime($u['locked_at'] ?? 'now') ?>"><span class="text-danger"><i class="fas fa-clock"></i> <?= htmlspecialchars($u['locked_at'] ?? '') ?></span></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-success" onclick="openUnlockModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'] ?? '')) ?>')">
                                                    <i class="fas fa-unlock"></i> Desbloquear
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <!-- Caso 2: No hay usuarios bloqueados -->
                <?php else: ?>
                    <div class="alert alert-success mt-4">
                        <h5><i class="icon fas fa-check"></i> Todo en orden</h5>
                        No hay usuarios bloqueados en el sistema en este momento.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- MicroModal setup and ajax logic -->
<script src="https://cdn.jsdelivr.net/npm/micromodal/dist/micromodal.min.js"></script>
<script>
    function openUnlockModal(userId, username) {
        window.ActionModal.show({
            title: `Desbloquear a ${username}`,
            description: 'Para desbloquear este usuario y reestablecer sus intentos de acceso, por favor ingresa tu contraseña de Súper Administrador.',
            fields: [
                { id: 'superadmin_password', type: 'password', placeholder: 'Tu contraseña actual' }
            ],
            confirmText: 'Confirmar Desbloqueo',
            onConfirm: function(data) {
                const passVal = data['superadmin_password'] ? data['superadmin_password'].trim() : '';

                if (!passVal) {
                    window.ActionModal.showError('La contraseña es obligatoria.');
                    return;
                }

                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('superadmin_password', passVal);

                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) {
                    formData.append('csrf_token', csrfMeta.getAttribute('content'));
                }

                window.ActionModal.hide();

                fetch('<?= APP_BASE ?>/admin/unlockUserAction', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        showAlert('success', data.message);
                        
                        const row = document.getElementById('row-user-' + userId);
                        if (row) {
                            row.style.transition = "opacity 0.5s ease";
                            row.style.opacity = 0;
                            setTimeout(() => {
                                row.remove();
                                const tbody = document.querySelector('table tbody');
                                if (tbody && tbody.querySelectorAll('tr[id^="row-user-"]').length === 0) {
                                    const card = document.getElementById('lockedUsersCard');
                                    if (card) {
                                        card.style.transition = "opacity 0.5s ease";
                                        card.style.opacity = 0;
                                        setTimeout(() => {
                                            card.remove();
                                            showAlert('success', 'Todos los usuarios han sido reestablecidos. Ya no hay bloqueos en el sistema.');
                                        }, 500);
                                    }
                                }
                            }, 500);
                        }
                    } else {
                        showAlert('error', data.message);
                    }
                })
                .catch(error => {
                    showAlert('error', 'Ocurrió un error en la solicitud.');
                });
            }
        });
    }

    function showAlert(type, message) {
        const box = document.getElementById('unlockAlertBox');
        box.style.display = 'block';
        if (type === 'success') {
            box.className = 'alert alert-success alert-dismissible';
            box.innerHTML = `<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><h5><i class="icon fas fa-check"></i> Éxito</h5>${message}`;
        } else {
            box.className = 'alert alert-danger alert-dismissible';
            box.innerHTML = `<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button><h5><i class="icon fas fa-ban"></i> Error</h5>${message}`;
        }
    }

    // Initialize DataTables
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#lockedUsersTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "pageLength": 10,
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": 5 } // Disable ordering on Actions column
                ]
            });
        }
    });
</script>
