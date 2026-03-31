<?php
// Se espera que el controlador pase la variable $nextNoteNumber (número de nota autogenerado)
?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Crear Nota de Crédito/Débito</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="/soleipharmav2/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Crear Nota de Crédito/Débito</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <form id="notesForm" action="/soleipharmav2/notes/save" method="post">
            <div class="form-group">
                <label>Número de Nota</label>
                <input type="text" name="note_number" class="form-control"
                    value="<?= htmlspecialchars($nextNoteNumber) ?>" readonly>
            </div>
            <div class="form-group">
                <label>Tipo de Nota</label>
                <select name="type" class="form-control" required>
                    <option value="credit">Crédito</option>
                    <option value="debit">Débito</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nombre del Cliente</label>
                <input type="text" name="client_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Documento del Cliente (RUC/DNI)</label>
                <input type="text" name="client_document" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Dirección del Cliente</label>
                <input type="text" name="client_address" class="form-control">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="form-group">
                <label>Monto</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>
            <!-- Campos ocultos para credenciales de administrador -->
            <input type="hidden" name="confirm_username" id="confirmUsername">
            <input type="hidden" name="confirm_password" id="confirmPassword">
            <button type="submit" class="btn btn-primary">Crear Nota</button>
            <a href="/soleipharmav2/notes/index" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</section>

</section>

<!-- Incluir jQuery (Micromodal JS ya se incluye globalmente en admin_footer) -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    $("#notesForm").on('submit', function (e) {
        e.preventDefault(); // Evita el envío tradicional del formulario
        
        window.ActionModal.show({
            title: 'Confirmar Creación de Nota',
            description: 'Ingrese sus credenciales de administrador para confirmar:',
            fields: [
                { id: 'modal-input-username', type: 'text', placeholder: 'Usuario' },
                { id: 'modal-input-password', type: 'password', placeholder: 'Contraseña' }
            ],
            onConfirm: function(data) {
                const username = data['modal-input-username'] ? data['modal-input-username'].trim() : '';
                const password = data['modal-input-password'] ? data['modal-input-password'].trim() : '';

                if (!username || !password) {
                    window.ActionModal.showError('Debe ingresar usuario y contraseña');
                    return;
                }

                window.ActionModal.hide();

                // Asigna las credenciales ingresadas a los campos ocultos
                $("#confirmUsername").val(username);
                $("#confirmPassword").val(password);
                
                // Serializa y envía el formulario vía AJAX
                var formData = $("#notesForm").serialize();
                $.ajax({
                    url: $("#notesForm").attr("action"),
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Nota creada',
                                text: response.message
                            }).then(() => {
                                window.location.href = "/soleipharmav2/notes/index";
                            });
                        } else {
                            // Si las credenciales son incorrectas u ocurre otro error, se muestra el error sin reiniciar el formulario
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
</script>