<!-- views/admin/role_management.php -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark">Gestión de Usuarios</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/soleipharmav2/">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Gestión de Usuarios</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Listado de Usuarios</h3>
                        <div class="card-tools">
                            <?php if (!empty($disabledUsers)): ?>
                            <button type="button" class="btn btn-sm btn-danger mr-2" onclick="MicroModal.show('disabledUsersModal')">
                                <i class="fas fa-users-slash"></i> Ver Usuarios Deshabilitados (<?= count($disabledUsers) ?>)
                            </button>
                            <?php endif; ?>
                            <a href="/soleipharmav2/admin/roleChangeHistory" class="btn btn-sm btn-info">
                                <i class="fas fa-history"></i> Ver Bitácora de Roles
                            </a>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table id="usersTable" class="table table-bordered table-striped w-100 table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Nombre Completo</th>
                                    <th>Rol Actual</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($users)): ?>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?= $u['id'] ?></td>
                                            <td><?= htmlspecialchars($u['username']) ?></td>
                                            <td><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?></td>
                                            <td>
                                                <?php 
                                                    $badgeClass = 'badge-secondary';
                                                    if ($u['role'] === 'superadmin') $badgeClass = 'badge-purple';
                                                    if ($u['role'] === 'admin') $badgeClass = 'badge-info';
                                                ?>
                                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(strtoupper($u['role'])) ?></span>
                                            </td>
                                            <td>
                                                <?php if (($u['status'] ?? 'active') === 'disabled'): ?>
                                                    <span class="badge badge-danger">Deshabilitado</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info mr-1 mb-1" onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['first_name'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($u['second_name'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($u['last_name'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($u['second_surname'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($u['branch'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                                                    <i class="fas fa-edit"></i> Editar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning mr-1 mb-1" onclick="openChangeRoleModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= htmlspecialchars($u['role']) ?>')">
                                                    <i class="fas fa-user-shield"></i> Roles
                                                </button>
                                                
                                                <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                                                    <?php if (($u['status'] ?? 'active') === 'disabled'): ?>
                                                        <button type="button" class="btn btn-sm btn-success mr-1 mb-1" onclick="toggleUserStatus(<?= $u['id'] ?>, 'active')">
                                                            <i class="fas fa-check-circle"></i> Activar
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-sm btn-secondary mr-1 mb-1" onclick="toggleUserStatus(<?= $u['id'] ?>, 'disabled')">
                                                            <i class="fas fa-ban"></i> Deshabilitar
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-sm btn-danger mb-1" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                                                        <i class="fas fa-trash-alt"></i> Eliminar
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No hay usuarios activos disponibles para gestionar.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Role Change -->
    <div id="changeRoleModal" class="modal micromodal-slide" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="changeRoleModal-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="changeRoleModal-title">
                        Modificar Rol de <span id="crUsernameDisplay"></span>
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="changeRoleModal-content">
                    <p class="mb-3 text-muted text-sm">
                        <i class="fas fa-info-circle"></i> Al cambiar los privilegios de un usuario, el sistema cerrará automáticamente cualquier sesión que tenga activa actualmente.
                    </p>
                    <form id="changeRoleForm">
                        <input type="hidden" id="crUserId" name="user_id">
                        
                        <div class="form-group">
                            <label for="crNewRole" class="font-weight-bold">Nuevo Nivel de Acceso:</label>
                            <select id="crNewRole" name="new_role" class="form-control" required>
                                <option value="user">USER (Básico)</option>
                                <option value="admin">ADMIN (Administrador Comercial)</option>
                                <option value="superadmin">SUPERADMIN (Acceso Total)</option>
                            </select>
                        </div>

                        <div class="form-group mt-3">
                            <label for="crSuperadminPassword" class="font-weight-bold text-danger"><i class="fas fa-lock"></i> Contraseña de Autorización:</label>
                            <div style="position: relative; margin-top: 10px;">
                                <input type="password" id="crSuperadminPassword" name="superadmin_password" class="form-control micromodal-input is-warning" style="margin-top: 0; padding-right: 40px;" required placeholder="Tu contraseña de superadmin..." autocomplete="new-password">
                                <i class="fas fa-eye" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #6c757d; font-size: 1.1rem;" onclick="togglePasswordVisibility('crSuperadminPassword', this)" title="Mostrar contraseña"></i>
                            </div>
                            <span class="micromodal-validation" id="crValidationError"></span>
                        </div>
                    </form>
                </main>
                <footer class="modal-footer">
                    <button type="button" class="btn btn-secondary modal__btn" data-micromodal-close>Cancelar</button>
                    <button type="button" class="btn btn-primary modal__btn modal__btn-primary" onclick="submitChangeRoleForm()">Confirmar Cambio</button>
                </footer>
            </div>
        </div>
    </div>

    <!-- Modal for Edit User -->
    <div id="editUserModal" class="modal micromodal-slide" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="editUserModal-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="editUserModal-title">
                        Editar Datos de <span id="euUsernameDisplay"></span>
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="editUserModal-content">
                    <p class="mb-3 text-muted text-sm">
                        <i class="fas fa-exclamation-triangle text-warning"></i> <strong>Nota:</strong> Los datos personales (nombres y apellidos) solo pueden actualizarse 1 vez cada 6 meses. El cambio de sucursal es ilimitado, pero forzará el cierre de sesión del usuario.
                    </p>
                    <form id="editUserForm">
                        <input type="hidden" id="euUserId" name="edit_user_id">
                        
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="euFirstName">Primer Nombre *</label>
                                <input type="text" id="euFirstName" name="first_name" class="form-control micromodal-input" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="euSecondName">Segundo Nombre</label>
                                <input type="text" id="euSecondName" name="second_name" class="form-control micromodal-input">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="euLastName">Primer Apellido *</label>
                                <input type="text" id="euLastName" name="last_name" class="form-control micromodal-input" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="euSecondSurname">Segundo Apellido</label>
                                <input type="text" id="euSecondSurname" name="second_surname" class="form-control micromodal-input">
                            </div>
                        </div>

                        <div class="form-group mt-2">
                            <label for="euBranch"><i class="fas fa-store"></i> Sucursal Asignada *</label>
                            <select id="euBranch" name="branch" class="form-control micromodal-input" required>
                                <option value="Sucursal Leon">Sucursal León</option>
                                <option value="Sucursal Managua">Sucursal Managua</option>
                                <option value="Sucursal Chinandega">Sucursal Chinandega</option>
                                <option value="Sucursal Rivas">Sucursal Rivas</option>
                            </select>
                        </div>
                        <span class="micromodal-validation text-danger" id="euValidationError" style="display:none; margin-top:10px;"></span>
                    </form>
                </main>
                <footer class="modal-footer">
                    <button type="button" class="btn btn-secondary modal__btn" data-micromodal-close>Cancelar</button>
                    <button type="button" class="btn btn-info modal__btn" onclick="submitEditUserForm()">Guardar Cambios</button>
                </footer>
            </div>
        </div>
    </div>
    <!-- Modal for Disabled Users -->
    <?php if (!empty($disabledUsers)): ?>
    <div id="disabledUsersModal" class="modal micromodal-slide" aria-hidden="true">
        <div class="modal__overlay" tabindex="-1" data-micromodal-close>
            <div class="modal__container" style="max-width: 900px; width: 95%;" role="dialog" aria-modal="true" aria-labelledby="disabledUsersModal-title">
                <header class="modal__header">
                    <h2 class="modal__title" id="disabledUsersModal-title">
                        Usuarios Deshabilitados del Sistema
                    </h2>
                    <button class="modal__close" aria-label="Close modal" data-micromodal-close></button>
                </header>
                <main class="modal__content" id="disabledUsersModal-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Nombre Completo</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($disabledUsers as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['username']) ?></td>
                                        <td><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?></td>
                                        <td><span class="badge badge-secondary"><?= htmlspecialchars(strtoupper($u['role'])) ?></span></td>
                                        <td>
                                            <?php if ($u['id'] !== $_SESSION['user']['id']): ?>
                                                <button type="button" class="btn btn-sm btn-success mr-1 mb-1" onclick="MicroModal.close('disabledUsersModal'); toggleUserStatus(<?= $u['id'] ?>, 'active')">
                                                    <i class="fas fa-check-circle"></i> Activar
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger mb-1" onclick="MicroModal.close('disabledUsersModal'); deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['username'])) ?>')">
                                                    <i class="fas fa-trash-alt"></i> Eliminar
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </main>
                <footer class="modal-footer">
                    <button type="button" class="btn btn-secondary modal__btn" data-micromodal-close>Cerrar</button>
                </footer>
            </div>
        </div>
    </div>
    <?php endif; ?>

</section>

<!-- MicroModal setup and sweetalert -->
<script src="https://cdn.jsdelivr.net/npm/micromodal/dist/micromodal.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        MicroModal.init({
            disableScroll: true,
            awaitOpenAnimation: true,
            awaitCloseAnimation: true
        });

        // Submit form via Enter key
        document.getElementById('crSuperadminPassword').addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitChangeRoleForm();
            }
        });
    });

    function togglePasswordVisibility(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
            icon.title = "Ocultar contraseña";
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            icon.title = "Mostrar contraseña";
        }
    }

    function openChangeRoleModal(userId, username, currentRole) {
        document.getElementById('crUserId').value = userId;
        document.getElementById('crUsernameDisplay').textContent = username;
        document.getElementById('crNewRole').value = currentRole;
        document.getElementById('crSuperadminPassword').value = '';
        document.getElementById('crValidationError').style.display = 'none';
        
        MicroModal.show('changeRoleModal');
        setTimeout(() => document.getElementById('crSuperadminPassword').focus(), 150);
    }

    function submitChangeRoleForm() {
        const form = document.getElementById('changeRoleForm');
        const formData = new FormData(form);
        
        // Append CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            formData.append('csrf_token', csrfMeta.getAttribute('content'));
        }

        const passVal = document.getElementById('crSuperadminPassword').value.trim();
        let errorHint = document.getElementById('crValidationError');
        
        if (!passVal) {
            errorHint.textContent = 'La contraseña es obligatoria para autorizar este cambio.';
            errorHint.style.display = 'block';
            return;
        }
        errorHint.style.display = 'none';

        fetch('/soleipharmav2/admin/changeRoleAction', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                MicroModal.close('changeRoleModal');
                Swal.fire({
                    icon: 'success',
                    title: 'Privilegios Actualizados',
                    text: data.message,
                    timer: 2500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                errorHint.textContent = data.message;
                errorHint.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorHint.textContent = 'Ocurrió un error en la solicitud.';
            errorHint.style.display = 'block';
        });
    }

    function openEditUserModal(id, firstName, secondName, lastName, secondSurname, branch, username) {
        document.getElementById('euUserId').value = id;
        document.getElementById('euUsernameDisplay').textContent = username;
        
        document.getElementById('euFirstName').value = firstName;
        document.getElementById('euSecondName').value = secondName;
        document.getElementById('euLastName').value = lastName;
        document.getElementById('euSecondSurname').value = secondSurname;
        document.getElementById('euBranch').value = branch;
        
        document.getElementById('euValidationError').style.display = 'none';
        MicroModal.show('editUserModal');
    }

    function submitEditUserForm() {
        const form = document.getElementById('editUserForm');
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        
        // Append CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            formData.append('csrf_token', csrfMeta.getAttribute('content'));
        }

        let errorHint = document.getElementById('euValidationError');
        errorHint.style.display = 'none';

        fetch('/soleipharmav2/admin/editUserAction', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                MicroModal.close('editUserModal');
                Swal.fire({
                    icon: 'success',
                    title: 'Usuario Actualizado',
                    text: data.message,
                    timer: 3500,
                    showConfirmButton: true
                }).then(() => {
                    location.reload();
                });
            } else {
                errorHint.textContent = data.message;
                errorHint.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorHint.textContent = 'Ocurrió un error en la solicitud.';
            errorHint.style.display = 'block';
        });
    }

    function toggleUserStatus(userId, newStatus) {
        const actionText = newStatus === 'active' ? 'activar' : 'deshabilitar';
        Swal.fire({
            title: '¿Confirmar acción?',
            text: `¿Estás seguro que deseas ${actionText} a este usuario?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: newStatus === 'active' ? '#28a745' : '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${actionText}`
        }).then((result) => {
            if (result.isConfirmed) {
                window.ActionModal.show({
                    title: 'Credenciales Admin',
                    description: `Ingrese credenciales de administrador para ${actionText} a este usuario.`,
                    fields: [
                        { id: 'auth_username', type: 'text', placeholder: 'Usuario' },
                        { id: 'auth_password', type: 'password', placeholder: 'Contraseña' }
                    ],
                    onConfirm: function(data) {
                        if (!data.auth_username || !data.auth_password) {
                            window.ActionModal.showError('Ambos campos son obligatorios.');
                            return;
                        }

                        const formData = new FormData();
                        formData.append('user_id', userId);
                        formData.append('new_status', newStatus);
                        formData.append('auth_username', data.auth_username);
                        formData.append('auth_password', data.auth_password);

                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        if (csrfMeta) formData.append('csrf_token', csrfMeta.getAttribute('content'));

                        window.ActionModal.hide();

                        fetch('/soleipharmav2/admin/toggleUserStatusAction', {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp.success) {
                                Swal.fire('¡Éxito!', resp.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Denegado', resp.message, 'error');
                            }
                        }).catch(() => Swal.fire('Error', 'Problema de red o servidor.', 'error'));
                    }
                });
            }
        });
    }

    function deleteUser(userId, usernameTarget) {
        Swal.fire({
            title: `¿Eliminar a ${usernameTarget}?`,
            text: "Esta acción borrará la cuenta del sistema si no tiene otras restricciones. ¡No se puede deshacer!",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, ¡Eliminar!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.ActionModal.show({
                    title: 'Eliminación Crítica',
                    description: `Ingrese credenciales de administrador para aprobar la eliminación permanentemente de <b>${usernameTarget}</b>.`,
                    fields: [
                        { id: 'auth_username', type: 'text', placeholder: 'Usuario' },
                        { id: 'auth_password', type: 'password', placeholder: 'Contraseña' }
                    ],
                    onConfirm: function(data) {
                        if (!data.auth_username || !data.auth_password) {
                            window.ActionModal.showError('Ambos campos son obligatorios.');
                            return;
                        }

                        const formData = new FormData();
                        formData.append('user_id', userId);
                        formData.append('auth_username', data.auth_username);
                        formData.append('auth_password', data.auth_password);

                        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                        if (csrfMeta) formData.append('csrf_token', csrfMeta.getAttribute('content'));

                        window.ActionModal.hide();

                        fetch('/soleipharmav2/admin/deleteUserAction', {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(resp => {
                            if (resp.success) {
                                Swal.fire('¡Eliminado!', resp.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Denegado o Imposible',
                                    text: resp.message,
                                    footer: 'Sugerencia: Usa el botón "Deshabilitar" si este usuario no puede eliminarse.'
                                });
                            }
                        }).catch(() => Swal.fire('Error', 'Problema de red o servidor.', 'error'));
                    }
                });
            }
        });
    }

    // Initialize DataTables
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof $ !== 'undefined' && $.fn.DataTable) {
            $('#usersTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "pageLength": 10,
                "responsive": true,
                "order": [[ 3, "asc" ], [ 2, "asc" ]], // Order by Role then Name
                "columnDefs": [
                    { "orderable": false, "targets": [4, 5] } // Disable ordering on Actions column
                ]
            });
        }
    });
</script>
<style>
.badge-purple {
    background-color: #6f42c1;
    color: #fff;
}
</style>
